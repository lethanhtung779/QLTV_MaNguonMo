<?php
declare(strict_types=1);
namespace Library\Controllers;
use Library\Core\Permissions;
use Library\Core\Pagination;
use Library\Repositories\ActivityRepository;
use InvalidArgumentException;
use Throwable;
final class StaffController extends Controller
{
    public function index(): void {
        require_permission('users.manage');
        $this->render('admin/users',['title'=>'Nhân viên','staff'=>$this->db->query("SELECT id,name,email,role,is_active,last_login_at FROM users WHERE role IN ('admin','librarian') ORDER BY id")->fetchAll()]);
    }
    public function save(): never {
        $actor=require_permission('users.manage'); validate_csrf();
        try {
            $id=request_int('id',0); $name=request_string('name',160,true);
            $email=mb_strtolower(request_string('email',190,true));
            $role=request_string('role',32,true); $active=request_int('is_active',0,1);
            $password=request_password('password');
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || !in_array($role,['admin','librarian'],true)) throw new InvalidArgumentException('Email hoặc vai trò không hợp lệ.');
            if (($id===0 || $password!=='') && (strlen($password)<12 || !preg_match('/[a-z]/i',$password) || !preg_match('/[0-9]/',$password))) throw new InvalidArgumentException('Mật khẩu cần ít nhất 12 ký tự, gồm chữ và số.');
            $this->db->beginTransaction();
            // Lock every administrator in a stable order, so two concurrent changes cannot remove the last one.
            $admins=$this->db->query("SELECT id,is_active FROM users WHERE role='admin' ORDER BY id FOR UPDATE")->fetchAll();
            if ($id>0) {
                $s=$this->db->prepare("SELECT id,role FROM users WHERE id=? AND role IN ('admin','librarian') FOR UPDATE"); $s->execute([$id]); $old=$s->fetch();
                if (!$old) throw new InvalidArgumentException('Không tìm thấy nhân viên.');
                if ($id===(int)$actor['id'] && (!$active || $role!=='admin')) throw new InvalidArgumentException('Không thể khóa hoặc hạ quyền tài khoản đang sử dụng.');
                $remaining=count(array_filter($admins,fn($a)=>(int)$a['id']!==$id && (int)$a['is_active']===1));
                if ($old['role']==='admin' && (!$active || $role!=='admin') && !$remaining) throw new InvalidArgumentException('Hệ thống phải có ít nhất một quản trị viên hoạt động.');
                $this->db->prepare('UPDATE users SET name=?,email=?,role=?,is_active=? WHERE id=?')->execute([$name,$email,$role,$active,$id]);
                if ($password!=='') $this->db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
            } else {
                $this->db->prepare('INSERT INTO users(name,email,password_hash,role,is_active) VALUES(?,?,?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$active]);
                $id=(int)$this->db->lastInsertId();
            }
            (new ActivityRepository($this->db))->log((int)$actor['id'],'staff_saved','user',$id,'Cập nhật nhân viên: '.$name.'; vai trò '.$role.'; trạng thái '.$active);
            $this->db->commit(); $this->success('Đã lưu nhân viên. Quyền và trạng thái có hiệu lực từ yêu cầu kế tiếp.','/admin/users');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($e instanceof InvalidArgumentException?$e->getMessage():'Không thể lưu nhân viên. Kiểm tra email đã tồn tại.','/admin/users');
        }
    }
    public function permissions(): void {
        require_permission('roles.manage');
        $users=$this->db->query("SELECT id,name,email FROM users WHERE role='librarian' ORDER BY name")->fetchAll();
        $id=(int)(query_string('user')?:($users[0]['id']??0));
        $s=$this->db->prepare("SELECT id,name,role FROM users WHERE id=? AND role='librarian'"); $s->execute([$id]); $target=$s->fetch();
        $granted=[]; if ($target) foreach (Permissions::LABELS as $code=>$label) $granted[$code]=Permissions::allows($target,$code);
        $this->render('admin/permissions',['title'=>'Phân quyền','staff'=>$users,'target'=>$target,'granted'=>$granted,'labels'=>Permissions::LABELS]);
    }
    public function savePermissions(): never {
        $actor=require_permission('roles.manage'); validate_csrf();
        try {
            $id=request_int('user_id',1); $codes=$_POST['permissions']??[];
            if (!is_array($codes)) throw new InvalidArgumentException('Danh sách quyền không hợp lệ.');
            foreach ($codes as $code) if (!is_string($code) || !isset(Permissions::LABELS[$code]) || in_array($code,Permissions::ADMIN_ONLY,true)) throw new InvalidArgumentException('Quyền quản trị chỉ dành cho Admin.');
            $this->db->beginTransaction();
            $s=$this->db->prepare("SELECT id FROM users WHERE id=? AND role='librarian' FOR UPDATE"); $s->execute([$id]);
            if (!$s->fetchColumn()) throw new InvalidArgumentException('Chỉ cấp quyền nghiệp vụ cho tài khoản Thủ thư.');
            $s=$this->db->prepare('INSERT INTO user_permissions(user_id,permission_code,allowed) VALUES(?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)');
            foreach (Permissions::LABELS as $code=>$label) $s->execute([$id,$code,(int)in_array($code,$codes,true)]);
            (new ActivityRepository($this->db))->log((int)$actor['id'],'permissions_updated','user',$id,'Quyền được cấp: '.implode(', ',$codes));
            $this->db->commit(); $this->success('Đã cập nhật quyền nhân viên.','/admin/permissions?user='.$id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($e instanceof InvalidArgumentException?$e->getMessage():'Không thể lưu quyền.','/admin/permissions');
        }
    }
    public function activity(): void {
        require_permission('audit.view'); $q=query_string('q');
        $where = '(a.action LIKE ? OR a.details LIKE ?';
        $params = ['%'.$q.'%', '%'.$q.'%'];
        $translated = [];
        if ($q !== '') foreach (ui_action_labels() as $code => $label) {
            if (mb_stripos($label, $q) !== false) $translated[] = $code;
        }
        if ($translated) {
            $where .= ' OR a.action IN ('.implode(',', array_fill(0, count($translated), '?')).')';
            $params = array_merge($params, $translated);
        }
        $where .= ')';
        $s=$this->db->prepare('SELECT COUNT(*) FROM activity_logs a WHERE '.$where);
        $s->execute($params); $pagination=new Pagination((int)$s->fetchColumn(),30);
        $s=$this->db->prepare('SELECT a.*,u.name AS actor FROM activity_logs a LEFT JOIN users u ON u.id=a.user_id WHERE '.$where.' ORDER BY a.id DESC LIMIT '.$pagination->perPage.' OFFSET '.$pagination->offset);
        $s->execute($params);
        $this->render('admin/activity',['title'=>'Nhật ký hoạt động','entries'=>$s->fetchAll(),'pagination'=>$pagination,'search'=>$q]);
    }
}
