<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use Library\Core\Database;
use Library\Repositories\InventoryAuditRepository;

$db = Database::connection();
$repo = new InventoryAuditRepository($db);

echo "=== TEST 1: CREATE AUDIT (SHELF SCOPE: Kệ V1-01) ===\n";
$audit = $repo->createAudit('Kiểm tra Kệ V1-01', 'shelf:Kệ V1-01', 1, 'Đợt kiểm kê thử nghiệm');
echo "Created audit ID: {$audit['id']}, Code: {$audit['audit_code']}, Expected: {$audit['total_expected']}\n";

echo "=== TEST 2: SCAN MATCHED COPY ===\n";
$v1Copy = $db->query("SELECT * FROM book_copies WHERE shelf_location = 'Kệ V1-01' LIMIT 1")->fetch();
$res1 = $repo->scanCopy((int) $audit['id'], $v1Copy['copy_code'], 1, 'good', 'Kệ V1-01');
echo "Scanned: {$res1['copy']['copy_code']}, Matched: " . ($res1['is_matched'] ? 'YES' : 'NO') . ", OutOfScope: " . ($res1['out_of_scope'] ? 'YES' : 'NO') . "\n";

echo "=== TEST 3: SCAN MISPLACED COPY ===\n";
$v1Copy2 = $db->query("SELECT * FROM book_copies WHERE shelf_location = 'Kệ V1-01' AND id != {$v1Copy['id']} LIMIT 1")->fetch();
$res2 = $repo->scanCopy((int) $audit['id'], $v1Copy2['copy_code'], 1, 'good', 'Kệ K1-03');
echo "Scanned: {$res2['copy']['copy_code']}, Matched: " . ($res2['is_matched'] ? 'YES' : 'NO') . ", OutOfScope: " . ($res2['out_of_scope'] ? 'YES' : 'NO') . "\n";

echo "=== TEST 4: SCAN OUT-OF-SCOPE COPY ===\n";
$l2Copy = $db->query("SELECT * FROM book_copies WHERE shelf_location = 'Kệ L2-02' LIMIT 1")->fetch();
$res3 = $repo->scanCopy((int) $audit['id'], $l2Copy['copy_code'], 1, 'worn', 'Kệ V1-01');
echo "Scanned: {$res3['copy']['copy_code']}, Matched: " . ($res3['is_matched'] ? 'YES' : 'NO') . ", OutOfScope: " . ($res3['out_of_scope'] ? 'YES' : 'NO') . "\n";

echo "=== TEST 5: DUPLICATE SCAN ===\n";
$res4 = $repo->scanCopy((int) $audit['id'], $v1Copy['copy_code'], 1, 'damaged', 'Kệ V1-01');
echo "Duplicate scanned: " . ($res4['already_scanned'] ? 'YES' : 'NO') . ", Condition: {$res4['scanned_condition']}\n";

echo "=== TEST 6: VERIFY AUDIT TOTALS ===\n";
$auditUpdated = $repo->findAudit((int) $audit['id']);
echo "Total Scanned: {$auditUpdated['total_scanned']}\n";
echo "Total Matched: {$auditUpdated['total_matched']}\n";
echo "Total Misplaced: {$auditUpdated['total_misplaced']}\n";
echo "Total Damaged: {$auditUpdated['total_damaged']}\n";

echo "=== TEST 7: ITEMS AND NOTES ===\n";
$items = $repo->auditItems((int) $audit['id']);
foreach ($items as $it) {
    echo "- Item {$it['copy_code']}: Condition={$it['scanned_condition']}, Matched={$it['is_matched']}, Note={$it['note']}\n";
}

echo "=== TEST 8: COMPLETE AUDIT ===\n";
$completed = $repo->completeAudit((int) $audit['id'], 1, 'Chốt kiểm kê thành công.');
echo "Completed status: {$completed['status']}, Approver: {$completed['approved_by']}\n";

echo "=== TEST 9: CLEANUP TEST AUDIT RECORD ===\n";
$db->exec("DELETE FROM inventory_audit_items WHERE audit_id = {$audit['id']}");
$db->exec("DELETE FROM inventory_audits WHERE id = {$audit['id']}");
echo "Cleaned up test audit successfully!\n";
