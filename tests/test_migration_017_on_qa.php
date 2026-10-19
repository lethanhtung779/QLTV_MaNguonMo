<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap/app.php';

$backupFile = dirname(__DIR__) . '/storage/backups/paper_page_library_backup_20261018_before_017.sql';
if (!is_file($backupFile)) {
    throw new RuntimeException("Backup file not found: {$backupFile}");
}

// Generate unique isolated QA test database name
$timestamp = date('Ymd_His');
$qaDbName = "paper_page_test_qa_{$timestamp}";
file_put_contents(dirname(__DIR__) . '/storage/test_database_name.txt', $qaDbName);

echo "=======================================================================\n";
echo "   PRE-FLIGHT AUDIT: TESTING MIGRATION 017 ON ISOLATED QA DATABASE     \n";
echo "=======================================================================\n";
echo "QA Database: {$qaDbName}\n";
echo "Source Backup: {$backupFile} (" . filesize($backupFile) . " bytes)\n";
echo "Backup SHA-256: " . strtoupper(hash_file('sha256', $backupFile)) . "\n\n";

$rootPdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 1. Provision fresh QA database from backup SQL
echo "1. PROVISIONING ISOLATED QA DATABASE...\n";
$rootPdo->exec("DROP DATABASE IF EXISTS `{$qaDbName}`");
$rootPdo->exec("CREATE DATABASE `{$qaDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$mysqlExe = 'D:\\1.Download\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe';
$cmd = sprintf('"%s" -u root %s < "%s"', $mysqlExe, $qaDbName, $backupFile);
exec($cmd, $output, $returnVar);
if ($returnVar !== 0) {
    throw new RuntimeException("Failed to restore backup into {$qaDbName}");
}
echo "   -> Backup restored into {$qaDbName} cleanly (0 errors)!\n\n";

// Connect to QA database
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname={$qaDbName};charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// 2. Preflight checks on QA
$initialBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$initialCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies')->fetchColumn();
$b201Initial = $pdo->query('SELECT id, book_code, title, publisher, isbn, cover_url FROM books WHERE book_code = "B201"')->fetch();

echo "2. PREFLIGHT STATUS IN QA:\n";
echo "   - Initial books count: {$initialBooks} (Expected: 129)\n";
echo "   - Initial copies count: {$initialCopies} (Expected: 265)\n";
echo "   - B201 initial cover: " . ($b201Initial['cover_url'] ?: 'EMPTY') . "\n";
echo "   - B201 initial ISBN: " . ($b201Initial['isbn'] ?: 'NULL') . "\n\n";

if ($initialBooks !== 129 || $initialCopies !== 265) {
    throw new RuntimeException("Preflight failed: Expected 129 books and 265 copies.");
}

// 3. Execute Migration 017 on QA
echo "3. EXECUTING MIGRATION 017 ON QA...\n";
$migration = require dirname(__DIR__) . '/database/migrations/017_import_verified_catalog_books.php';
$migration($pdo);

// Record in QA schema_migrations
$pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, NOW())')
    ->execute(['017_import_verified_catalog_books']);
echo "   -> Migration 017 executed successfully on QA!\n\n";

// 4. Post-migration verification
echo "4. POST-MIGRATION VERIFICATION:\n";
$finalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$finalCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies')->fetchColumn();
$newBooksCount = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE book_code LIKE "AUTH-%"')->fetchColumn();
$newBooksWithZeroCopies = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE book_code LIKE "AUTH-%" AND total_copies = 0')->fetchColumn();
$newBooksPhysicalCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies cp JOIN books b ON b.id = cp.book_id WHERE b.book_code LIKE "AUTH-%"')->fetchColumn();

echo "   - Final books count: {$finalBooks} (Expected: 180 = 129 + 51)\n";
echo "   - New AUTH books count: {$newBooksCount} (Expected: 51)\n";
echo "   - New AUTH books with total_copies = 0: {$newBooksWithZeroCopies} (Expected: 51)\n";
echo "   - Physical copies in book_copies for new books: {$newBooksPhysicalCopies} (Expected: 0 - STRICT RULE 8)\n";
echo "   - Total copies in library: {$finalCopies} (Expected: 265 - UNCHANGED)\n\n";

if ($finalBooks !== 180 || $newBooksCount !== 51 || $newBooksWithZeroCopies !== 51 || $newBooksPhysicalCopies !== 0 || $finalCopies !== 265) {
    throw new RuntimeException("Integrity check failed: Mismatch in book counts or physical copies created!");
}

// 5. Strict ISBN and Barcode Integrity Audit
echo "5. STRICT ISBN & BARCODE AUDIT:\n";
$barcodeAsIsbnCount = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE isbn LIKE '893%'")->fetchColumn();
echo "   - Books with '893' barcode in isbn field: {$barcodeAsIsbnCount} (MUST BE 0)\n";
if ($barcodeAsIsbnCount > 0) {
    throw new RuntimeException("VIOLATION: Barcodes starting with 893 were found in books.isbn column!");
}

