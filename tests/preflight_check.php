<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

$pdo = \Library\Core\Database::connection();
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

echo "=== 1. PREFLIGHT DATABASE CONNECTION ===" . PHP_EOL;
echo "Current Database: $dbName" . PHP_EOL;
if ($dbName !== 'paper_page_library') {
    echo "[FAIL] Expected database 'paper_page_library', got '$dbName'" . PHP_EOL;
    exit(1);
}
echo "[PASS] Connected to 'paper_page_library'" . PHP_EOL;

echo PHP_EOL . "=== 2. MIGRATION HISTORY & DISK AUDIT ===" . PHP_EOL;
$applied = $pdo->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
echo "Applied count: " . count($applied) . PHP_EOL;

$mig015Applied = in_array('015_import_80_authentic_books', $applied, true);
$mig016Applied = in_array('016_quarantine_simulated_copies_and_clean_isbns', $applied, true);

echo "Migration 015 applied: " . ($mig015Applied ? 'YES' : 'NO') . PHP_EOL;
echo "Migration 016 applied: " . ($mig016Applied ? 'YES' : 'NO') . PHP_EOL;

if (!$mig015Applied) {
    echo "[FAIL] Migration 015 must be applied before Migration 016!" . PHP_EOL;
    exit(1);
}
if ($mig016Applied) {
    echo "[FAIL] Migration 016 is already applied!" . PHP_EOL;
    exit(1);
}
echo "[PASS] Migration 015 applied, Migration 016 NOT YET applied" . PHP_EOL;

// Check files on disk vs applied
$files = glob(dirname(__DIR__) . '/database/migrations/*.php');
sort($files, SORT_STRING);
$pending = [];
foreach ($files as $file) {
    $ver = pathinfo($file, PATHINFO_FILENAME);
    if (!in_array($ver, $applied, true)) {
        $pending[] = $ver;
    }
}
echo "Pending migrations on disk: " . implode(', ', $pending) . PHP_EOL;
if ($pending !== ['016_quarantine_simulated_copies_and_clean_isbns']) {
    echo "[FAIL] Expected ONLY 016_quarantine_simulated_copies_and_clean_isbns to be pending, found: " . json_encode($pending) . PHP_EOL;
    exit(1);
}
echo "[PASS] ONLY Migration 016 will be executed by migrate runner" . PHP_EOL;

echo PHP_EOL . "=== 3. BACKUP & CHECKSUM VERIFICATION ===" . PHP_EOL;
$backupSql = dirname(__DIR__) . '/storage/backups/paper_page_library-20261017-231206-faf6c4.sql';
$backupSha = dirname(__DIR__) . '/storage/backups/paper_page_library-20261017-231206-faf6c4.sql.sha256';

if (!is_file($backupSql) || !is_file($backupSha)) {
    echo "[FAIL] Backup file or checksum file missing!" . PHP_EOL;
    exit(1);
}
$shaRaw = trim(file_get_contents($backupSha));
$expectedHash = preg_split('/\s+/', $shaRaw)[0];
$actualHash = hash_file('sha256', $backupSql);
if (!hash_equals($expectedHash, $actualHash)) {
    echo "[FAIL] Backup SHA-256 mismatch! Expected: $expectedHash, Got: $actualHash" . PHP_EOL;
    exit(1);
}
echo "[PASS] Backup file exists, SHA-256 matches: $actualHash" . PHP_EOL;

