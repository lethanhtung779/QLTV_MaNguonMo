<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Permissions;
use Library\Core\Database;
$db=Database::connection();$checks=0;
function check(bool $value,string $label): void {global $checks;if (!$value) throw new RuntimeException('FAIL: '.$label);$checks++;}
$db->beginTransaction();
try {
    $id=(new \Library\Repositories\UserRepository($db))->create(['name'=>'Matrix test','email'=>'matrix-'.bin2hex(random_bytes(6)).'@example.test','password_hash'=>password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT),'role'=>'librarian']);
    $librarian=['id'=>$id,'role'=>'librarian'];$admin=['id'=>$id,'role'=>'admin'];$reader=['id'=>$id,'role'=>'reader'];
    $s=$db->prepare('INSERT INTO user_permissions(user_id,permission_code,allowed) VALUES(?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)');
    foreach (Permissions::LABELS as $code=>$label) {
        check(Permissions::allows($admin,$code),'Admin '.$code);
        check(!Permissions::allows($reader,$code),'Reader '.$code);
        check(Permissions::allows($librarian,$code)===!in_array($code,Permissions::ADMIN_ONLY,true),'Default librarian '.$code);
        $s->execute([$id,$code,0]);check(!Permissions::allows($librarian,$code),'Revoke '.$code);
        $s->execute([$id,$code,1]);check(Permissions::allows($librarian,$code)===!in_array($code,Permissions::ADMIN_ONLY,true),'Grant/administrative boundary '.$code);
    }
    check(!Permissions::allows($admin,'unknown.permission'),'Unknown permission fails closed');
    $reports=new \Library\Repositories\LoanRepository($db);
    $from=date('Y-m-d',strtotime('-90 days'));$to=date('Y-m-d');
    $total=$reports->reportRows($from,$to,'',1,0,true)[0];
    $first=$reports->reportRows($from,$to,'',20,0);$second=$reports->reportRows($from,$to,'',20,20);
    check($total>20 && count($first)===20 && count($second)>0,'Report count and second page');
    check($first[0]['loan_code']!==$second[0]['loan_code'],'Pages do not repeat first row');
    $loans=$reports->all('', '',20,0);check(count($loans)<=20,'Loan register result bounded');
    foreach (['inventory','fines'] as $kind) {
        $r=new \Library\Repositories\ReportRepository($db);check(count($r->rows($kind,20))<=20,'Bounded '.$kind);
        check($r->rows($kind,1,0,true)[0]===count($r->rows($kind,10001)),'Exact count '.$kind);
    }
    $db->rollBack();
    echo "Permissions/report MySQL: {$checks} assertions passed; temporary permissions rolled back.\n";
} catch(Throwable $e) {if ($db->inTransaction())$db->rollBack();throw $e;}
