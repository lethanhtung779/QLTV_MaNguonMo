<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Database;
$db=Database::connection();
$source=(string)$db->query('SELECT DATABASE()')->fetchColumn();
$target='paper_page_test_'.date('Ymd_His').'_'.bin2hex(random_bytes(2));
$db->exec('CREATE DATABASE `'.$target.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/',$table)) throw new RuntimeException('Invalid table name');
        $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
        $ddl=preg_replace('/^CREATE TABLE `'.preg_quote($table,'/').'`/','CREATE TABLE `'.$target.'`.`'.$table.'`',$ddl,1);
        // Explicitly target the isolated schema for referenced tables too.
        $ddl=preg_replace('/REFERENCES `([^`]+)`/','REFERENCES `'.$target.'`.`$1`',$ddl);
        $db->exec($ddl);
        $columns=[];
        foreach ($db->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll() as $column) {
            if (!str_contains($column['Extra'],'GENERATED')) $columns[]='`'.$column['Field'].'`';
        }
        $list=implode(',',$columns);
        $db->exec('INSERT INTO `'.$target.'`.`'.$table.'` ('.$list.') SELECT '.$list.' FROM `'.$source.'`.`'.$table.'`');
    }
} finally { $db->exec('SET FOREIGN_KEY_CHECKS=1'); }
file_put_contents(__DIR__.'/../storage/test_database_name.txt',$target);
echo 'PASS: isolated database prepared: '.$target.PHP_EOL;
