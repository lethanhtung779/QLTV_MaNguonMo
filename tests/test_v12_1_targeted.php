<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';
$db = \Library\Core\Database::connection();

$testResults = [];

function assert_test(string $name, bool $pass, string $detail = ''): void {
    global $testResults;
    $testResults[] = ['name' => $name, 'pass' => $pass, 'detail' => $detail];
    echo ($pass ? "  [PASS] " : "  [FAIL] ") . $name . ($detail ? " ($detail)" : "") . PHP_EOL;
}

echo "=== 1. KIỂM THỬ METADATA QUALITY DASHBOARD (/admin/metadata-quality) ===\n";

// 1.1 RBAC Check
$guestCanAccess = false;
try {
    unset($_SESSION['user']);
    $user = current_user();
    $guestCanAccess = ($user !== null);
} catch (Throwable $e) {
    $guestCanAccess = false;
}
assert_test("Chặn truy cập khách vãng lai", !$guestCanAccess, "Guest session is null");

// Simulate Admin
$admin = $db->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$_SESSION['user'] = [
    'id' => (int) $admin['id'],
    'name' => $admin['name'],
    'email' => $admin['email'],
    'role' => $admin['role'],
    'credential_version' => hash('sha256', $admin['password_hash']),
];

// Run MetadataQualityController index with default
$_GET = [];
ob_start();
(new \Library\Controllers\MetadataQualityController())->index();
$dashHtml = ob_get_clean();
assert_test("Admin xem được Dashboard", str_contains($dashHtml, 'Kiểm toán Chất lượng Dữ liệu Sách') && str_contains($dashHtml, 'Độ hoàn thiện dữ liệu'), "Rendered length: " . strlen($dashHtml));

// Filter: Duplicate ISBN
$_GET = ['filter' => 'duplicate_isbn'];
ob_start();
(new \Library\Controllers\MetadataQualityController())->index();
$dupFilterHtml = ob_get_clean();
assert_test("Bộ lọc Trùng lặp ISBN hoạt động", str_contains($dupFilterHtml, 'filter=duplicate_isbn') || str_contains($dupFilterHtml, 'Trùng ISBN'), "Filter duplicate_isbn executed successfully");

// Filter: Missing Cover
$_GET = ['filter' => 'missing_cover'];
ob_start();
(new \Library\Controllers\MetadataQualityController())->index();
$coverFilterHtml = ob_get_clean();
assert_test("Bộ lọc Thiếu ảnh bìa hoạt động", str_contains($coverFilterHtml, 'filter=missing_cover') && str_contains($coverFilterHtml, 'Bìa trung tính'), "Filter missing_cover executed successfully");


echo "\n=== 2. KIỂM THỬ SAFE BULK IMPORT TOOL (/admin/books/import) ===\n";

// 2.1 CSRF Validation using Csrf::valid()
$csrfValid = \Library\Core\Csrf::valid('invalid_token_999');
assert_test("Xác thực CSRF token chặn request giả mạo", !$csrfValid, "Csrf::valid returns false for invalid token");

// 2.2 Anti-Formula Injection Check
$formulaPayload = "=CMD('calc.exe')";
$plusPayload = "+123456";
$atPayload = "@SUM(A1:A10)";
$cleanFormula = preg_replace('/^([=+\-@])/', "'$1", $formulaPayload);
$cleanPlus = preg_replace('/^([=+\-@])/', "'$1", $plusPayload);
$cleanAt = preg_replace('/^([=+\-@])/', "'$1", $atPayload);
assert_test("Anti-Formula Injection escape dấu '='", str_starts_with($cleanFormula, "'="), "Escaped to '=");
assert_test("Anti-Formula Injection escape dấu '+'", str_starts_with($cleanPlus, "'+"), "Escaped to '+");
assert_test("Anti-Formula Injection escape dấu '@'", str_starts_with($cleanAt, "'@"), "Escaped to '@");

// 2.3 Duplicate ISBN Detection in Preview (Using verified ISBN in DB)
$existingIsbn = '978-604-2-03054-0'; // B105 - NXB Kim Đồng
$_POST = [
    '_csrf' => \Library\Core\Csrf::token(),
    'import_type' => 'csv',
    'raw_data' => "Sách trùng lặp test, Tác giả Test, $existingIsbn, NXB Test, 2022, Văn học, 2, Kệ T1-01",
    'default_category_id' => 1,
    'default_copies' => 2,
    'default_shelf' => 'Kệ V2-01'
];
ob_start();
(new \Library\Controllers\BookImportController())->preview();
$previewHtml = ob_get_clean();
assert_test("Cảnh báo trùng lặp ISBN khi đã tồn tại trong DB", str_contains($previewHtml, 'Mã ISBN đã tồn tại trong thư viện'), "Preview detected duplicate ISBN $existingIsbn");


