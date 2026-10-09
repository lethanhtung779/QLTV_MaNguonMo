<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap/autoload.php';
use Library\Core\Session;
use Library\Core\Csrf;
$checks=0;
$assert=function(bool $ok,string $label) use(&$checks) { if(!$ok) throw new RuntimeException('FAIL: '.$label);$checks++; };
Session::start('qa_security_session');
$assert(ini_get('session.use_strict_mode')==='1','Strict session IDs enabled');
$before=session_id();Session::regenerate();$assert(session_id()!==$before,'Session ID rotates');
$token=Csrf::token();$assert(Csrf::valid($token),'CSRF accepts the session token');$assert(!Csrf::valid('wrong-token'),'CSRF rejects a wrong token');
$assert(!Csrf::valid(['array']),'CSRF rejects array input');
$cookie=session_get_cookie_params();$assert($cookie['httponly']===true && $cookie['samesite']==='Lax','Cookie flags');
$_SESSION['user']=['id'=>1];$_SESSION['_last_activity']=time()-1900;session_write_close();Session::start('qa_security_session');
$assert(!isset($_SESSION['user']),'Idle timeout removes authentication');
$assert(!Csrf::valid($token),'Timeout invalidates the old CSRF token');
$assert(e('<script>alert(1)</script>')==='&lt;script&gt;alert(1)&lt;/script&gt;','XSS escaped');
$_POST=['id'=>['1']];try {request_int('id');$assert(false,'Invalid integer rejected');}catch(InvalidArgumentException){$assert(true,'Invalid integer rejected');}
$_GET=['page'=>['999']];$page=new \Library\Core\Pagination(25,12);$assert($page->page===1 && $page->pages===3,'Pagination rejects array input');
$_GET=['page'=>'999'];$page=new \Library\Core\Pagination(25,12);$assert($page->page===3 && $page->offset===24,'Pagination clamps out-of-range page');
$assert(\Library\Services\LoanReportCsvExporter::safeCell("\t=1+1")==="'\t=1+1",'CSV protects formulas after whitespace');
$dir=sys_get_temp_dir().'/paper-page-throttle-'.bin2hex(random_bytes(4));$limiter=new \Library\Services\LoginThrottle($dir);
$email='qa@example.test';$limiter->check($email);for($i=0;$i<5;$i++)$limiter->failure($email);
try{(new \Library\Services\LoginThrottle($dir))->check($email);$assert(false,'Throttle survives a new instance');}catch(RuntimeException){$assert(true,'Throttle survives a new instance');}
$limiter->clear($email);$limiter->check($email);$assert(true,'Successful login clears throttle');
foreach(glob($dir.'/*.json') as $file)unlink($file);rmdir($dir);
Session::destroy();echo "Security/helper tests: $checks assertions passed.\n";
