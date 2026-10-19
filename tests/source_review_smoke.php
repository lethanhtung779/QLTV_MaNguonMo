<?php
declare(strict_types=1);

/** Database-free regression checks for the source review patch. */
require dirname(__DIR__) . '/bootstrap/autoload.php';

use Library\Controllers\BookImportController;
use Library\Services\BarcodeService;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $checks++;
};

$check(BookImportController::validIsbn('978-0-13-235088-4'), 'ISBN-13 checksum');
$check(BookImportController::validIsbn('0306406152'), 'ISBN-10 checksum');
$check(!BookImportController::validIsbn('8935095633340'), 'EAN-13 product barcode cannot be ISBN');
$check(!BookImportController::validIsbn('9780132350885'), 'Wrong ISBN-13 checksum rejected');
$check(!BookImportController::validIsbn('test-input'), 'Random text rejected as ISBN');

$book = [
    'book_code' => 'AUTH-017',
    'title' => 'Bà Ngoại Trên Cây Táo (Die Omama im Apfelbaum)',
    'author' => 'Mira Lobe, Susi Weigel',
    'publisher' => 'Nhà xuất bản Kim Đồng',
    'isbn' => '9786042341219',
    'category_id' => 7,
    'description' => 'Sách thiếu nhi',
];
$metadata = book_extended_metadata($book);
$check(isset($metadata['verification_badge']), 'Badge key always provided for AUTH catalogue book');
$check(isset($metadata['rich_description']), 'Description key always provided for AUTH catalogue book');
$check(($metadata['source_url'] ?? '') === 'https://nxbkimdong.com.vn/products/ba-ngoai-tren-cay-tao', 'Publisher source instead of Standard Ebooks');
$check(($metadata['license'] ?? '') === '', 'No fabricated Open Access licence for publisher edition');

$service = new BarcodeService();
$check(str_contains($service->renderBarcodeSvg('B810-0001'), '<svg'), 'Code 128 SVG produced');
$check(str_contains($service->renderQrSvg('B810-0001'), '<svg'), 'QR SVG produced');
$check(str_contains($service->renderQrSvg(str_repeat('A', 62)), '<svg'), 'QR maximum supported payload encoded');
$check(str_contains($service->renderBarcodeSvg(str_repeat('X', 80)), '<svg'), 'Code 128 maximum supported payload encoded');
foreach ([['barcode', ''], ['barcode', "\x01"], ['barcode', str_repeat('X', 81)], ['qr', str_repeat('X', 63)]] as [$type, $value]) {
    try {
        if ($type === 'barcode') $service->renderBarcodeSvg($value);
        else $service->renderQrSvg($value);
        $check(false, "Invalid $type payload must be rejected");
    } catch (InvalidArgumentException $e) {
        $check(true, "Invalid $type payload rejected");
    }
}
$controllerSource = file_get_contents(dirname(__DIR__) . '/app/Controllers/BookImportController.php');
$check(!str_contains($controllerSource, "['confirmed_data']"), 'Import commit does not trust posted hidden JSON');
$check(str_contains($controllerSource, "'total_copies'" ) === false || str_contains($controllerSource, '0, NULL, 1, 1'), 'Bulk import creates zero copies');

printf("Paper Haven source review: %d checks passed.\n", $checks);
