<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;
use Library\Repositories\FavoriteRepository;
use Library\Repositories\ReviewRepository;
use Library\Repositories\InventoryAuditRepository;
use Library\Services\BarcodeService;

$db = Database::connection();
$checks = 0;
$assert = static function(bool $condition, string $msg) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $msg);
    }
};

echo "Running Phase 3 & Phase 4 test suite...\n";

// 1. Test BarcodeService
$barcodeSvc = new BarcodeService();
$barcodeSvg = $barcodeSvc->renderBarcodeSvg('B001-0001');
$assert(str_starts_with($barcodeSvg, '<svg') && str_contains($barcodeSvg, 'B001-0001'), 'Barcode SVG generated for copy code.');

$qrSvg = $barcodeSvc->renderQrSvg('B001-0001');
$assert(str_starts_with($qrSvg, '<svg') && str_contains($qrSvg, '#193E30'), 'QR code SVG generated with Paper Haven green color.');

// 2. Test FavoriteRepository
$favRepo = new FavoriteRepository($db);
$readerId = 103; // Reader Phạm Nhật Minh
$bookId = 1;

// Ensure clean start
$db->exec("DELETE FROM book_favorites WHERE reader_id = {$readerId} AND book_id = {$bookId}");

$added = $favRepo->toggle($readerId, $bookId);
$assert($added === true, 'Favorite toggled ON.');
$assert($favRepo->isFavorite($readerId, $bookId) === true, 'isFavorite returns true.');
$list = $favRepo->listForReader($readerId);
$assert(count($list) >= 1 && (int)$list[0]['id'] === $bookId, 'Book is in reader favorite list.');

$removed = $favRepo->toggle($readerId, $bookId);
$assert($removed === false, 'Favorite toggled OFF.');
$assert($favRepo->isFavorite($readerId, $bookId) === false, 'isFavorite returns false.');

// 3. Test ReviewRepository
$revRepo = new ReviewRepository($db);
$db->exec("DELETE FROM book_reviews WHERE reader_id = {$readerId} AND book_id = {$bookId}");

$revRepo->addOrUpdate($bookId, $readerId, 5, 'Tuyệt phẩm kinh điển vượt thời gian!');
$summary = $revRepo->summaryForBook($bookId);
$assert($summary['total'] >= 1, 'Review count incremented.');
$assert($summary['average'] >= 4.0, 'Average rating is calculated.');

$reviews = $revRepo->forBook($bookId);
$assert(count($reviews) >= 1 && $reviews[0]['full_name'] !== '', 'Review author name joined.');

// Update review
$revRepo->addOrUpdate($bookId, $readerId, 4, 'Đọc lại lần 2 vẫn thấy hay.');
$updated = $revRepo->userReview($bookId, $readerId);
$assert((int)$updated['rating'] === 4, 'Review rating updated in-place without duplicate row.');

// Clean review
$db->exec("DELETE FROM book_reviews WHERE reader_id = {$readerId} AND book_id = {$bookId}");

// 4. Test InventoryAuditRepository
$auditRepo = new InventoryAuditRepository($db);
$audit = $auditRepo->createAudit('Kiểm kê tự động kiểm thử QA', 'all', 1, 'Kiểm thử Phase 4');
$auditId = (int) $audit['id'];
$assert($auditId > 0, 'Inventory audit created with ID.');
$assert(str_starts_with($audit['audit_code'], 'KK'), 'Audit code has correct prefix KK.');

// Scan a copy
$scanResult = $auditRepo->scanCopy($auditId, 'B001-0001', 1, 'good', 'Kệ A-01');
$assert($scanResult['copy']['copy_code'] === 'B001-0001', 'Copy identified by code.');

$items = $auditRepo->auditItems($auditId);
$assert(count($items) === 1, 'Audit items count is 1.');

$auditRefreshed = $auditRepo->findAudit($auditId);
$assert((int)$auditRefreshed['total_scanned'] === 1, 'Total scanned incremented to 1.');

// Complete audit
$completed = $auditRepo->completeAudit($auditId, 1, 'Đã hoàn tất');
$assert($completed['status'] === 'completed', 'Audit status changed to completed.');

// Clean audit
$db->exec("DELETE FROM inventory_audit_items WHERE audit_id = {$auditId}");
$db->exec("DELETE FROM inventory_audits WHERE id = {$auditId}");

echo "PASS: Phase 3 & Phase 4 feature suite ({$checks} assertions passed).\n";
