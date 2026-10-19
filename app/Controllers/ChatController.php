<?php
declare(strict_types=1);
namespace Library\Controllers;
use Library\Core\Csrf;
use Library\Core\Database;
use Library\Core\Config;
use Library\Services\ChatService;
use Library\Services\ChatRateLimiter;
use Throwable;
final class ChatController
{
 public function handle(string $method,string $path):void {
  header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('Vary: Cookie');
  try {
   if($method==='GET'&&$path==='/chat/state'){if(!(new ChatRateLimiter())->allow('chat-state|'.Config::get('DB_NAME').'|'.($_SERVER['REMOTE_ADDR']??'unknown'),60,60)){header('Retry-After: 60');$this->json(['error'=>'Bạn gửi quá nhanh. Hãy chờ một phút.'],429);return;}$this->json((new ChatService(Database::connection()))->state());return;}
   if($method!=='POST'||!in_array($path,['/chat/message','/chat/reset'],true)){$this->json(['error'=>'Yêu cầu không hỗ trợ.'],405);return;}
   if((int)($_SERVER['CONTENT_LENGTH']??0)>6000){$this->json(['error'=>'Câu hỏi quá dài.'],413);return;}
   $raw=file_get_contents('php://input',false,null,0,6001);if(!is_string($raw)||strlen($raw)>6000){$this->json(['error'=>'Câu hỏi quá dài.'],413);return;}
   $data=json_decode($raw,true);if(!is_array($data)){$this->json(['error'=>'Định dạng yêu cầu không hợp lệ.'],400);return;}
   if(!Csrf::valid($data['_csrf']??null)){$this->json(['error'=>'Phiên chat đã hết hạn. Tải lại trang để tiếp tục.'],419);return;}
   $key=Config::get('DB_NAME').'|'.($_SERVER['REMOTE_ADDR']??'unknown');if(!(new ChatRateLimiter())->allow('chat|'.$key,30,60)){header('Retry-After: 60');$this->json(['error'=>'Bạn gửi quá nhanh. Hãy chờ một phút rồi thử lại.'],429);return;}
   $service=new ChatService(Database::connection());if($path==='/chat/reset'){$service->reset();$this->json($service->state());return;}
   $message=$data['message']??null;if(!is_string($message)||!mb_check_encoding($message,'UTF-8')||str_contains($message,"\0")||mb_strlen($message)>1000||trim($message)===''){$this->json(['error'=>'Câu hỏi cần từ 1 đến 1000 ký tự.'],422);return;}
   $this->json($service->reply(trim($message),($data['use_ai']??false)===true));
  }catch(Throwable $e){error_log('Chat request failed: '.get_class($e));$this->json(['error'=>'Trợ lý chưa thể xử lý lúc này. Vui lòng thử lại sau.'],503);}
 }
 private function json(array $data,int $status=200):void{if ($status===200 && isset($_SESSION['_chat']['identity'])) $data['scope']=hash('sha256',$_SESSION['_chat']['identity'].'|'.Csrf::token());http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);}
}