$newBooksWithIsbn = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE book_code LIKE 'AUTH-%' AND isbn IS NOT NULL AND isbn != ''")->fetchColumn();
$newBooksNullIsbn = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE book_code LIKE 'AUTH-%' AND (isbn IS NULL OR isbn = '')")->fetchColumn();
echo "   - New books with VERIFIED 978... ISBN-13: {$newBooksWithIsbn} (Expected: 37)\n";
echo "   - New books with PARTIAL / NULL ISBN: {$newBooksNullIsbn} (Expected: 14)\n\n";

if ($newBooksWithIsbn !== 37 || $newBooksNullIsbn !== 14) {
    throw new RuntimeException("ISBN breakdown mismatch: Expected 37 VERIFIED and 14 PARTIAL (NULL).");
}

// 6. Verify B201 Update
echo "6. VERIFY B201 UPDATE:\n";
$b201Updated = $pdo->query('SELECT id, book_code, title, publisher, isbn, cover_url FROM books WHERE book_code = "B201"')->fetch();
echo "   - Book code: {$b201Updated['book_code']}\n";
echo "   - Title: {$b201Updated['title']}\n";
echo "   - Publisher: {$b201Updated['publisher']}\n";
echo "   - ISBN: {$b201Updated['isbn']}\n";
echo "   - Cover URL: {$b201Updated['cover_url']}\n";

if ($b201Updated['isbn'] !== '9786049503368') {
    throw new RuntimeException("B201 ISBN is not 9786049503368!");
}
if ($b201Updated['cover_url'] !== '/assets/book-covers/b201-giao-trinh-c-plus-plus.webp') {
    throw new RuntimeException("B201 cover URL mismatch!");
}

$localB201Webp = dirname(__DIR__) . '/public' . $b201Updated['cover_url'];
$localB201Jpg = dirname(__DIR__) . '/public' . str_replace('.webp', '.jpg', $b201Updated['cover_url']);
if (!is_file($localB201Webp) || !is_file($localB201Jpg)) {
    throw new RuntimeException("B201 image files missing on disk!");
}
echo "   -> PASS: B201 verified with official ISBN-13 and local images!\n\n";

// 7. Verify Local Cover Files for all 51 new books
echo "7. VERIFY LOCAL COVER ASSETS FOR ALL 51 BOOKS:\n";
$missingFiles = 0;
$authBooks = $pdo->query('SELECT book_code, title, cover_url FROM books WHERE book_code LIKE "AUTH-%" ORDER BY book_code')->fetchAll();
foreach ($authBooks as $ab) {
    $webpPath = dirname(__DIR__) . '/public' . $ab['cover_url'];
    $jpgPath = dirname(__DIR__) . '/public' . str_replace('.webp', '.jpg', $ab['cover_url']);
    if (!is_file($webpPath) || !is_file($jpgPath)) {
        echo "   [FAIL] Missing image files for {$ab['book_code']}\n";
        $missingFiles++;
    }
}
if ($missingFiles === 0) {
    echo "   -> 100% PASS: 51/51 new books have valid WebP and JPG fallback files on disk!\n\n";
} else {
    throw new RuntimeException("{$missingFiles} cover files are missing on disk!");
}

// 8. Test Borrow/Circulation Logic (RULE 8)
echo "8. TEST BORROW/CIRCULATION SAFETY (RULE 8):\n";
$auth001Id = (int) $pdo->query('SELECT id FROM books WHERE book_code = "AUTH-001"')->fetchColumn();
$availCopies = (int) $pdo->query("SELECT COUNT(*) FROM book_copies WHERE book_id = {$auth001Id} AND status = 'available'")->fetchColumn();
echo "   - Available copies for AUTH-001: {$availCopies}\n";
if ($availCopies === 0) {
    echo "   -> PASS: AUTH-001 correctly has 0 available copies. Any checkout attempt is strictly blocked.\n\n";
} else {
    throw new RuntimeException("AUTH-001 should have 0 available copies!");
}

// 9. Category breakdown
echo "9. CATEGORY BREAKDOWN OF 51 NEW BOOKS:\n";
$catStmt = $pdo->query('
    SELECT c.id, c.name, COUNT(*) as cnt 
    FROM books b 
    JOIN categories c ON c.id = b.category_id 
    WHERE b.book_code LIKE "AUTH-%" 
    GROUP BY c.id, c.name 
    ORDER BY cnt DESC
');
foreach ($catStmt->fetchAll() as $row) {
    echo "   - [ID {$row['id']}] {$row['name']}: {$row['cnt']} books\n";
}

echo "\n=======================================================================\n";
echo "   100% PASS: ALL PRE-FLIGHT QA CHECKS PASSED WITH ZERO ERRORS!        \n";
echo "   PRODUCTION DATABASE (paper_page_library) REMAINS UNTOUCHED.         \n";
echo "=======================================================================\n";
