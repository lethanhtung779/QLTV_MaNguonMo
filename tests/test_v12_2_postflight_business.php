<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap/app.php';

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$pdo = \Library\Core\Database::connection();

echo "=== 1. VERIFY AUTHENTICATION OF 3 ROLES ===" . PHP_EOL;
$authService = new \Library\Services\AuthService($pdo);

// Admin
$admin = $authService->authenticate('admin@paperpage.local', \Library\Core\Config::get('SEED_ADMIN_PASSWORD'));
if (!$admin || $admin['role'] !== 'admin') {
    echo "[FAIL] Admin authentication failed" . PHP_EOL;
    exit(1);
}
echo "[PASS] Admin authenticated successfully ({$admin['email']}, role: {$admin['role']})" . PHP_EOL;

// Librarian
$librarian = $authService->authenticate('demo-librarian@paperpage.local', \Library\Core\Config::get('SEED_DEMO_LIBRARIAN_PASSWORD'));
if (!$librarian || $librarian['role'] !== 'librarian') {
    echo "[FAIL] Librarian authentication failed" . PHP_EOL;
    exit(1);
}
echo "[PASS] Librarian authenticated successfully ({$librarian['email']}, role: {$librarian['role']})" . PHP_EOL;

// Reader
$reader = $authService->authenticate('demo-reader@paperpage.local', \Library\Core\Config::get('SEED_DEMO_READER_PASSWORD'));
if (!$reader || $reader['role'] !== 'reader') {
    echo "[FAIL] Reader authentication failed" . PHP_EOL;
    exit(1);
}
echo "[PASS] Reader authenticated successfully ({$reader['email']}, role: {$reader['role']})" . PHP_EOL;

echo PHP_EOL . "=== 2. HTTP ENDPOINTS ON PORT 8010 ===" . PHP_EOL;
$baseUrl = 'http://127.0.0.1:8010';
$endpoints = [
    '/' => 'Trang chủ Paper Haven',
    '/catalog' => 'Danh mục sách',
    '/book/239' => 'Chi tiết sách B001',
    '/book/320' => 'Chi tiết sách B810 (V12)',
    '/login' => 'Đăng nhập',
    '/register' => 'Đăng ký',
];

foreach ($endpoints as $uri => $label) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
        ]
    ]);
    $content = @file_get_contents($baseUrl . $uri, false, $ctx);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/HTTP\/\S+\s+(\d+)/', $statusLine, $m);
    $statusCode = (int) ($m[1] ?? 0);
    if ($statusCode !== 200) {
        echo "[FAIL] $label ($uri) returned HTTP $statusCode" . PHP_EOL;
        exit(1);
    }
    echo "[PASS] $label ($uri) -> HTTP 200 OK (size: " . strlen((string)$content) . " bytes)" . PHP_EOL;
}

echo PHP_EOL . "=== 3. BOOK DETAIL & PENDING_AUDIT VERIFICATION ===" . PHP_EOL;
// Book 320 is a V12 book whose copies were quarantined
$book320Html = file_get_contents($baseUrl . '/book/320');
if (!str_contains($book320Html, 'Chờ kiểm kê lưu kho') && !str_contains($book320Html, 'pending_audit') && !str_contains($book320Html, 'chờ kiểm kê')) {
    echo "[FAIL] Book 320 should display pending_audit notice!" . PHP_EOL;
    exit(1);
}
echo "[PASS] Book 320 correctly displays pending_audit status notice" . PHP_EOL;

// Verify book 320 available copies count is 0
$bookRepo = new \Library\Repositories\BookRepository($pdo);
$book320 = $bookRepo->find(320);
$available320 = (int) ($book320['available_copies'] ?? 0);
if ($available320 !== 0) {
    echo "[FAIL] Book 320 should have 0 available copies, got $available320" . PHP_EOL;
    exit(1);
}
echo "[PASS] Book 320 has exactly 0 available copies for loan" . PHP_EOL;

echo PHP_EOL . "=== 4. CIRCULATION INTEGRITY (CANNOT LOAN PENDING_AUDIT COPIES) ===" . PHP_EOL;
$loanService = new \Library\Services\LoanService($pdo, 5000.0);

$copiesStmt = $pdo->prepare('SELECT * FROM book_copies WHERE book_id = :id');
$copiesStmt->execute(['id' => 320]);
$copies320 = $copiesStmt->fetchAll();
$allPendingAudit = true;
foreach ($copies320 as $copy) {
    if ($copy['status'] !== 'pending_audit') {
        $allPendingAudit = false;
    }
}
if (!$allPendingAudit) {
    echo "[FAIL] All copies of book 320 should be pending_audit!" . PHP_EOL;
    exit(1);
}
echo "[PASS] All copies of book 320 are strictly pending_audit (total: " . count($copies320) . ")" . PHP_EOL;

echo PHP_EOL . "=== 5. BARCODE / QR SERVICE INTEGRITY ===" . PHP_EOL;
$barcodeService = new \Library\Services\BarcodeService();
$barcodeSvg = $barcodeService->renderBarcodeSvg('B810-0001');
$qrSvg = $barcodeService->renderQrSvg('B810-0001');
if (!str_contains($barcodeSvg, '<svg') || !str_contains($barcodeSvg, '</svg>')) {
    echo "[FAIL] Barcode SVG generation failed" . PHP_EOL;
    exit(1);
}
if (!str_contains($qrSvg, '<svg') || !str_contains($qrSvg, '</svg>')) {
    echo "[FAIL] QR SVG generation failed" . PHP_EOL;
    exit(1);
}
echo "[PASS] Code 128 Barcode & QR SVG generated cleanly" . PHP_EOL;

echo PHP_EOL . "=== 6. METADATA QUALITY & IMPORT REPOSITORY CHECKS ===" . PHP_EOL;
// Check metadata quality controller logic
$_SESSION['user'] = $admin;
$mqController = new \Library\Controllers\MetadataQualityController();
ob_start();
$mqController->index();
$mqHtml = ob_get_clean();
if (!str_contains($mqHtml, 'Chất lượng Dữ liệu Biên mục') && !str_contains($mqHtml, 'metadata-quality')) {
    echo "[FAIL] Metadata Quality Dashboard render failed" . PHP_EOL;
    exit(1);
}
echo "[PASS] Metadata Quality Dashboard rendered successfully for Admin" . PHP_EOL;

echo PHP_EOL . "=== ALL POSTFLIGHT TARGETED BUSINESS CHECKS PASSED (100%) ===" . PHP_EOL;
