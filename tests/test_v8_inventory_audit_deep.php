<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;
use Library\Repositories\InventoryAuditRepository;

$db = Database::connection();
$checks = 0;
$assert = static function(bool $condition, string $msg) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $msg);
    }
};

echo "Running Inventory Audit Deep Test Suite (Section 3)...\n";
$repo = new InventoryAuditRepository($db);

// Clean up any old test audits with title matching
$db->exec("DELETE FROM inventory_audit_items WHERE audit_id IN (SELECT id FROM inventory_audits WHERE title LIKE '%Kiểm kê chuyên sâu V8 QA%')");
$db->exec("DELETE FROM inventory_audits WHERE title LIKE '%Kiểm kê chuyên sâu V8 QA%'");

// Lấy ID người dùng có vai trò staff thực tế
$staffUsers = $db->query("SELECT id FROM users WHERE role IN ('admin', 'librarian') ORDER BY id ASC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
$staff1Id = (int) $staffUsers[0];
$staff2Id = isset($staffUsers[1]) ? (int) $staffUsers[1] : $staff1Id;

// 1. Tạo đợt kiểm kê
$audit = $repo->createAudit('Kiểm kê chuyên sâu V8 QA', 'all', $staff1Id, 'Thử nghiệm V8.1');
$auditId = (int) $audit['id'];

try {
    $assert($auditId > 0, 'Audit created with valid ID');
    $assert($audit['status'] === 'in_progress', 'Audit status is in_progress');
    $assert((int)$audit['total_expected'] >= 49, 'Audit total_expected loaded from catalog');

    // Lấy bản sao thực tế từ database
    $copy1 = $db->query("SELECT * FROM book_copies WHERE status = 'available' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $copyOnLoan = $db->query("SELECT * FROM book_copies WHERE status = 'on_loan' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $assert(!empty($copy1), 'Found available copy for test');

    // 2. Quét bản sao hợp lệ đúng vị trí
    $s1 = $repo->scanCopy($auditId, $copy1['copy_code'], $staff1Id, 'good', $copy1['shelf_location']);
    $assert($s1['is_matched'] === true, 'Valid copy matches shelf location');
    $assert($s1['already_scanned'] === false, 'First scan is not duplicate');

    // 3. Quét trùng bản sao trong cùng đợt (Duplicate scan)
    $s1Dup = $repo->scanCopy($auditId, $copy1['copy_code'], $staff1Id, 'worn', $copy1['shelf_location']);
    $assert($s1Dup['already_scanned'] === true, 'Re-scan detected as already_scanned');
    $assert($s1Dup['scanned_condition'] === 'worn', 'Re-scan updated condition to worn');

    $items = $repo->auditItems($auditId);
    $assert(count($items) === 1, 'Duplicate scan did not create duplicate row');

    // 4. Quét mã không tồn tại
    $caughtNonExistent = false;
    try {
        $repo->scanCopy($auditId, 'INVALID_CODE_XYZ_99999', $staff1Id, 'good');
    } catch (RuntimeException $e) {
        $caughtNonExistent = true;
    }
    $assert($caughtNonExistent, 'Scanning non-existent code throws RuntimeException');

    // 5. Quét sách sai vị trí (Misplaced)
    $copy2 = $db->query("SELECT * FROM book_copies WHERE id != {$copy1['id']} AND status = 'available' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($copy2) {
        $s2 = $repo->scanCopy($auditId, $copy2['copy_code'], $staff1Id, 'good', 'Kệ Sai Vị Trí QA-999');
        $assert($s2['is_matched'] === false, 'Misplaced copy correctly detected is_matched=false');
    }

    // 6. Quét sách đang được mượn (On loan)
    if ($copyOnLoan) {
        $sLoan = $repo->scanCopy($auditId, $copyOnLoan['copy_code'], $staff1Id, 'good', 'Quầy kiểm tra');
        $assert($sLoan['copy']['status'] === 'on_loan', 'Scanned copy preserves on_loan status in payload');
    }

    // 7. Quét sách hư hỏng (Damaged condition)
    $copy3 = $db->query("SELECT * FROM book_copies WHERE id NOT IN ({$copy1['id']}, " . ($copy2 ? $copy2['id'] : 0) . ") LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($copy3) {
        $s3 = $repo->scanCopy($auditId, $copy3['copy_code'], $staff1Id, 'damaged', $copy3['shelf_location']);
        $assert($s3['scanned_condition'] === 'damaged', 'Damaged condition recorded');
    }

    // 8. Hai nhân viên thao tác đồng thời (Staff 1 and Staff 2)
    $copy4 = $db->query("SELECT * FROM book_copies WHERE id NOT IN ({$copy1['id']}, " . ($copy2 ? $copy2['id'] : 0) . ", " . ($copy3 ? $copy3['id'] : 0) . ") LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($copy4) {
        $repo->scanCopy($auditId, $copy4['copy_code'], $staff2Id, 'good', $copy4['shelf_location']);
        $allItems = $repo->auditItems($auditId);
        $staff2Items = array_filter($allItems, fn($it) => (int)$it['scanned_by'] === $staff2Id);
        $assert(count($staff2Items) >= 1, 'Staff 2 scan recorded with correct user attribution');
    }

    // 9. Hoàn tất đợt kiểm kê & Phê duyệt kết quả
    $completed = $repo->completeAudit($auditId, $staff1Id, 'Đã nghiệm thu và phê duyệt đợt kiểm kê.');
    $assert($completed['status'] === 'completed', 'Audit completed successfully');
    $assert((int)$completed['approved_by'] === $staff1Id, 'Approved by staff 1 recorded');
    $assert(!empty($completed['completed_at']), 'Completed_at timestamp set');

    // 10. Danh sách sách thiếu (missing copies)
    $missingList = $repo->missingCopies($auditId);
    $assert(is_array($missingList), 'Missing copies returned as array');

} finally {
    // 11. Dọn dẹp dữ liệu kiểm thử an toàn
    $db->exec("DELETE FROM inventory_audit_items WHERE audit_id = {$auditId}");
    $db->exec("DELETE FROM inventory_audits WHERE id = {$auditId}");
}

echo "PASS: Inventory Audit Deep Test Suite ({$checks} assertions passed).\n";
