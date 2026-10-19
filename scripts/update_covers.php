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
$updates = [
    'B105' => 'b105-de-men-phieu-luu-ky.jpg',
    'B703' => 'b703-hoang-tu-be.jpg',
];
foreach ($updates as $code => $filename) {
    // Backup current row
    $stmt = $pdo->prepare('SELECT * FROM books WHERE book_code = ?');
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $backupPath = __DIR__ . '/../storage/backups/' . $code . '_backup.json';
        file_put_contents($backupPath, json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Backed up $code to $backupPath\n";
    } else {
        echo "No record found for $code, skipping backup.\n";
        continue;
    }
    // Update cover_url in a transaction
    $pdo->beginTransaction();
    $newUrl = '/assets/book-covers/' . $filename;
    $upd = $pdo->prepare('UPDATE books SET cover_url = ? WHERE book_code = ?');
    $upd->execute([$newUrl, $code]);
    $pdo->commit();
    echo "Updated $code cover_url to $newUrl\n";
}
?>
