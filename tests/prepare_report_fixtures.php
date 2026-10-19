<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();
$fixtures=json_decode(file_get_contents(dirname(__DIR__).'/storage/http_qa.json'),true);
$tag='REPORT-'.bin2hex(random_bytes(4));
$book=(new \Library\Repositories\BookRepository($db))->create(['book_code'=>$tag,'title'=>'Sách kiểm thử phân trang báo cáo','author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>null,'description'=>null,'total_copies'=>1,'cover_url'=>null]);
$copy=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$book)->fetchColumn();
$reader=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>'QA phân trang','phone'=>null,'email'=>null,'address'=>null,'expires_on'=>null]);
$service=new \Library\Services\LoanService($db,5000);
for ($i=0;$i<25;$i++) {
 $service->checkout($reader,[$copy],(int)$fixtures['admin']['id'],date('Y-m-d'),date('Y-m-d',strtotime('+14 days')),'Report pagination fixture');
 $item=(int)$db->query('SELECT id FROM loan_items WHERE book_copy_id='.$copy.' AND returned_at IS NULL')->fetchColumn();
 $service->returnBook($item,(int)$fixtures['admin']['id'],'good');
}
echo "PASS: created 25 real checkout/return records in isolated test database for pagination.\n";
