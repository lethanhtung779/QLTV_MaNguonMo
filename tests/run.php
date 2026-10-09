<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/autoload.php';

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('Smoke test failed: ' . $message);
    }
};

$assert(e('<script>') === '&lt;script&gt;', 'HTML output is escaped.');
$assert(format_date('2025-03-04') === '04/03/2025', 'Dates format in Vietnamese order.');
$assert(format_date('not-a-date') === '—', 'Invalid dates have a safe placeholder.');
$assert(book_cover(['isbn' => '978-0-06-093546-7']) === 'https://covers.openlibrary.org/b/isbn/9780060935467-M.jpg?default=false', 'ISBN cover URL is normalized.');
$assert(book_cover(['isbn' => '']) === '', 'Books without ISBN have no external cover URL.');
$assert(cover_fallback(['category_id' => 1]) === 'cover-ochre', 'Book fallback tone is deterministic.');
$assert(selected(2, '2') === ' selected', 'Form selection compares string/integer identifiers.');
$assert(\Library\Core\Csrf::valid(['unexpected' => 'array']) === false, 'CSRF rejects non-string tokens.');

$router = (new ReflectionClass(\Library\Core\Router::class))->newInstanceWithoutConstructor();
$normalize = new ReflectionMethod(\Library\Core\Router::class, 'normalizePath');
$assert($normalize->invoke($router, '/catalog/?q=book') === '/catalog', 'Router strips query strings and trailing slashes.');
$assert($normalize->invoke($router, '/') === '/', 'Router preserves the home route.');

fwrite(STDOUT, sprintf("Smoke tests: %d passed.\n", $checks));
