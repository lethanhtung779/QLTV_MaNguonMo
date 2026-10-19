<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();
if(!str_starts_with($name,'paper_page_test_')&&($name!=='paper_page_library'||!in_array('--allow-main',$argv,true)))throw new RuntimeException('Explicit main flag or test DB required');
$rows=json_decode(file_get_contents(__DIR__.'/category_catalog_manifest.json'),true,512,JSON_THROW_ON_ERROR);
if(count($rows)!==7||count(array_unique(array_column($rows,'book_code')))!==7)throw new RuntimeException('Reviewed seven-book manifest required');
$licenses=['CC0-1.0','CC-BY-NC-3.0','CC-BY-4.0'];$categories=['Kinh tế','Khoa học','Lịch sử','Tâm lý - Kỹ năng sống','Thiếu nhi','Tin học','Văn phòng'];
foreach($rows as $r){
 if(!preg_match('/^CAT-[A-Z0-9-]{4,32}$/',$r['book_code'])||!preg_match('#^/assets/book-covers/[a-z0-9-]+\.jpg$#',$r['cover_url'])||!in_array($r['license'],$licenses,true)||!in_array($r['category'],$categories,true)||$r['isbn']!==null||$r['total_copies']!==0)throw new RuntimeException('Unreviewed record');
 $p=dirname(__DIR__).'/public'.$r['cover_url'];$info=is_file($p)?getimagesize($p):false;
 if(!$info||$info['mime']!=='image/jpeg'||$info[0]<150||$info[1]<200||$info[0]>5000||$info[1]>5000||filesize($p)>3000000||!hash_equals($r['cover_sha256'],hash_file('sha256',$p)))throw new RuntimeException('Invalid cover');
 $u=parse_url($r['source_url']);if(($u['scheme']??'')!=='https'||!in_array($u['host']??'',['standardebooks.org','greenteapress.com','books.libreoffice.org'],true))throw new RuntimeException('Unapproved source');
}
if(!in_array('--apply',$argv,true)){echo 'PASS seven licensed covers validated; no DB writes',PHP_EOL;exit;}
$db->beginTransaction();try{$created=0;$ids=[];$repo=new Library\Repositories\BookRepository($db);
 foreach($rows as $r){
  $q=$db->prepare('SELECT id FROM categories WHERE name=?');$q->execute([$r['category']]);$categoryId=(int)$q->fetchColumn();
  if(!$categoryId){if($r['category']!=='Văn phòng')throw new RuntimeException('Expected category missing');$q=$db->prepare('INSERT INTO categories(name,description) VALUES(?,?)');$q->execute([$r['category'],'Sách kỹ năng và công cụ văn phòng']);$categoryId=(int)$db->lastInsertId();}
  $q=$db->prepare('SELECT * FROM books WHERE book_code=? FOR UPDATE');$q->execute([$r['book_code']]);$b=$q->fetch();
  if($b){foreach(['title','author','publisher','publication_year','cover_url','description'] as $f)if((string)$b[$f] !== (string)$r[$f])throw new RuntimeException('Existing book conflict');if((int)$b['category_id']!==$categoryId||$b['isbn']!==null||(int)$b['total_copies']!==0)throw new RuntimeException('Existing inventory conflict');$ids[$r['book_code']]=(int)$b['id'];continue;}
  $data=array_intersect_key($r,array_flip(['book_code','title','author','isbn','publisher','publication_year','description','total_copies','cover_url']));$data['category_id']=$categoryId;$ids[$r['book_code']]=$repo->create($data);$created++;
 }
 $db->commit();echo json_encode(['database'=>$name,'created'=>$created,'ids'=>$ids,'physical_copies_added'=>0,'visibility_changes'=>0],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
