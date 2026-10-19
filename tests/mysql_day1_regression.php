<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Database;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Services\LoanService;
use Library\Services\FineService;
$db=Database::connection();$reproduce=in_array('--reproduce',$argv,true);$checks=0;$failures=0;
$assert=function(bool $ok,string $label)use(&$checks,&$failures,$reproduce){$checks++;echo ($ok?'PASS: ':'REPRODUCED: ').$label.PHP_EOL;if(!$ok){$failures++;if(!$reproduce)throw new RuntimeException('FAIL: '.$label);}};
$reject=function(callable $run,string $label)use($assert){try{$run();}catch(RuntimeException $e){if($e instanceof PDOException)throw $e;$assert(true,$label);return;}$assert(false,$label);};
$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
$tag='DAY1-'.bin2hex(random_bytes(6));$books=new BookRepository($db);$readers=new ReaderRepository($db);$loans=new LoanService($db,5000);$fine=new FineService($db);
$makeBook=function(string $suffix,int $count=1)use($books,$tag){return $books->create(['book_code'=>$tag.$suffix,'title'=>$tag.$suffix,'author'=>'Day1 QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>$count,'cover_url'=>null]);};
$book=$makeBook('A',2);$book2=$makeBook('B');$reader=$readers->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>$tag,'phone'=>null,'email'=>null,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
$copyIds=array_map('intval',$db->query('SELECT id FROM book_copies WHERE book_id IN ('.$book.','.$book2.') ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));$day=date('Y-m-d');$due=date('Y-m-d',strtotime('+7 days'));$key=bin2hex(random_bytes(16));
$code=$loans->checkout($reader,[$copyIds[0]],$actor,$day,$due,'Day1 checkout',$key);
try{$again=$loans->checkout($reader,[$copyIds[0]],$actor,$day,$due,'Day1 checkout',$key);$same=$again===$code;}catch(RuntimeException){$same=false;}
$assert($same,'Checkout replay returns original loan without mutation');
$s=$db->prepare('SELECT li.id,l.id loan_id FROM loans l JOIN loan_items li ON li.loan_id=l.id WHERE l.loan_code=?');$s->execute([$code]);$item=$s->fetch();$itemId=(int)$item['id'];
$renewKey=bin2hex(random_bytes(16));$date=$loans->renew($itemId,$actor,null,$renewKey);$replayed=$loans->renew($itemId,$actor,null,$renewKey);
$assert($date===$replayed&&(int)$db->query('SELECT COUNT(*) FROM loan_renewals WHERE loan_item_id='.$itemId)->fetchColumn()===1,'Renewal replay does not extend twice');
$fine->assessConditionCharge(['id'=>$itemId,'reader_id'=>$reader,'title'=>'QA','copy_code'=>'QA'],'damaged',$actor);
$payKey=bin2hex(random_bytes(16));$fine->recordPayment($reader,'1000','cash','Day1 repeated reference','',$actor,$payKey);$fine->recordPayment($reader,'1000','cash','Day1 repeated reference','',$actor,$payKey);
$assert((int)$db->query('SELECT COUNT(*) FROM fine_payments WHERE reader_id='.$reader)->fetchColumn()===1,'Payment replay charges once while outstanding balance remains');
if(!$reproduce){
 $reject(fn()=>$fine->recordPayment($reader,'2000','cash','Day1 repeated reference','',$actor,$payKey),'Same payment key with different amount rejected');
 $reject(fn()=>$loans->checkout($reader,[$copyIds[1]],$actor,$day,$due,'Changed input',$key),'Same checkout key with different payload rejected');
}
$code2=$loans->checkout($reader,[$copyIds[2]],$actor,$day,$due,'Day1 status');$s->execute([$code2]);$item2=(int)$s->fetch()['id'];
$db->prepare('UPDATE readers SET expires_on=DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY) WHERE id=?')->execute([$reader]);
$reject(fn()=>$loans->renew($item2,$actor),'Expired card cannot renew');
$db->prepare('UPDATE readers SET expires_on=DATE_ADD(CURRENT_DATE,INTERVAL 1 YEAR),is_active=0 WHERE id=?')->execute([$reader]);
$reject(fn()=>$loans->renew($item2,$actor),'Inactive card cannot renew');
$db->prepare('UPDATE readers SET is_active=1 WHERE id=?')->execute([$reader]);
if(!$reproduce){
 $loans->returnBook($itemId,$actor);
 $retryKey=bin2hex(random_bytes(16));$before=(int)$db->query('SELECT COUNT(*) FROM loans')->fetchColumn();
 $reject(fn()=>$loans->checkout($reader,[$copyIds[1],$copyIds[2]],$actor,$day,$due,'Rollback',$retryKey),'Second unavailable copy rolls back whole checkout');
 $assert((int)$db->query('SELECT COUNT(*) FROM loans')->fetchColumn()===$before&&$db->query('SELECT status FROM book_copies WHERE id='.$copyIds[1])->fetchColumn()==='available','Failed checkout leaves header/items/copy intact');
 $q=$db->prepare('SELECT COUNT(*) FROM mutation_requests WHERE request_key=?');$q->execute([$retryKey]);$assert((int)$q->fetchColumn()===0,'Failed transaction leaves no committed idempotency claim');
 $loans->returnBook($item2,$actor);$retried=$loans->checkout($reader,[$copyIds[1],$copyIds[2]],$actor,$day,$due,'Rollback',$retryKey);$assert($retried!=='','Retry same failed key can succeed');
 $s->execute([$retried]);$newItem=(int)$s->fetch()['id'];$loans->returnBook($newItem,$actor);
 $assert($db->query('SELECT status FROM book_copies WHERE id='.$copyIds[1])->fetchColumn()==='available' && $db->query('SELECT status FROM book_copies WHERE id='.$copyIds[2])->fetchColumn()==='on_loan','Partial return makes only returned copy available');
 $reject(fn()=>$loans->returnBook($itemId,$actor),'Duplicate return rejected without extra charges');
 $s->execute([$retried]);$returnLoan=(int)$s->fetch()['loan_id'];$loans->returnAll($returnLoan,$actor);
 $assert((int)$db->query('SELECT COUNT(*) FROM loan_items WHERE loan_id='.$returnLoan.' AND returned_at IS NULL')->fetchColumn()===0,'Return-all closes every remaining item');
 $assert((int)$db->query('SELECT COUNT(*) FROM book_copies c WHERE (c.status="on_loan") <> EXISTS(SELECT 1 FROM loan_items li WHERE li.book_copy_id=c.id AND li.returned_at IS NULL)')->fetchColumn()===0,'Inventory invariant remains valid after workflow');
}
echo 'Day1 regression: '.$checks.' expectations; '.$failures.' violations.'.PHP_EOL;