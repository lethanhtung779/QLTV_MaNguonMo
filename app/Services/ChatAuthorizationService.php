<?php
declare(strict_types=1);
namespace Library\Services;
use PDO;
use Library\Core\Permissions;
use Library\Repositories\UserRepository;
use Library\Repositories\ReaderRepository;
final class ChatAuthorizationService
{
 public function __construct(private readonly PDO $db) {}
 public function user(): ?array {
  $session=current_user();if(!$session)return null;
  $record=(new UserRepository($this->db))->findById((int)($session['id']??0));
  if(!$record||$record['role']!==($session['role']??'')||!is_string($session['credential_version']??null)||!hash_equals(hash('sha256',$record['password_hash']),$session['credential_version'])){unset($_SESSION['user'],$_SESSION['_chat']);return null;}
  $user=['id'=>(int)$record['id'],'role'=>$record['role'],'credential_version'=>$session['credential_version']];
  if($record['role']==='reader'){$reader=(new ReaderRepository($this->db))->findByUser((int)$record['id']);if($reader)$user['reader_id']=(int)$reader['id'];}
  return $user;
 }
 public function allows(?array $user,string $permission): bool {return $user!==null&&Permissions::allows($user,$permission);}
 public function readerId(?array $user): ?int {return ($user['role']??'')==='reader'&&isset($user['reader_id'])?(int)$user['reader_id']:null;}
 public function identity(?array $user):string{return $user?hash('sha256',$user['id'].'|'.$user['role'].'|'.$user['credential_version']):'guest';}
}
