<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap/app.php';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$db = Library\Core\Database::connection();
$books = $db->query('SELECT b.*, c.name category_name, p.name publisher_relation,
    (SELECT GROUP_CONCAT(a.name ORDER BY a.id SEPARATOR "; ") FROM book_authors ba JOIN authors a ON a.id=ba.author_id WHERE ba.book_id=b.id) author_relations
    FROM books b LEFT JOIN categories c ON c.id=b.category_id LEFT JOIN publishers p ON p.id=b.publisher_id ORDER BY b.id')->fetchAll();
$rows = [];
foreach ($books as $book) {
    $cover = book_cover($book);
    $path = str_starts_with($cover, '/covers/') ? dirname(__DIR__) . '/storage/uploads/covers/' . basename($cover) : dirname(__DIR__) . '/public' . $cover;
    $image = $cover && is_file($path) ? getimagesize($path) : false;
    $rows[] = ['id'=>$book['id'], 'book_code'=>$book['book_code'], 'title'=>$book['title'], 'author'=>$book['author'],
        'author_relations'=>$book['author_relations'], 'publisher'=>$book['publisher'], 'publisher_relation'=>$book['publisher_relation'],
        'publication_year'=>$book['publication_year'], 'isbn'=>$book['isbn'], 'category'=>$book['category_name'],
        'cover_url'=>$book['cover_url'], 'usable_local_image'=>(bool)$image,
        'mime'=>$image['mime']??null, 'width'=>$image[0]??null, 'height'=>$image[1]??null,
        'bytes'=>$image?filesize($path):null, 'sha256'=>$image?hash_file('sha256',$path):null];
}
echo json_encode(['time'=>date(DATE_ATOM),'database'=>$db->query('SELECT DATABASE()')->fetchColumn(),'books'=>$rows],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), "\n";