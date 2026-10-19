<?php
declare(strict_types=1);
namespace Library\Services;
use RuntimeException;
final class ChatRateLimiter
{
 public function __construct(private readonly ?string $directory=null){}
 public function allow(string $key,int $limit=30,int $seconds=60):bool {
  $dir=$this->directory??dirname(__DIR__,2).'/storage/cache/chat_limits';if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir))throw new RuntimeException('Rate limiter unavailable');
  $f=fopen($dir.'/'.hash('sha256',$key).'.json','c+');if(!$f)throw new RuntimeException('Rate limiter unavailable');
  try{if(!flock($f,LOCK_EX))throw new RuntimeException('Rate limiter lock unavailable');$data=json_decode(stream_get_contents($f)?:'{}',true);$now=time();if(!is_array($data)||$now-(int)($data['start']??0)>=$seconds)$data=['start'=>$now,'count'=>0];$allowed=(int)$data['count']<$limit;if($allowed)$data['count']++;rewind($f);ftruncate($f,0);fwrite($f,json_encode($data,JSON_THROW_ON_ERROR));fflush($f);return $allowed;}finally{flock($f,LOCK_UN);fclose($f);}
 }
}
