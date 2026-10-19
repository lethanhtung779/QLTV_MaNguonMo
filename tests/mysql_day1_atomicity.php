<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Database;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Services\LoanService;
use Library\Services\FineService;
$db=Database::connection();$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
$checks=0;$assert=function(bool $ok,string $why)use(&$checks){if(!$ok)throw new RuntimeException('FAIL: '.$why);$checks++;echo 'PASS: '.$why.PHP_EOL;};
$tag='ATOMIC-'.bin2hex(random_bytes(5));$books=new BookRepository($db);$reader=(new ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>$tag,'phone'=>null,'email'=>null,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
$copies=[];foreach(['A','B'] as $suffix){$id=$books->create(['book_code'=>$tag.$suffix,'title'=>$tag.$suffix,'author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>1,'cover_url'=>null]);$copies[]=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$id)->fetchColumn();}
$loans=new LoanService($db,5000);$fine=new FineService($db);$day=date('Y-m-d');$due=date('Y-m-d',strtotime('+7 days'));
$hash=function()use($db){$out=[];foreach(['loans','loan_items','book_copies','loan_renewals','fine_charges','fine_payments','fine_payment_allocations','activity_logs','notifications','reservations','mutation_requests'] as $t)$out[$t]=hash('sha256',json_encode($db->query('SELECT * FROM '.$t.' ORDER BY 1')->fetchAll(),JSON_THROW_ON_ERROR));return $out;};
$failLate=function(callable $run,string $label)use($db,$hash,$assert){
 $before=$hash();
 $db->exec("CREATE TRIGGER qa_day1_fail_late BEFORE INSERT ON activity_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='DAY1 injected late failure'");
 try {try{$run();throw new RuntimeException('Operation unexpectedly committed');}catch(PDOException $e){$assert(str_contains($e->getMessage(),'DAY1 injected late failure'),$label.' reaches injected SQL failure');}}
 finally{$db->exec('DROP TRIGGER IF EXISTS qa_day1_fail_late');}
 $assert(!$db->inTransaction()&&$before===$hash(),$label.' restores all 11 business/ledger tables after rollback');
};
$key=bin2hex(random_bytes(16));$create=fn()=>$loans->checkout($reader,$copies,$actor,$day,$due,'Atomic fixture',$key);
$failLate($create,'Multi-copy checkout');$code=$create();$assert($code===$create(),'Failed checkout key retries and then replays');
$q=$db->prepare('SELECT li.id,l.id loan_id FROM loans l JOIN loan_items li ON li.loan_id=l.id WHERE l.loan_code=? ORDER BY li.id');$q->execute([$code]);$items=$q->fetchAll();$item=(int)$items[0]['id'];$loan=(int)$items[0]['loan_id'];
$renewKey=bin2hex(random_bytes(16));$renew=fn()=>$loans->renew($item,$actor,null,$renewKey);$failLate($renew,'Renewal');$date=$renew();$assert($date===$renew(),'Failed renewal key retries and replays');
$fine->assessConditionCharge(['id'=>$item,'reader_id'=>$reader,'title'=>'QA','copy_code'=>'QA'],'damaged',$actor);
$payKey=bin2hex(random_bytes(16));$pay=fn()=>$fine->recordPayment($reader,'1000','cash','atomic','QA',$actor,$payKey);
$failLate($pay,'Fine payment/allocation');$pay();$pay();$assert((int)$db->query('SELECT COUNT(*) FROM fine_payments WHERE reader_id='.$reader)->fetchColumn()===1,'Failed payment key retries and allocates once');
$failLate(fn()=>$loans->returnAll($loan,$actor,'damaged'),'Return-all with condition fines');
$loans->returnBook($item,$actor);$before=$hash();
try{$loans->returnBook($item,$actor);throw new Exception('Duplicate return accepted');}catch(RuntimeException $e){if($e instanceof PDOException)throw $e;}
$assert($before===$hash(),'Repeated partial return preserves all business tables');
$loans->returnAll($loan,$actor);$assert((int)$db->query('SELECT COUNT(*) FROM loan_items WHERE loan_id='.$loan.' AND returned_at IS NULL')->fetchColumn()===0,'Partial then return-all closes only outstanding items');
// Use a rollback-only policy update: immutable values and old loan snapshots stay consistent.
$settings=new SystemSettingRepository($db);$old=$settings->currentPolicy();$db->beginTransaction();
try{
 $values=[];foreach($db->query('SELECT setting_key,setting_value FROM system_settings')->fetchAll() as $r)$values[$r['setting_key']]=(string)$r['setting_value'];
 $values['fine_per_overdue_day']='9000';$values['renewal_days']='7';$settings->update($values,$actor);$current=$settings->currentPolicy();
 $assert((int)$current['id']>(int)$old['id']&&(int)$current['renewal_days']===7,'Policy update has one new immutable version');
 $assert((float)$db->query('SELECT daily_fine FROM loan_items WHERE id='.$item)->fetchColumn()===(float)$old['fine_per_overdue_day'],'Policy change does not rewrite old loan daily fine');
 $assert($db->query('SELECT new_due_date FROM loan_renewals WHERE loan_item_id='.$item.' ORDER BY id DESC LIMIT 1')->fetchColumn()===$date,'Policy change does not rewrite old renewal ledger');
 $assert($db->query('SELECT renewal_days FROM loan_renewals WHERE loan_item_id='.$item.' ORDER BY id DESC LIMIT 1')->fetchColumn()===$old['renewal_days'],'Old renewal retains applied duration');
}finally{$db->rollBack();}
$assert($settings->currentPolicy()['id']===$old['id'],'Rollback restores policy settings and version');
$originalValues=$values;
foreach($db->query('SELECT setting_key,setting_value FROM system_settings')->fetchAll() as $r)$originalValues[$r['setting_key']]=(string)$r['setting_value'];
try {
 $db->beginTransaction();$settings->update($values,$actor);$db->commit();$applied=$settings->currentPolicy();
 $newCode=$loans->checkout($reader,[$copies[0]],$actor,$day,$due,'New policy');
 $q->execute([$newCode]);$newItem=(int)$q->fetch()['id'];
 $assert((float)$db->query('SELECT daily_fine FROM loan_items WHERE id='.$newItem)->fetchColumn()===9000.0,'New checkout snapshots new fine policy');
 $newDue=$loans->renew($newItem,$actor);
 $assert($newDue===date('Y-m-d',strtotime($due.' +7 days')),'New renewal uses new policy duration');
 $snapshot=$db->query('SELECT policy_version,renewal_days FROM loan_renewals WHERE loan_item_id='.$newItem)->fetch();
 $assert((int)$snapshot['policy_version']===(int)$applied['id']&&(int)$snapshot['renewal_days']===7,'New renewal version and duration come from the same policy row');
 $assert((float)$db->query('SELECT daily_fine FROM loan_items WHERE id='.$item)->fetchColumn()===(float)$old['fine_per_overdue_day'],'Committed policy update still preserves old loan fine');
 $loans->returnBook($newItem,$actor);
}finally{
 if($db->inTransaction())$db->rollBack();
 $db->beginTransaction();$settings->update($originalValues,$actor);$db->commit();
}
$assert((float)$settings->currentPolicy()['fine_per_overdue_day']===(float)$old['fine_per_overdue_day'],'Test restores original policy values using another immutable version');
echo "Day1 atomicity/policy: {$checks} checks passed.".PHP_EOL;

