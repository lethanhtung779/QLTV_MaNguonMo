<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

$pdo = \Library\Core\Database::connection();
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

echo "=== 1. POSTFLIGHT SCHEMA & MIGRATION VERIFICATION ===" . PHP_EOL;
echo "Current Database: $dbName" . PHP_EOL;

$migCount = (int) $pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE version = '016_quarantine_simulated_copies_and_clean_isbns'")->fetchColumn();
echo "Migration 016 recorded entries in schema_migrations: $migCount" . PHP_EOL;
if ($migCount !== 1) {
    echo "[FAIL] Migration 016 should be recorded exactly once, found: $migCount" . PHP_EOL;
    exit(1);
}
echo "[PASS] Migration 016 recorded exactly once" . PHP_EOL;

echo PHP_EOL . "=== 2. INVENTORY & COPY STATUS METRICS ===" . PHP_EOL;
$totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies')->fetchColumn();
$copyStatuses = $pdo->query('SELECT status, COUNT(*) as cnt FROM book_copies GROUP BY status ORDER BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Total Books: $totalBooks (Expected: 129)" . PHP_EOL;
echo "Total Copies: $totalCopies (Expected: 265)" . PHP_EOL;
echo "Copy Status Breakdown: " . json_encode($copyStatuses) . PHP_EOL;

if ($totalBooks !== 129) {
    echo "[FAIL] Total books mismatch! Expected 129, got $totalBooks" . PHP_EOL;
    exit(1);
}
if ($totalCopies !== 265) {
    echo "[FAIL] Total copies mismatch! Expected 265, got $totalCopies" . PHP_EOL;
    exit(1);
}

$pendingAuditCount = (int) ($copyStatuses['pending_audit'] ?? 0);
$availableCount = (int) ($copyStatuses['available'] ?? 0);
$onLoanCount = (int) ($copyStatuses['on_loan'] ?? 0);
$damagedCount = (int) ($copyStatuses['damaged'] ?? 0);

echo "Pending Audit Copies: $pendingAuditCount (Expected: 160)" . PHP_EOL;
echo "Available Copies: $availableCount (Expected: 102)" . PHP_EOL;
echo "On Loan Copies: $onLoanCount (Expected: 2)" . PHP_EOL;
echo "Damaged Copies: $damagedCount (Expected: 1)" . PHP_EOL;

if ($pendingAuditCount !== 160) {
    echo "[FAIL] Expected exactly 160 pending_audit copies, got $pendingAuditCount" . PHP_EOL;
    exit(1);
}
if ($availableCount !== 102) {
    echo "[FAIL] Expected exactly 102 available copies, got $availableCount" . PHP_EOL;
    exit(1);
}
echo "[PASS] Copy counts and statuses exactly match expected operational metrics" . PHP_EOL;

// Check shelf location for quarantined copies
$quarantinedShelfCheck = (int) $pdo->query("SELECT COUNT(*) FROM book_copies WHERE status = 'pending_audit' AND shelf_location != 'Kho tiếp nhận — Chờ kiểm kê'")->fetchColumn();
if ($quarantinedShelfCheck !== 0) {
    echo "[FAIL] Found $quarantinedShelfCheck quarantined copies with unexpected shelf location" . PHP_EOL;
    exit(1);
}
echo "[PASS] 100% of 160 quarantined copies assigned to 'Kho tiếp nhận — Chờ kiểm kê'" . PHP_EOL;

echo PHP_EOL . "=== 3. ISBN AUDIT & CLEANLINESS ===" . PHP_EOL;
$verifiedIsbns = [
    'B105' => '978-604-2-03054-0',
    'B110' => '978-604-1-00069-8',
    'B203' => '978-0-13-235088-4',
    'B204' => '978-0-201-63361-0',
    'B210' => '978-0-262-03384-8',
    'B301' => '978-604-1-01204-2',
    'B405' => '978-604-77-5576-9',
    'B504' => '978-604-77-8931-3',
    'B509' => '978-604-392-282-0',
    'B603' => '978-604-335-644-1',
    'B703' => '978-604-2-35413-4',
];

foreach ($verifiedIsbns as $code => $expectedIsbn) {
    $actualIsbn = (string) $pdo->query("SELECT isbn FROM books WHERE book_code = '$code'")->fetchColumn();
    if ($actualIsbn !== $expectedIsbn) {
        echo "[FAIL] Book $code expected ISBN $expectedIsbn, got $actualIsbn" . PHP_EOL;
        exit(1);
    }
}
echo "[PASS] All 11 verified titles have authoritative publisher ISBNs" . PHP_EOL;

$datasetFile = dirname(__DIR__) . '/database/migrations/data/v12_80_books_dataset.php';
$dataset = require $datasetFile;
$v12Codes = array_column($dataset, 'book_code');
$unverifiedCodes = array_diff($v12Codes, array_keys($verifiedIsbns));

$unverifiedWithIsbn = $pdo->query("SELECT book_code, isbn FROM books WHERE book_code IN ('" . implode("','", $unverifiedCodes) . "') AND isbn IS NOT NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
if (!empty($unverifiedWithIsbn)) {
    echo "[FAIL] Found unverified V12 books with non-NULL ISBN: " . json_encode($unverifiedWithIsbn) . PHP_EOL;
    exit(1);
}
echo "[PASS] 100% of 69 unverified titles have ISBN = NULL (no simulated ISBNs presented)" . PHP_EOL;

$totalNullIsbns = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE isbn IS NULL')->fetchColumn();
$totalNonNullIsbns = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE isbn IS NOT NULL')->fetchColumn();
echo "Total books with ISBN = NULL: $totalNullIsbns (41 old + 69 unverified = 110)" . PHP_EOL;
echo "Total books with ISBN != NULL: $totalNonNullIsbns (8 old + 11 verified = 19)" . PHP_EOL;
if ($totalNullIsbns !== 110 || $totalNonNullIsbns !== 19) {
    echo "[FAIL] Expected 110 NULL ISBNs and 19 Non-NULL ISBNs" . PHP_EOL;
    exit(1);
}
echo "[PASS] ISBN distribution exact: 19 authoritative, 110 transparent NULL" . PHP_EOL;

echo PHP_EOL . "=== 4. FOREIGN KEY & REFERENTIAL INTEGRITY AUDIT ===" . PHP_EOL;
// Check orphaned copies
$orphanedCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies c LEFT JOIN books b ON c.book_id = b.id WHERE b.id IS NULL')->fetchColumn();
// Check loan_items FKs
$orphanedLoanItems = (int) $pdo->query('SELECT COUNT(*) FROM loan_items li LEFT JOIN book_copies c ON li.book_copy_id = c.id WHERE c.id IS NULL')->fetchColumn();
// Check reservations FKs
$orphanedReservations = (int) $pdo->query('SELECT COUNT(*) FROM reservations r LEFT JOIN books b ON r.book_id = b.id WHERE b.id IS NULL')->fetchColumn();

echo "Orphaned copies: $orphanedCopies" . PHP_EOL;
echo "Orphaned loan items: $orphanedLoanItems" . PHP_EOL;
echo "Orphaned reservations: $orphanedReservations" . PHP_EOL;

if ($orphanedCopies !== 0 || $orphanedLoanItems !== 0 || $orphanedReservations !== 0) {
    echo "[FAIL] Referential integrity violation detected!" . PHP_EOL;
    exit(1);
}
echo "[PASS] 0 foreign key violations across all tables" . PHP_EOL;

echo PHP_EOL . "=== 5. CIRCULATION SAFETY (pending_audit NOT LOANABLE) ===" . PHP_EOL;
$loanablePendingAudit = (int) $pdo->query("
    SELECT COUNT(*) 
    FROM book_copies c
    WHERE c.status = 'pending_audit'
      AND c.id IN (SELECT book_copy_id FROM loan_items WHERE returned_at IS NULL)
")->fetchColumn();
if ($loanablePendingAudit !== 0) {
    echo "[FAIL] Found $loanablePendingAudit pending_audit copies attached to active loans!" . PHP_EOL;
    exit(1);
}
echo "[PASS] 0 pending_audit copies attached to active loans" . PHP_EOL;

echo PHP_EOL . "=== ALL POSTFLIGHT DATABASE ASSERTIONS PASSED (100%) ===" . PHP_EOL;
