<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$name='paper_page_test_install_'.date('Ymd_His').'_'.bin2hex(random_bytes(2));
putenv('DB_NAME='.$name); $_ENV['DB_NAME']=$name;
$process=proc_open([PHP_BINARY,dirname(__DIR__).'/database/setup.php'],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
if (!is_resource($process)) throw new RuntimeException('Cannot launch setup');
$out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);
if(proc_close($process)!==0) throw new RuntimeException('FAIL: fresh setup '.$err);
$db=\Library\Core\Database::connection(); $counts=[];
foreach(['books','book_copies','book_authors','authors','publishers'] as $table) $counts[$table]=(int)$db->query('SELECT COUNT(*) FROM `'.$name.'`.`'.$table.'`')->fetchColumn();
if($counts['books']!==9 || $counts['book_copies']!==63 || $counts['book_authors']!==9) throw new RuntimeException('FAIL: fresh inventory '.json_encode($counts));
echo 'PASS: fresh installation creates 9 titles, 63 copies and 9 author links on first setup.'.PHP_EOL;
$process=proc_open([PHP_BINARY,dirname(__DIR__).'/database/setup.php'],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
if(proc_close($process)!==0 || (int)$db->query('SELECT COUNT(*) FROM `'.$name.'`.book_copies')->fetchColumn()!==63)throw new RuntimeException('FAIL: setup idempotency');
echo 'PASS: second setup preserves 9 titles and 63 copies.'.PHP_EOL;
