<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$checks=0;
function verify(bool $value,string $label):void{global $checks;if(!$value)throw new RuntimeException('FAIL: '.$label);$checks++;}
$tag='RETURNALL-'.bin2hex(random_bytes(5));$actor=(int)$db->query("SELECT id FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetchColumn();
$book=(new \Library\Repositories\BookRepository($db))->create(['book_code'=>$tag,'title'=>'Kiểm thử trả toàn bộ','author'=>'QA','isbn'=>null,'category_id'=>null,'publisher'=>null,'publication_year'=>null,'description'=>null,'total_copies'=>2,'cover_url'=>null]);
$reader=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>null,'card_number'=>$tag,'full_name'=>'QA atomic return','phone'=>null,'email'=>null,'address'=>null,'expires_on'=>null]);
$copies=array_map('intval',$db->query('SELECT id FROM book_copies WHERE book_id='.$book.' ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
$service=new \Library\Services\LoanService($db,5000);$code=$service->checkout($reader,$copies,$actor,date('Y-m-d'),date('Y-m-d',strtotime('+14 days')),'Atomic return test');
$s=$db->prepare('SELECT id FROM loans WHERE loan_code=?');$s->execute([$code]);$loan=(int)$s->fetchColumn();
$db->prepare("UPDATE book_copies SET status='damaged' WHERE id=?")->execute([$copies[1]]);
$failed=false;try{$service->returnAll($loan,$actor);}catch(RuntimeException $e){$failed=true;}
verify($failed,'Conflicting second copy rejects entire return');
verify((int)$db->query('SELECT COUNT(*) FROM loan_items WHERE loan_id='.$loan.' AND returned_at IS NULL')->fetchColumn()===2,'First item return rolled back');
verify($db->query('SELECT status FROM book_copies WHERE id='.$copies[0])->fetchColumn()==='on_loan','First copy inventory rolled back');
$db->prepare("UPDATE book_copies SET status='on_loan' WHERE id=?")->execute([$copies[1]]);
$service->returnAll($loan,$actor,'worn');
verify((int)$db->query('SELECT COUNT(*) FROM loan_items WHERE loan_id='.$loan.' AND returned_at IS NULL')->fetchColumn()===0,'Successful return closes all items');
verify((int)$db->query('SELECT COUNT(*) FROM loan_items WHERE loan_id='.$loan.' AND return_condition="worn"')->fetchColumn()===2,'Each item retains condition snapshot');
verify((int)$db->query('SELECT COUNT(*) FROM book_copies WHERE book_id='.$book.' AND status="available"')->fetchColumn()===2,'Inventory restored');
$failed=false;try{$service->returnAll($loan,$actor);}catch(RuntimeException $e){$failed=true;}
verify($failed,'Duplicate entire return rejected');
echo "Return-all atomic MySQL: {$checks} assertions passed.\n";
