<?php
declare(strict_types=1);
namespace Library\Controllers;
use Library\Repositories\UserRepository;
use Library\Repositories\ActivityRepository;
use Library\Core\Session;
use InvalidArgumentException;
use Throwable;
final class ProfileController extends Controller
{
    public function index(): void
    {
        $user=require_auth(['admin','librarian']);
        $this->render('admin/profile',['title'=>'Hồ sơ tài khoản','user'=>$user]);
    }
    public function save(bool $password=false): never
    {
        $user=require_auth(['admin','librarian']); validate_csrf();
        try {
            $repo=new UserRepository($this->db);
            $this->db->beginTransaction();
            if ($password) {
                $current=request_string('current_password',1024,true); $new=request_string('new_password',72,true); $confirm=request_string('password_confirmation',72,true);
                $record=$repo->findById((int)$user['id']);
                if (!$record || !password_verify($current,$record['password_hash'])) throw new InvalidArgumentException('Mật khẩu hiện tại không chính xác.');
                if (strlen($new)<12 || !preg_match('/[A-Z]/',$new) || !preg_match('/[a-z]/',$new) || !preg_match('/[0-9]/',$new) || !hash_equals($new,$confirm)) throw new InvalidArgumentException('Mật khẩu cần 12–72 byte, chữ hoa, chữ thường, số và xác nhận khớp.');
                $repo->updatePassword((int)$user['id'],password_hash($new,PASSWORD_DEFAULT));
                (new ActivityRepository($this->db))->log((int)$user['id'],'password_changed','user',(int)$user['id'],'Đổi mật khẩu');
            } else {
                $name=request_string('name',160,true); $email=mb_strtolower(request_string('email',190,true));
                if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Email không hợp lệ.');
                $repo->updateProfile((int)$user['id'],$name,$email);
                $_SESSION['user']['name']=$name; $_SESSION['user']['email']=$email;
            }
            $this->db->commit(); Session::regenerate();
            $this->success('Đã cập nhật tài khoản.','/profile');
        } catch (InvalidArgumentException $e) { if($this->db->inTransaction())$this->db->rollBack(); $this->fail($e->getMessage(),'/profile');
        } catch (Throwable $e) { if($this->db->inTransaction())$this->db->rollBack(); $this->fail('Không thể cập nhật; email có thể đã được sử dụng.','/profile'); }
    }
}
