<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();
$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();
if (!str_starts_with($name,'paper_page_test_') && ($name!=='paper_page_library' || !in_array('--allow-main',$argv,true))) throw new RuntimeException('Explicit main flag or test database required.');
$rows=json_decode(file_get_contents(__DIR__.'/real_catalog_manifest.json'),true,512,JSON_THROW_ON_ERROR);
if(count($rows)!==12)throw new RuntimeException('Expected reviewed 12-book manifest');
foreach($rows as $r){
 if(!preg_match('/^SE-[A-F0-9]{12}$/',$r['book_code']) || !preg_match('#^/assets/book-covers/[a-z0-9-]+\.jpg$#',$r['cover_url']))throw new RuntimeException('Unsafe filename/code');
 $p=dirname(__DIR__).'/public'.$r['cover_url'];$info=is_file($p)?getimagesize($p):false;
 if(!$info || $info['mime']!=='image/jpeg' || $info[0]<150 || $info[1]<200 || $info[0]>5000 || $info[1]>5000 || filesize($p)>3000000 || !hash_equals($r['cover_sha256'],hash_file('sha256',$p)))throw new RuntimeException('Invalid cover '.$r['book_code']);
 if($r['publisher']!=='Standard Ebooks' || $r['license']!=='CC0-1.0' || $r['isbn']!==null || $r['total_copies']!==0)throw new RuntimeException('Unreviewed metadata');
}
if(!in_array('--apply',$argv,true)){echo 'PASS dry-run:12 validated editions, no writes on '.$name.PHP_EOL;exit;}
$db->beginTransaction();
try{
 $category=$db->prepare('SELECT id FROM categories WHERE name=?');$category->execute(['Văn học']);$categoryId=(int)$category->fetchColumn();if(!$categoryId)throw new RuntimeException('Literature category missing');
 $repo=new Library\Repositories\BookRepository($db);$ids=[];$created=0;
 foreach($rows as $r){
  $q=$db->prepare('SELECT * FROM books WHERE book_code=? FOR UPDATE');$q->execute([$r['book_code']]);$existing=$q->fetch();
  if($existing){foreach(['title','author','publisher','publication_year','cover_url'] as $field)if((string)$existing[$field] !== (string)$r[$field])throw new RuntimeException('Existing catalog conflict '.$r['book_code']);$ids[]=(int)$existing['id'];continue;}
  $ids[]=$repo->create(['book_code'=>$r['book_code'],'title'=>$r['title'],'author'=>$r['author'],'isbn'=>null,'category_id'=>$categoryId,'publisher'=>$r['publisher'],'publication_year'=>$r['publication_year'],'description'=>$r['description'],'total_copies'=>0,'cover_url'=>$r['cover_url']]);$created++;
 }
 $hidden=[];
 // Visibility is independent of circulation. Never deactivate or rewrite demo histories.
 for($i=1;$i<=21;$i++){
  $code=sprintf('DEMO-B%03d',$i);$q=$db->prepare('SELECT id,description,is_public FROM books WHERE book_code=? FOR UPDATE');$q->execute([$code]);$b=$q->fetch();
  if(!$b || !str_contains($b['description'],'Dữ liệu demo tổng hợp'))throw new RuntimeException('Reviewed demo record missing '.$code);
  if((int)$b['is_public']!==0){$q=$db->prepare('UPDATE books SET is_public=0, updated_at=updated_at WHERE id=? AND is_public=1');$q->execute([$b['id']]);}
  $hidden[]=(int)$b['id'];
 }
 $db->commit();echo json_encode(['database'=>$name,'created'=>$created,'real_book_ids'=>$ids,'hidden_demo_ids'=>$hidden,'physical_copies_added'=>0],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
