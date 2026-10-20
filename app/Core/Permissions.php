<?php
declare(strict_types=1);
namespace Library\Core;
final class Permissions
{
    public const LABELS = [
        'books.view'=>'Xem sách','books.create'=>'Thêm sách','books.update'=>'Sửa sách','books.deactivate'=>'Ngừng bán sách',
        'orders.view'=>'Xem đơn hàng','orders.create'=>'Tạo đơn hàng','orders.update'=>'Cập nhật đơn hàng','orders.cancel'=>'Hủy đơn hàng',
        'customers.view'=>'Xem khách hàng','customers.create'=>'Tạo khách hàng','customers.update'=>'Sửa khách hàng',
        'payments.view'=>'Xem thanh toán','payments.collect'=>'Thu tiền',
        'reports.view'=>'Xem báo cáo',
        'users.manage'=>'Quản lý nhân viên','roles.manage'=>'Cấp quyền nhân viên',
        'settings.manage'=>'Cài đặt hệ thống','audit.view'=>'Xem nhật ký'
    ];
    public const ADMIN_ONLY = ['users.manage','roles.manage','settings.manage','audit.view'];
    public static function allows(array $user, string $permission): bool
    {
        if (!isset(self::LABELS[$permission])) return false;
        $staffRoles = ['admin','nhan_vien'];
        if (!in_array($user['role']??'', $staffRoles,true)) return false;
        if ($user['role']==='admin') return true;
        if (in_array($permission,self::ADMIN_ONLY,true)) return false;
        $s=Database::connection()->prepare('SELECT allowed FROM user_permissions WHERE user_id=? AND permission_code=?');
        $s->execute([(int)$user['id'],$permission]); $override=$s->fetchColumn();
        if ($override!==false) return (bool)$override;
        $s=Database::connection()->prepare('SELECT 1 FROM role_permissions WHERE role=? AND permission_code=?');
        $s->execute([$user['role'],$permission]); return (bool)$s->fetchColumn();
    }
    public static function route(string $method,string $path): ?string
    {
        if (!str_starts_with($path,'/admin')) return null;
        if ($path==='/admin') return 'reports.view';
        if ($path==='/admin/users') return 'users.manage';
        if ($path==='/admin/permissions') return 'roles.manage';
        if ($path==='/admin/activity') return 'audit.view';
        if (str_starts_with($path,'/admin/settings')) return 'settings.manage';
        if (str_starts_with($path,'/admin/statistics')) return 'reports.view';
        if (str_starts_with($path,'/admin/orders')) {
            if ($method==='GET') return 'orders.view';
            if (str_ends_with($path,'/cancel')) return 'orders.cancel';
            if (str_ends_with($path,'/status')) return 'orders.update';
            return 'orders.create';
        }
        if (str_starts_with($path,'/admin/payments')) return $method==='GET'?'payments.view':'payments.collect';
        if (str_starts_with($path,'/admin/customers')) {
            if ($method==='GET') return 'customers.view';
            return (is_scalar($_POST['id']??null) && (int)$_POST['id']>0)?'customers.update':'customers.create';
        }
        if (str_starts_with($path,'/admin/books')) {
            if ($method==='GET') return 'books.view';
            if ($path==='/admin/books/deactivate') return 'books.deactivate';
            return (is_scalar($_POST['id']??null) && (int)$_POST['id']>0)?'books.update':'books.create';
        }
        return null;
    }
}
