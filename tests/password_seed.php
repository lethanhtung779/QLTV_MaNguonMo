<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$root=dirname(__DIR__);$envHash=hash_file('sha256',$root.'/.env');$checks=0;
$assert=function(bool $v,string $label)use(&$checks){if(!$v)throw new RuntimeException('FAIL: '.$label);$checks++;echo 'PASS: '.$label.PHP_EOL;};
$run=function(string $script,array $overrides)use($root){$p=proc_open([PHP_BINARY,$root.'/database/'.$script],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,array_merge(getenv(),$overrides));if(!is_resource($p))throw new RuntimeException('Cannot launch seed');stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return proc_close($p);};
$email='audit-seed-'.bin2hex(random_bytes(5)).'@example.test';$long='Aa9'.str_repeat('x',70);
$before=(int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$assert($run('seed.php',['SEED_ADMIN_EMAIL'=>$email,'SEED_ADMIN_PASSWORD'=>$long])!==0,'Admin seed rejects password over 72 bytes');
$assert((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()===$before,'Rejected admin seed leaves users unchanged');
$assert($run('seed_demo.php',['APP_ENV'=>'local','SEED_DEMO_DATA'=>'true','SEED_DEMO_LIBRARIAN_PASSWORD'=>$long,'SEED_DEMO_READER_PASSWORD'=>$long])!==0,'Demo seed rejects overlong secret before database writes');
$assert((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()===$before,'Rejected demo seed leaves users unchanged');
$assert(hash_equals($envHash,hash_file('sha256',$root.'/.env')),'Seed rejection preserves private env file');
$bounded=substr($long,0,72);$assert($run('seed.php',['SEED_ADMIN_EMAIL'=>$email,'SEED_ADMIN_PASSWORD'=>$bounded])===0,'Admin seed accepts exact 72-byte password');
$user=(new Library\Repositories\UserRepository($db))->findByEmail($email);$assert($user!==null&&password_verify($bounded,$user['password_hash']),'Seeded boundary password is stored correctly');
echo 'Seed password checks: '.$checks.' passed.'.PHP_EOL;