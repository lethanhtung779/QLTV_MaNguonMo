<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'paper_page_library';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage();
    exit(1);
}
$code = 'B703';
$wrongUrl = '/assets/book-covers/b703-hoang-tu-be.jpg';
// 1. Read current record
$stmt = $pdo->prepare('SELECT * FROM books WHERE book_code = ?');
$stmt->execute([$code]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current) {
    echo "No record found for $code\n";
    exit(1);
}
// 2. Backup current record
$backupPath = __DIR__.'/../storage/backups/'.$code.'_pre_update_backup.json';
file_put_contents($backupPath, json_encode($current, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "Current record backed up to $backupPath\n";
// 3. Verify conditions
if ($current['cover_url'] !== $wrongUrl) {
    echo "Cover URL is not the wrong one, aborting.\n";
    exit(1);
}
// 4. Transactional update to NULL
$pdo->beginTransaction();
$upd = $pdo->prepare('UPDATE books SET cover_url = NULL WHERE book_code = ? AND cover_url = ?');
$upd->execute([$code, $wrongUrl]);
$affected = $upd->rowCount();
if ($affected === 1) {
    $pdo->commit();
    echo "Transaction committed, $affected row updated.\n";
} else {
    $pdo->rollBack();
    echo "Transaction rolled back, affected rows $affected (expected 1).\n";
    exit(1);
}
// 5. Fetch updated record for report
$stmt = $pdo->prepare('SELECT * FROM books WHERE book_code = ?');
$stmt->execute([$code]);
$updated = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($updated);
?>
