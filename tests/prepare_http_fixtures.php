<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection(); $out=[];
foreach(['admin','librarian','reader'] as $role) {
    $email='qa-final-'.$role.'-'.bin2hex(random_bytes(3)).'@example.test'; $password='Qa'.bin2hex(random_bytes(16)).'12';
    $id=(new \Library\Repositories\UserRepository($db))->create(['name'=>'QA '.$role,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$role]);
    $out[$role]=['id'=>$id,'email'=>$email,'password'=>$password];
    if($role==='reader') $out[$role]['reader_id']=(new \Library\Repositories\ReaderRepository($db))->create(['user_id'=>$id,'card_number'=>'QAHTTP-'.$id,'full_name'=>'QA Reader','phone'=>null,'email'=>$email,'address'=>null,'expires_on'=>date('Y-m-d',strtotime('+1 year'))]);
}
$out['book_id']=(int)$db->query('SELECT id FROM books WHERE is_active=1 ORDER BY id LIMIT 1')->fetchColumn();
file_put_contents(dirname(__DIR__).'/storage/http_qa.json',json_encode($out));
echo "PASS: isolated HTTP fixtures created; credentials kept in ignored storage.\n";