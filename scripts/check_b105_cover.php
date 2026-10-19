<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'paper_page_library';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->query('SELECT cover_url FROM books WHERE book_code = "B105"');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'not found']);
}
?>
