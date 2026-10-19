<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();
$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();
$lock='paper-page-migrations:'.substr(hash('sha256',$name),0,32);
$q=$db->prepare('SELECT GET_LOCK(?,10)');$q->execute([$lock]);
if ((int)$q->fetchColumn()!==1) throw new RuntimeException('Another migration process holds the lock.');
try {
    // This command only upgrades an existing schema; it never seeds/recreates data.
    $applied=$db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files=glob(__DIR__.'/migrations/*.php');sort($files,SORT_STRING);
    foreach($files as $file){
        $version=pathinfo($file,PATHINFO_FILENAME);
        if(in_array($version,$applied,true))continue;
        $migration=require $file;
        if(!is_callable($migration))throw new RuntimeException('Invalid migration '.$version);
        $migration($db);
        $q=$db->prepare('INSERT INTO schema_migrations(version) VALUES(?)');$q->execute([$version]);
        echo 'APPLIED: '.$version.' on '.$name.PHP_EOL;
    }
    echo 'PASS: existing-schema migrations complete; no seed/reset on '.$name.PHP_EOL;
} finally {
    $q=$db->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);
}
