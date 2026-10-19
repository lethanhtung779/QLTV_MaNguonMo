<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$balance=array_sum(array_column((new Library\Repositories\FineRepository($db))->accounts(),'balance'));
echo json_encode(['database'=>$db->query('SELECT DATABASE()')->fetchColumn(),'balance'=>$balance,'formatted'=>number_format($balance,0,',','.').'đ']);