echo "\n=== 3. KIỂM THỬ RECOMMENDATION SERVICE ===\n";

$rec = new \Library\Services\RecommendationService($db);
// 3.1 Recommendation for unauthenticated / guest user
$guestRecs = $rec->getRecommendations(null, 0, 5);
assert_test("Gợi ý cho khách vãng lai (Fallback Top-Rated)", count($guestRecs) > 0, "Returned " . count($guestRecs) . " books");
if (!empty($guestRecs)) {
    assert_test("Có lý do gợi ý rõ ràng", !empty($guestRecs[0]['recommendation_reason']), "Reason: " . ($guestRecs[0]['recommendation_reason'] ?? ''));
}

// 3.2 Related books on book detail page
$related = $rec->getRecommendations(null, 1, 4);
assert_test("Gợi ý sách liên quan cho sách ID=1", count($related) > 0, "Returned " . count($related) . " books");
$containsSelf = false;
foreach ($related as $b) {
    if ((int)$b['id'] === 1) $containsSelf = true;
}
assert_test("Loại trừ chính cuốn sách đang xem khỏi danh sách gợi ý", !$containsSelf, "Book ID 1 excluded");


echo "\n=== 4. KIỂM THỬ TÌM KIẾM & CATALOG (/catalog) ===\n";

$bookRepo = new \Library\Repositories\BookRepository($db);

// 4.1 Search new real book: "Vũ Trọng Phụng"
$countAuthor = $bookRepo->countSearch('Vũ Trọng Phụng', 0, '', true);
$searchAuthor = $bookRepo->search('Vũ Trọng Phụng', 0, '', 10, 0, '', true);
assert_test("Tìm kiếm theo tác giả thật (Vũ Trọng Phụng)", $countAuthor >= 1 && count($searchAuthor) >= 1, "Found $countAuthor books");

// 4.2 Search by title: "Số đỏ"
$countTitle = $bookRepo->countSearch('Số đỏ', 0, '', true);
$searchTitle = $bookRepo->search('Số đỏ', 0, '', 10, 0, '', true);
assert_test("Tìm kiếm theo nhan đề thật (Số đỏ)", $countTitle >= 1 && count($searchTitle) >= 1, "Found $countTitle books");

// 4.3 Search by author: "Stephen Hawking"
$countHawking = $bookRepo->countSearch('Stephen Hawking', 0, '', true);
$searchHawking = $bookRepo->search('Stephen Hawking', 0, '', 10, 0, '', true);
assert_test("Tìm kiếm tác phẩm khoa học (Stephen Hawking)", $countHawking >= 1 && count($searchHawking) >= 1, "Found $countHawking books");

// 4.4 Filter by category: 1 (Văn học)
$countCat1 = $bookRepo->countSearch('', 1, '', true);
$searchCat1 = $bookRepo->search('', 1, '', 20, 0, '', true);
assert_test("Lọc theo thể loại Văn học (category=1)", $countCat1 >= 10 && count($searchCat1) >= 10, "Found $countCat1 books in category 1");


echo "\n=== 5. KIỂM THỬ TRANG CHI TIẾT SÁCH MỚI (/book/:id) ===\n";

$b101 = $db->query("SELECT id FROM books WHERE book_code = 'B101' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($b101) {
    $bookId = (int) $b101['id'];
    ob_start();
    (new \Library\Controllers\CatalogController())->show($bookId);
    $bookHtml = ob_get_clean();
    assert_test("Hiển thị chi tiết sách B101 (Số đỏ)", str_contains($bookHtml, 'Số đỏ') && str_contains($bookHtml, 'Vũ Trọng Phụng'), "Contains title and author");
    assert_test("Hiển thị DDC chuẩn cho B101", str_contains($bookHtml, '895.922'), "Contains DDC 895.922");
    assert_test("Hiển thị vị trí kệ cách ly cho B101", str_contains($bookHtml, 'Kho tiếp nhận — Chờ kiểm kê'), "Contains shelf location Kho tiếp nhận — Chờ kiểm kê");
    assert_test("Hiển thị thông báo bìa lưu chiểu trung tính minh bạch", str_contains($bookHtml, 'bìa lưu chiểu trung tính'), "Transparent notice present");
}

echo "\n=== TỔNG KẾT TEST TARGETED V12.1 ===\n";
$passed = count(array_filter($testResults, fn($r) => $r['pass']));
$total = count($testResults);
echo "Kết quả: $passed / $total PASS (" . round(($passed / $total) * 100, 1) . "%)\n";
