<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
use Library\Core\Database;
use Library\Core\Config;
$db=Database::connection();$name=(string)Config::get('DB_NAME','');
if (!preg_match('/^[A-Za-z0-9_]+$/',$name)) throw new RuntimeException('Invalid database name');
$dir=dirname(__DIR__).'/storage/backups';
if (!is_dir($dir)&&!mkdir($dir,0700,true)) throw new RuntimeException('Cannot create backup directory');
$file=$dir.'/'.$name.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
$out=fopen($file,'xb');if (!$out) throw new RuntimeException('Cannot create backup');
$identifier=static fn(string $value): string=>chr(96).$value.chr(96);
try {
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
    fwrite($out,"-- Paper & Page consistent MySQL backup\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        if (!preg_match('/^[A-Za-z0-9_]+$/',$table)) throw new RuntimeException('Invalid table name');
        $ddl=$db->query('SHOW CREATE TABLE '.$identifier($table))->fetch(PDO::FETCH_NUM)[1];
        fwrite($out,$ddl.";\n");
        $columns=array_values(array_filter($db->query('SHOW COLUMNS FROM '.$identifier($table))->fetchAll(),fn($c)=>!preg_match('/\b(?:VIRTUAL|STORED) GENERATED\b/i', $c['Extra'])));
        $names=array_column($columns,'Field');$quoted=implode(',',array_map($identifier,$names));
        foreach ($db->query('SELECT '.$quoted.' FROM '.$identifier($table)) as $row) {
            $values=implode(',',array_map(fn($c)=>$row[$c]===null?'NULL':$db->quote((string)$row[$c]),$names));
            fwrite($out,'INSERT INTO '.$identifier($table).' ('.$quoted.') VALUES ('.$values.");\n");
        }
    }
    fwrite($out,"SET FOREIGN_KEY_CHECKS=1;\n");$db->commit();fclose($out);
    @chmod($file,0600);
    $checksum=hash_file('sha256',$file);
    if (!is_string($checksum)) throw new RuntimeException('Cannot checksum backup');
    $checksumFile=$file.'.sha256';
    $metadataFile=$file.'.json';
    file_put_contents($checksumFile,$checksum.'  '.basename($file).PHP_EOL,LOCK_EX);
    file_put_contents($metadataFile,json_encode([
        'created_at'=>date(DATE_ATOM),
        'database'=>$name,
        'mysql_version'=>(string)$db->query('SELECT VERSION()')->fetchColumn(),
        'tables'=>count($tables),
        'bytes'=>filesize($file),
        'sha256'=>$checksum,
        'restore_policy'=>'Restore only to a new isolated test schema unless production recovery is explicitly approved.',
    ],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
    @chmod($checksumFile,0600);@chmod($metadataFile,0600);
    echo 'PASS: consistent backup, SHA-256 and metadata saved privately to '.$file.PHP_EOL;
} catch(Throwable $e) {if ($db->inTransaction())$db->rollBack();fclose($out);throw $e;}
