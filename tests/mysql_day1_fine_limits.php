<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$reproduce=in_array('--reproduce',$argv,true);$checks=0;$failures=0;
$assert=function(bool $ok,string $why)use(&$checks,&$failures,$reproduce){$checks++;echo ($ok?'PASS: ':'REPRODUCED: ').$why.PHP_EOL;if(!$ok){$failures++;if(!$reproduce)throw new RuntimeException('FAIL: '.$why);}};
$tag=str_pad('LIMIT-'.bin2hex(random_bytes(8)),40,'X');$title=str_repeat('Ấ',240);
$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
$book=(new \Library\Repositories\BookRepository($db))->create(['book_code'=>$tag,'title'=>$title,'author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>1,'cover_url'=>null]);
$reader=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>'Fine limits QA','phone'=>null,'email'=>null,'address'=>null,'expires_on'=>null]);
$copy=$db->query('SELECT id,copy_code FROM book_copies WHERE book_id='.$book)->fetch();
$code=(new \Library\Services\LoanService($db,5000))->checkout($reader,[(int)$copy['id']],$actor,date('Y-m-d',strtotime('-5 days')),date('Y-m-d',strtotime('-2 days')));
$q=$db->prepare('SELECT li.id,li.daily_fine FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.loan_code=?');$q->execute([$code]);$item=$q->fetch();
$fine=new \Library\Services\FineService($db);
try{$fine->synchronizeReader($reader,$actor);$ok=true;}catch(PDOException $e){if(!$reproduce)throw $e;$ok=false;}
$assert($ok,'Maximum allowed title/code can accrue overdue charges without SQL truncation');
try{$fine->assessConditionCharge(['id'=>(int)$item['id'],'reader_id'=>$reader,'title'=>$title,'copy_code'=>$copy['copy_code']],'damaged',$actor);$ok=true;}catch(PDOException $e){if(!$reproduce)throw $e;$ok=false;}
$assert($ok,'Maximum allowed title/code can create condition charge without SQL truncation');
if(!$reproduce){
 $q=$db->prepare('SELECT charge_type,amount,CHAR_LENGTH(description) length FROM fine_charges WHERE loan_item_id=?');$q->execute([$item['id']]);$charges=$q->fetchAll();
 $assert(count($charges)===2&&max(array_column($charges,'length'))<=300,'Both charge descriptions fit 300 Unicode characters');
 $byType=array_column($charges,'amount','charge_type');
 $assert((float)$byType['overdue']===(float)$item['daily_fine']*2,'Description clipping leaves money calculation exact');
}
$selects=fn()=>(int)$db->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch()['Value'];
$before=$selects();try{$fine->synchronizeAll();$ok=true;}catch(PDOException $e){if(!$reproduce)throw $e;$ok=false;}
$delta=$selects()-$before;
$assert($ok&&$delta===1,'Synchronize-all reads overdue items with one SELECT, not one per reader (observed '.$delta.')');
echo "Day1 fine limits/query: {$checks} expectations; {$failures} violations.",PHP_EOL;
