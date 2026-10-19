<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;
use Library\Services\IsbnValidator;

$db = Database::connection();
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

$assert(IsbnValidator::isValid('978-0-13-235088-4'), 'Valid ISBN-13 rejected');
$assert(IsbnValidator::isValid('0-306-40615-2'), 'Valid ISBN-10 rejected');
$assert(!IsbnValidator::isValid('978-0-13-235088-5'), 'Invalid ISBN-13 accepted');
$assert(!IsbnValidator::isValid('1234567890'), 'Invalid ISBN-10 accepted');

$rows = $db->query('SELECT id, isbn FROM books')->fetchAll(PDO::FETCH_ASSOC);
$valid = $invalid = $missing = 0;
$normalized = [];
foreach ($rows as $row) {
    $isbn = IsbnValidator::normalize($row['isbn']);
    if ($isbn === '') { $missing++; continue; }
    if (IsbnValidator::isValid($isbn)) $valid++; else $invalid++;
    $normalized[$isbn][] = (int) $row['id'];
}
$duplicateGroups = count(array_filter($normalized, static fn(array $ids): bool => count($ids) > 1));
$assert(count($rows) === $valid + $invalid + $missing, 'ISBN classification does not cover all books');

$notApplicableCodes = [];
foreach (['real_catalog_manifest.json', 'category_catalog_manifest.json'] as $filename) {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__) . '/database/' . $filename), true);
    foreach ($manifest as $item) {
        $source = (string) ($item['source_url'] ?? '');
        $publisher = strtolower((string) ($item['publisher'] ?? ''));
        if (IsbnValidator::normalize($item['isbn'] ?? null) === '' && filter_var($source, FILTER_VALIDATE_URL)
            && (!empty($item['license']) || str_contains($publisher, 'standard ebooks'))) {
            $notApplicableCodes[(string) $item['book_code']] = true;
        }
    }
}
$placeholders = implode(',', array_fill(0, count($notApplicableCodes), '?'));
$notApplicableStatement = $db->prepare("SELECT COUNT(*) FROM books WHERE book_code IN ({$placeholders}) AND (isbn IS NULL OR TRIM(isbn) = '')");
$notApplicableStatement->execute(array_keys($notApplicableCodes));
$notApplicable = (int) $notApplicableStatement->fetchColumn();
$needsVerification = $missing - $notApplicable;
$assert($notApplicable > 0 && $needsVerification >= 0, 'Not-applicable ISBN classification failed');

$copyRows = $db->query('SELECT status, COUNT(*) count FROM book_copies GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$copyTotal = (int) $db->query('SELECT COUNT(*) FROM book_copies')->fetchColumn();
$assert(array_sum(array_map('intval', $copyRows)) === $copyTotal, 'Copy statuses do not reconcile with total');

$admin = $db->query("SELECT * FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$assert((bool) $admin, 'No active admin available for read-only render test');
$_SESSION['user'] = [
    'id' => (int) $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'],
    'role' => 'admin', 'credential_version' => hash('sha256', $admin['password_hash']),
];
$_GET = ['page' => '1'];
ob_start();
(new Library\Controllers\MetadataQualityController())->index();
$html = (string) ob_get_clean();
$assert(str_contains($html, 'Kiểm toán Chất lượng Dữ liệu Sách'), 'Audit page did not render');
$assert(str_contains($html, 'Trang 1/'), 'Pagination summary missing');
$assert(substr_count($html, '<tbody>') === 1 && substr_count($html, '<tr>') <= 26, 'Page renders more than 25 data rows');
$assert(str_contains($html, 'Không áp dụng ISBN'), 'ISBN not-applicable classification missing');
$assert(!str_contains($html, '100% sách công khai'), 'Hardcoded public percentage remains');
$assert(preg_match('/aria-valuenow="([0-9]+)"/', $html, $scoreMatch) === 1, 'Computed quality score missing');

$totalBooks = count($rows);
$missingCover = (int) $db->query("SELECT COUNT(*) FROM books WHERE cover_url IS NULL OR TRIM(cover_url) = ''")->fetchColumn();
$missingDescription = (int) $db->query("SELECT COUNT(*) FROM books WHERE description IS NULL OR CHAR_LENGTH(TRIM(description)) < 40")->fetchColumn();
$missingPublisher = (int) $db->query("SELECT COUNT(*) FROM books WHERE publisher IS NULL OR TRIM(publisher) = ''")->fetchColumn();
$zeroCopies = (int) $db->query('SELECT COUNT(*) FROM books b WHERE NOT EXISTS (SELECT 1 FROM book_copies bc WHERE bc.book_id = b.id)')->fetchColumn();
$legacyScore = (int) round(
    (($valid + $invalid) / $totalBooks) * 25
    + (($totalBooks - $missingCover) / $totalBooks) * 20
    + (($totalBooks - $missingDescription) / $totalBooks) * 25
    + (($totalBooks - $missingPublisher) / $totalBooks) * 15
    + (($totalBooks - $zeroCopies) / $totalBooks) * 15
);

echo json_encode([
    'status' => 'PASS', 'checks' => $checks, 'books' => count($rows),
    'isbn_valid_checksum' => $valid, 'isbn_invalid_checksum' => $invalid,
    'isbn_blank' => $missing, 'isbn_not_applicable' => $notApplicable,
    'isbn_needs_verification' => $needsVerification,
    'duplicate_groups_normalized' => $duplicateGroups,
    'public_books' => (int) $db->query('SELECT COUNT(*) FROM books WHERE is_public = 1')->fetchColumn(),
    'legacy_score' => $legacyScore, 'corrected_score' => (int) $scoreMatch[1],
    'copy_statuses' => $copyRows, 'copy_total' => $copyTotal,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
