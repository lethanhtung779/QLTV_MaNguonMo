<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$reproduce=in_array('--reproduce',$argv,true);$checks=0;
$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();$tag='STATES-'.bin2hex(random_bytes(6));
$books=new \Library\Repositories\BookRepository($db);$copies=new \Library\Repositories\CopyRepository($db);
$book=$books->create(['book_code'=>$tag,'title'=>$tag,'author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>1,'cover_url'=>null]);
$id=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$book)->fetchColumn();
$save=fn($status,$condition)=>$copies->save($book,$id,['copy_code'=>$tag.'-0001','barcode'=>null,'shelf_location'=>null,'item_condition'=>$condition,'status'=>$status],$actor);
$assert=function(bool $ok,string $label)use(&$checks){if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++;echo 'PASS: '.$label.PHP_EOL;};
foreach(['damaged','lost'] as $status){$save($status,'good');$row=$db->query('SELECT status,item_condition FROM book_copies WHERE id='.$id)->fetch();$assert($row['status']===$status&&$row['item_condition']===$status,'Unusable state aligns condition: '.$status);$save('available','good');}
try{$save('available','damaged');throw new LogicException('Invalid availability accepted');}catch(RuntimeException $e){if($e instanceof PDOException)throw $e;$assert(true,'Damaged condition cannot be available');}
try{$save('on_loan','good');throw new LogicException('Manual checkout state accepted');}catch(RuntimeException $e){if($e instanceof PDOException)throw $e;$assert(true,'Manual on-loan state rejected; checkout service required');}
try{$save('retired','good');}catch(PDOException $e){if(!$reproduce)throw $e;echo 'REPRODUCED: retiring the final copy fails MySQL CHECK despite supported UI status: '.$e->errorInfo[1].PHP_EOL;exit;}
$assert($db->query('SELECT status FROM book_copies WHERE id='.$id)->fetchColumn()==='retired','Final physical copy can be retired without deleting history');
$assert((int)$db->query('SELECT total_copies FROM books WHERE id='.$book)->fetchColumn()===0,'Retired final copy makes operational inventory zero');
$data=['book_code'=>$tag,'title'=>'Updated zero-inventory title','author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>0,'cover_url'=>null];$books->update($book,$data);
$assert((int)$db->query('SELECT COUNT(*) FROM book_copies WHERE book_id='.$book)->fetchColumn()===1,'Editing zero-inventory title preserves physical copy history');
$save('available','worn');$assert((int)$db->query('SELECT total_copies FROM books WHERE id='.$book)->fetchColumn()===1,'Restoring repaired copy updates inventory to one');
echo "Day1 copy states: {$checks} checks passed.",PHP_EOL;