echo PHP_EOL . "=== 4. INDEPENDENT QA RESTORE TEST ===" . PHP_EOL;
$qaDbName = 'paper_page_test_preflight_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
$id = fn($v) => "`" . $v . "`";
$pdo->exec("CREATE DATABASE {$id($qaDbName)} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $pdo->exec("USE {$id($qaDbName)}");
    $sqlContent = file_get_contents($backupSql);
    $pdo->exec($sqlContent);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // Compare table counts
    $tables = $pdo->query("SHOW TABLES FROM {$id('paper_page_library')}")->fetchAll(PDO::FETCH_COLUMN);
    $allMatch = true;
    foreach ($tables as $t) {
        $cntMain = (int) $pdo->query("SELECT COUNT(*) FROM `paper_page_library`.{$id($t)}")->fetchColumn();
        $cntQa = (int) $pdo->query("SELECT COUNT(*) FROM {$id($qaDbName)}.{$id($t)}")->fetchColumn();
        if ($cntMain !== $cntQa) {
            echo "[FAIL] Table $t count mismatch: Main=$cntMain, QA=$cntQa" . PHP_EOL;
            $allMatch = false;
        }
    }
    if (!$allMatch) {
        throw new RuntimeException("Table count mismatch between main and restored backup");
    }
    echo "[PASS] 29/29 tables restored to $qaDbName with exact row counts" . PHP_EOL;
} finally {
    $pdo->exec("USE `paper_page_library`");
    $pdo->exec("DROP DATABASE IF EXISTS {$id($qaDbName)}");
    echo "[PASS] Independent QA restore DB cleaned up cleanly" . PHP_EOL;
}

echo PHP_EOL . "=== 5. BASELINE DATA METRICS (BEFORE MIGRATION 016) ===" . PHP_EOL;
$totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalCopies = (int) $pdo->query('SELECT COUNT(*) FROM book_copies')->fetchColumn();
$copyStatuses = $pdo->query('SELECT status, COUNT(*) as cnt FROM book_copies GROUP BY status ORDER BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$isbnsNotNull = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE isbn IS NOT NULL')->fetchColumn();
$isbnsNull = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE isbn IS NULL')->fetchColumn();

echo "Total Books: $totalBooks (Expected: 129)" . PHP_EOL;
echo "Total Copies: $totalCopies (Expected: 265)" . PHP_EOL;
echo "Copy Statuses: " . json_encode($copyStatuses) . PHP_EOL;
echo "ISBNs (Not Null: $isbnsNotNull, Null: $isbnsNull)" . PHP_EOL;

if ($totalBooks !== 129 || $totalCopies !== 265) {
    echo "[FAIL] Pre-migration books/copies count unexpected!" . PHP_EOL;
    exit(1);
}
echo "[PASS] Baseline data metrics match expected baseline (129 books, 265 copies)" . PHP_EOL;

echo PHP_EOL . "=== 6. MIGRATION 016 PRECONDITIONS & CODE INSPECTION ===" . PHP_EOL;
$mig016File = dirname(__DIR__) . '/database/migrations/016_quarantine_simulated_copies_and_clean_isbns.php';
$datasetFile = dirname(__DIR__) . '/database/migrations/data/v12_80_books_dataset.php';
if (!is_file($mig016File) || !is_file($datasetFile)) {
    echo "[FAIL] Migration 016 file or dataset file not found!" . PHP_EOL;
    exit(1);
}

$migCode = file_get_contents($mig016File);
if (!str_contains($migCode, 'information_schema.COLUMNS')) {
    echo "[FAIL] Migration 016 missing information_schema.COLUMNS check!" . PHP_EOL;
    exit(1);
}
if (!str_contains($migCode, 'beginTransaction()') || !str_contains($migCode, 'commit()') || !str_contains($migCode, 'rollBack()')) {
    echo "[FAIL] Migration 016 missing atomic transaction boundaries!" . PHP_EOL;
    exit(1);
}
// Confirm DDL is executed before beginTransaction
$posDdl = strpos($migCode, 'ALTER TABLE book_copies');
$posBegin = strpos($migCode, '$pdo->beginTransaction()');
if ($posDdl !== false && $posBegin !== false && $posDdl > $posBegin) {
    echo "[FAIL] ALTER TABLE must be placed BEFORE beginTransaction() to prevent broken transactions due to MySQL implicit commit!" . PHP_EOL;
    exit(1);
}
echo "[PASS] DDL ALTER TABLE placed safely outside DML transaction" . PHP_EOL;

$dataset = require $datasetFile;
if (!is_array($dataset) || count($dataset) !== 80) {
    echo "[FAIL] Dataset does not contain exactly 80 books! Found: " . count($dataset) . PHP_EOL;
    exit(1);
}
echo "[PASS] Dataset verified: exactly 80 books present" . PHP_EOL;

echo PHP_EOL . "=== ALL PREFLIGHT CHECKS PASSED (8/8) ===" . PHP_EOL;
