<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Database;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Services\LoanService;
use Library\Services\FineService;
$db=Database::connection();
if (($argv[1]??'')==='--worker') {
    [,,$operation,$reader,$target,$actor,$key,$ready]=$argv;
    file_put_contents($ready,(string)$db->query('SELECT CONNECTION_ID()')->fetchColumn());
    $service=new LoanService($db,5000);
    if($operation==='checkout')$result=$service->checkout((int)$reader,[(int)$target],(int)$actor,date('Y-m-d'),date('Y-m-d',strtotime('+7 days')),'Concurrent same request',$key);
    elseif($operation==='renew')$result=$service->renew((int)$target,(int)$actor,null,$key);
    else{
        (new FineService($db))->recordPayment((int)$reader,'1000','cash','Concurrent same request','',(int)$actor,$key);
        $q=$db->prepare('SELECT result_json FROM mutation_requests WHERE actor_id=? AND operation="payment" AND request_key=?');
        $q->execute([$actor,$key]);$result=json_decode($q->fetchColumn(),true,512,JSON_THROW_ON_ERROR)['payment_id'];
    }
    echo json_encode(['result'=>$result],JSON_THROW_ON_ERROR),PHP_EOL;exit;
}
$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
$tag='RACE-'.bin2hex(random_bytes(5));$reader=(new ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>$tag,'phone'=>null,'email'=>null,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
$copies=[];foreach(['A','B'] as $suffix){$id=(new BookRepository($db))->create(['book_code'=>$tag.$suffix,'title'=>$tag.$suffix,'author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'description'=>null,'total_copies'=>1,'cover_url'=>null]);$copies[]=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$id)->fetchColumn();}
$code=(new LoanService($db,5000))->checkout($reader,[$copies[0]],$actor,date('Y-m-d'),date('Y-m-d',strtotime('+7 days')));
$q=$db->prepare('SELECT li.id FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.loan_code=?');$q->execute([$code]);$item=(int)$q->fetchColumn();
(new FineService($db))->assessConditionCharge(['id'=>$item,'reader_id'=>$reader,'title'=>'QA','copy_code'=>'QA'],'damaged',$actor);
$dir=dirname(__DIR__).'/storage/backups/day1-20261011/race-'.$tag;mkdir($dir,0700);
foreach(['checkout'=>$copies[1],'renew'=>$item,'payment'=>0] as $operation=>$target){
 $key=bin2hex(random_bytes(16));$workers=[];$db->beginTransaction();
 try {
  $q=$db->prepare('SELECT id FROM readers WHERE id=? FOR UPDATE');$q->execute([$reader]);
  for($i=0;$i<2;$i++){
   $ready=$dir.'/'.$operation.'-'.$i.'.ready';
   $proc=proc_open([PHP_BINARY,__FILE__,'--worker',$operation,(string)$reader,(string)$target,(string)$actor,$key,$ready],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__),null,['bypass_shell'=>true]);
   if(!is_resource($proc))throw new RuntimeException('Cannot launch worker');
   fclose($pipes[0]);$workers[]=['proc'=>$proc,'pipes'=>$pipes,'ready'=>$ready];
  }
  $deadline=microtime(true)+10;
  do{$ids=[];foreach($workers as $w)if(is_file($w['ready']))$ids[]=(int)file_get_contents($w['ready']);if(count(array_unique($ids))===2)break;usleep(20000);}while(microtime(true)<$deadline);
  if(count(array_unique($ids))!==2)throw new RuntimeException('Workers did not establish two sessions');
  $wait=$db->prepare('SELECT COUNT(DISTINCT t.PROCESSLIST_ID) FROM performance_schema.data_lock_waits w
      JOIN performance_schema.data_locks d ON d.ENGINE_LOCK_ID=w.REQUESTING_ENGINE_LOCK_ID
      JOIN performance_schema.threads t ON t.THREAD_ID=w.REQUESTING_THREAD_ID
      WHERE d.OBJECT_SCHEMA=DATABASE() AND d.OBJECT_NAME IN ("readers","mutation_requests") AND t.PROCESSLIST_ID IN (?,?)');
  $deadline=microtime(true)+10;
  do{$wait->execute($ids);$count=(int)$wait->fetchColumn();if($count===2)break;usleep(20000);}while(microtime(true)<$deadline);
  if($count!==2)throw new RuntimeException('Expected two independently observed lock waiters for '.$operation);
  $db->commit();$results=[];
  foreach($workers as &$w){
   $out=stream_get_contents($w['pipes'][1]);$err=stream_get_contents($w['pipes'][2]);fclose($w['pipes'][1]);fclose($w['pipes'][2]);$exit=proc_close($w['proc']);$w['proc']=null;
   if($exit!==0)throw new RuntimeException('Worker failed: '.$err.$out);
   $results[]=json_decode(trim($out),true,512,JSON_THROW_ON_ERROR)['result'];
  }unset($w);
  if($results[0]!==$results[1])throw new RuntimeException('Concurrent replay returned different results');
  $sql=match($operation){'checkout'=>'SELECT COUNT(*) FROM loans WHERE reader_id='.$reader.' AND loan_code<>'.$db->quote($code),'renew'=>'SELECT COUNT(*) FROM loan_renewals WHERE loan_item_id='.$item,default=>'SELECT COUNT(*) FROM fine_payments WHERE reader_id='.$reader};
  if((int)$db->query($sql)->fetchColumn()!==1)throw new RuntimeException('Duplicate committed business records');
  $e=['operation'=>$operation,'sessions'=>$ids,'observed_waiters'=>$count,'results'=>$results,'committed_records'=>1];
  file_put_contents($dir.'/'.$operation.'.json',json_encode($e,JSON_PRETTY_PRINT));
  echo 'PASS: '.$operation.' same key: 2 MySQL sessions, 2 observed lock waiters, same result, 1 committed operation.'.PHP_EOL;
 }finally{
  if($db->inTransaction())$db->rollBack();
  foreach($workers as $w)if(is_resource($w['proc'])){proc_terminate($w['proc']);proc_close($w['proc']);}
 }
}
echo 'Day1 concurrent idempotency: 3 operation races passed.'.PHP_EOL;
