<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$root=dirname(__DIR__);
if(($argv[1]??'')==='--state'){
 $f=json_decode(file_get_contents($root.'/storage/day1_http.json'),true,512,JSON_THROW_ON_ERROR);$reader=(int)$f['reader_id'];$item=(int)$f['item_id'];
 $out=[];foreach(['loans','fine_payments'] as $table)$out[$table]=(int)$db->query('SELECT COUNT(*) FROM '.$table.' WHERE reader_id='.$reader)->fetchColumn();
 $out['renewals']=(int)$db->query('SELECT COUNT(*) FROM loan_renewals WHERE loan_item_id='.$item)->fetchColumn();
 $out['due_date']=$db->query('SELECT due_date FROM loan_items WHERE id='.$item)->fetchColumn();
 $out['claims']=(int)$db->query('SELECT COUNT(*) FROM mutation_requests')->fetchColumn();
 echo json_encode($out,JSON_THROW_ON_ERROR);exit;
}
$f=json_decode(file_get_contents($root.'/storage/http_qa.json'),true,512,JSON_THROW_ON_ERROR);$actor=(int)$f['admin']['id'];$reader=(int)$f['reader']['reader_id'];
$tag='HTTPDAY1-'.bin2hex(random_bytes(4));$copyIds=[];$bookIds=[];
$email=strtolower($tag).'@example.test';$password='Qa'.bin2hex(random_bytes(16)).'12';
$userId=(new \Library\Repositories\UserRepository($db))->create(['name'=>$tag,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>'reader']);
$reader=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>$userId,'card_number'=>$tag,'full_name'=>$tag,'phone'=>null,'email'=>$email,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
foreach(['A','B','C'] as $suffix){$book=(new \Library\Repositories\BookRepository($db))->create(['book_code'=>$tag.$suffix,'title'=>$tag.$suffix,'author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>1,'cover_url'=>null]);$bookIds[]=$book;$copyIds[]=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$book)->fetchColumn();}
$day=date('Y-m-d');$due=date('Y-m-d',strtotime('+7 days'));$code=(new \Library\Services\LoanService($db,5000))->checkout($reader,[$copyIds[0]],$actor,$day,$due);
$q=$db->prepare('SELECT li.id FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.loan_code=?');$q->execute([$code]);$item=(int)$q->fetchColumn();
(new \Library\Services\FineService($db))->assessConditionCharge(['id'=>$item,'reader_id'=>$reader,'title'=>'HTTP QA','copy_code'=>'HTTP QA'],'damaged',$actor);
$service=new \Library\Services\LoanService($db,5000);
$returnedCode=$service->checkout($reader,[$copyIds[2]],$actor,$day,$due);
$q->execute([$returnedCode]);$service->returnBook((int)$q->fetchColumn(),$actor);
file_put_contents($root.'/storage/day1_http.json',json_encode(['zero_book_id'=>$bookIds[2],'zero_copy_id'=>$copyIds[2],'zero_book_code'=>$tag.'C','reader_credentials'=>['email'=>$email,'password'=>$password],'reader_id'=>$reader,'item_id'=>$item,'copy_id'=>$copyIds[1],'borrowed_at'=>$day,'due_at'=>$due],JSON_THROW_ON_ERROR));
echo 'PASS: isolated Day1 HTTP fixtures ready.',PHP_EOL;
