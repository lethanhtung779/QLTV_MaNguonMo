<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$source=(string)$db->query('SELECT DATABASE()')->fetchColumn();
$file=$argv[1]??'';if(!is_file($file))throw new RuntimeException('Backup file required');
$target='paper_page_test_restore_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));$id=fn($v)=>chr(96).$v.chr(96);
$db->exec('CREATE DATABASE '.$id($target).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$db->exec('USE '.$id($target));
try{$db->exec(file_get_contents($file));}finally{$db->exec('SET FOREIGN_KEY_CHECKS=1');}
$results=[];$tables=$db->query('SHOW TABLES FROM '.$id($source))->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table){$read=fn($schema)=>$db->query('SELECT * FROM '.$id($schema).'.'.$id($table).' ORDER BY 1')->fetchAll();$before=$read($source);$after=$read($target);$same=hash_equals(hash('sha256',json_encode($before)),hash('sha256',json_encode($after)));$results[$table]=['count'=>count($after),'exact_rows'=>$same];echo ($same?'PASS: ':'FAIL: ').'restore exact rows including timestamps: '.$table.PHP_EOL;}
$e=['time'=>date(DATE_ATOM),'source'=>$source,'target'=>$target,'backup'=>realpath($file),'sha256'=>hash_file('sha256',$file),'results'=>$results];file_put_contents((getenv('QA_OUTPUT') ?: dirname(__DIR__).'/storage/backups/audit-20261011').'/restore-'.basename($file).'.json',json_encode($e,JSON_PRETTY_PRINT));
if(in_array(false,array_column($results,'exact_rows'),true))exit(1);