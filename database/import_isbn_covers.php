<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();
if(!str_starts_with($name,'paper_page_test_')&&($name!=='paper_page_library'||!in_array('--allow-main',$argv,true)))throw new RuntimeException('Explicit main guard required');
$rows=json_decode(file_get_contents(__DIR__.'/isbn_cover_manifest.json'),true,512,JSON_THROW_ON_ERROR);if(count($rows)!==6)throw new RuntimeException('Reviewed six-source manifest required');
$db->beginTransaction();try{$changed=0;
 foreach($rows as $r){
  if(!preg_match('#^https://covers\.openlibrary\.org/b/id/[0-9]+-L\.jpg\?default=false$#',$r['cover_url']))throw new RuntimeException('Unapproved cover URL');
  $q=$db->prepare('SELECT * FROM books WHERE book_code=? FOR UPDATE');$q->execute([$r['book_code']]);$b=$q->fetch();
  if(!$b || $b['title']!==$r['title'] || $b['author']!==$r['author'] || $b['isbn']!==$r['isbn'])throw new RuntimeException('Book identity changed');
  if($b['cover_url']===$r['cover_url'])continue;
  if($b['cover_url']!==null&&$b['cover_url']!=='')throw new RuntimeException('Refusing to replace existing cover');
  $q=$db->prepare('UPDATE books SET cover_url=?,updated_at=updated_at WHERE id=? AND isbn=? AND (cover_url IS NULL OR cover_url="")');$q->execute([$r['cover_url'],$b['id'],$r['isbn']]);$changed+=$q->rowCount();
 }
 if(in_array('--apply',$argv,true))$db->commit();else $db->rollBack();
 echo json_encode(['database'=>$name,'covers_updated'=>in_array('--apply',$argv,true)?$changed:0,'dry_run'=>!in_array('--apply',$argv,true),'basis'=>'exact stored ISBN, English cover; Vietnamese edition not verified'],JSON_PRETTY_PRINT),PHP_EOL;
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
