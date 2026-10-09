<?php
declare(strict_types=1);
namespace Library\Services;
use RuntimeException;
final class LoginThrottle
{
    public function __construct(private readonly ?string $directory=null) {}
    public function check(string $email): void { $this->access($email,'check'); }
    public function failure(string $email): void { $this->access($email,'failure'); }
    public function clear(string $email): void { $this->access($email,'clear'); }
    private function access(string $email,string $operation): void
    {
        $directory=$this->directory ?? dirname(__DIR__,2).'/storage/cache/login_attempts';
        if (!is_dir($directory) && !mkdir($directory,0770,true) && !is_dir($directory)) throw new RuntimeException('Không thể kiểm tra đăng nhập lúc này.');
        // Persist only counters; never store email, password, token or session ID.
        $key=hash('sha256',mb_strtolower(trim($email)).'|'.($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $handle=fopen($directory.'/'.$key.'.json','c+');
        if (!$handle) throw new RuntimeException('Không thể kiểm tra đăng nhập lúc này.');
        try {
            if (!flock($handle,LOCK_EX)) throw new RuntimeException('Không thể kiểm tra đăng nhập lúc này.');
            $state=json_decode(stream_get_contents($handle) ?: '{}',true);
            $state=is_array($state)?$state:[];
            $now=time();
            if ((int)($state['until'] ?? 0)<=$now && $now-(int)($state['started'] ?? 0)>=300) $state=[];
            $blocked=(int)($state['until'] ?? 0)>$now;
            if ($operation==='check' && $blocked) throw new RuntimeException('Bạn đã thử đăng nhập quá nhiều lần. Hãy chờ vài phút rồi thử lại.');
            if ($operation==='clear') $state=[];
            if ($operation==='failure') {
                $state['started'] ??= $now;
                $state['count']=(int)($state['count'] ?? 0)+1;
                if ($state['count']>=5) $state['until']=$now+300;
            }
            rewind($handle); ftruncate($handle,0); fwrite($handle,json_encode($state,JSON_THROW_ON_ERROR)); fflush($handle);
        } finally { flock($handle,LOCK_UN);fclose($handle); }
    }
}
