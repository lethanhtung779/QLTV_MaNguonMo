<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$f=json_decode(file_get_contents(dirname(__DIR__).'/storage/http_qa.json'),true);$uid=(int)$f['reader']['id'];$rid=(int)$f['reader']['reader_id'];$actor=(int)$f['admin']['id'];
$tag='QAREM-'.bin2hex(random_bytes(4));
$book=(new \Library\Repositories\BookRepository($db))->create(['book_code'=>$tag,'title'=>'Reminder QA','author'=>'Reminder QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>2026,'total_copies'=>1,'description'=>null,'cover_url'=>null]);
$copy=(int)$db->query('SELECT id FROM book_copies WHERE book_id='.$book)->fetchColumn();$service=new \Library\Services\LoanService($db,5000);
$code=$service->checkout($rid,[$copy],$actor,date('Y-m-d'),date('Y-m-d',strtotime('+1 day')));
$item=(int)$db->query('SELECT li.id FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.loan_code='.$db->quote($code))->fetchColumn();
$rem=new \Library\Services\ReminderService($db);$rem->synchronize($uid);$rem->synchronize($uid);
$count=(int)$db->query('SELECT COUNT(*) FROM notifications WHERE deduplication_key LIKE '.$db->quote('due-reminder:'.$item.':%'))->fetchColumn();
if($count!==1)throw new RuntimeException('FAIL: due reminder dedupe');echo "PASS: due-soon reminders deduplicated.\n";
$db->exec('UPDATE loan_items SET borrowed_at=DATE_SUB(CURRENT_DATE,INTERVAL 3 DAY),due_date=DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY) WHERE id='.$item);
$rem->synchronize($uid);$rem->synchronize($uid);$count=(int)$db->query('SELECT COUNT(*) FROM notifications WHERE deduplication_key LIKE '.$db->quote('overdue-reminder:'.$item.':%'))->fetchColumn();
if($count!==1)throw new RuntimeException('FAIL: overdue reminder dedupe');echo "PASS: overdue reminders deduplicated.\n";
$notice=(int)$db->query('SELECT id FROM notifications WHERE deduplication_key LIKE '.$db->quote('overdue-reminder:'.$item.':%'))->fetchColumn();
if((new \Library\Repositories\NotificationRepository($db))->markRead($notice,$actor))throw new RuntimeException('FAIL: other user notification');echo "PASS: notification ownership enforced.\n";
try{$service->renew($item,$uid,$rid+999999);throw new LogicException('FAIL: other reader renew');}catch(RuntimeException $e){if($e instanceof PDOException)throw $e;echo "PASS: renewal ownership enforced.\n";}
$service->returnBook($item,$actor);

$borrower=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag.'-BORROW','full_name'=>'QA reservation borrower','phone'=>null,'email'=>null,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
$code=$service->checkout($borrower,[$copy],$actor,date('Y-m-d'),date('Y-m-d',strtotime('+1 day')));
$item=(int)$db->query('SELECT li.id FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.loan_code='.$db->quote($code))->fetchColumn();
$service->reserve($rid,$book,$uid);$service->returnBook($item,$actor);
$selected=array_values(array_filter((new \Library\Repositories\LoanRepository($db))->availableCopies(),fn($row)=>(int)$row['id']===$copy));
if(count($selected)!==1 || empty($selected[0]['reserved_card']))throw new RuntimeException('FAIL: reserved copy not available to staff form');
file_put_contents(dirname(__DIR__).'/storage/reservation_qa.json',json_encode(['book_id'=>$book,'copy_id'=>$copy,'copy_code'=>$selected[0]['copy_code'],'reader_id'=>$rid]));
echo "PASS: ready reserved copy appears in staff checkout choices with reader card.\n";
