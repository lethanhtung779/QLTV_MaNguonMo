<?php
declare(strict_types=1);
namespace Library\Core;
final class Permissions
{
    public const LABELS = [
        'books.view'=>'Xem sách và bản sao','books.create'=>'Thêm sách','books.update'=>'Sửa sách và bản sao',
        'books.deactivate'=>'Ngừng lưu hành sách','metadata.manage'=>'Quản lý tác giả, NXB, thể loại',
        'readers.view'=>'Tra cứu độc giả','readers.create'=>'Tạo độc giả','readers.update'=>'Sửa độc giả',
        'readers.suspend'=>'Khóa hoặc kích hoạt thẻ','loans.view'=>'Xem phiếu mượn',
        'loans.create'=>'Lập phiếu mượn','loans.return'=>'Nhận trả','loans.renew'=>'Gia hạn',
        'reservations.manage'=>'Xử lý đặt trước','fines.view'=>'Xem phí và biên nhận',
        'fines.collect'=>'Thu phí','fines.waive'=>'Miễn giảm phí','reports.view'=>'Xem và xuất báo cáo',
        'users.manage'=>'Quản lý nhân viên','roles.manage'=>'Cấp quyền nhân viên',
        'settings.manage'=>'Cài đặt chính sách','audit.view'=>'Xem nhật ký'
    ];
    public const ADMIN_ONLY = ['users.manage','roles.manage','settings.manage','fines.waive','audit.view'];
    public static function allows(array $user, string $permission): bool
    {
        if (!isset(self::LABELS[$permission]) || !in_array($user['role']??'', ['admin','librarian'],true)) return false;
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
        if (str_starts_with($path,'/admin/fines')) return $method==='GET'?'fines.view':($path==='/admin/fines/waive'?'fines.waive':'fines.collect');
        if ($path==='/admin/reservations') return 'reservations.manage';
        if (str_starts_with($path,'/admin/loans')) return $method==='GET'?'loans.view':match($path) {
            '/admin/loans/return'=>'loans.return','/admin/loans/return-all'=>'loans.return','/admin/loans/renew'=>'loans.renew',default=>'loans.create'
        };
        if (str_starts_with($path,'/admin/reviews')) return $method==='GET'?'books.view':'books.update';
        if (str_starts_with($path,'/admin/inventory')) return $method==='GET'?'books.view':'books.update';
        if ($path === '/admin/print-labels') return 'books.view';
        if (preg_match('#^/admin/(categories|authors|publishers)#',$path)) return 'metadata.manage';
        if (str_starts_with($path,'/admin/readers')) {
            if ($method==='GET') return 'readers.view';
            if ($path==='/admin/readers/delete') return 'readers.suspend';
            return (is_scalar($_POST['id']??null) && (int)$_POST['id']>0)?'readers.update':'readers.create';
        }
        if ($path==='/admin/metadata-quality') return 'books.view';
        if ($path==='/admin/metadata-quality/isbn') return 'books.update';
        if (str_starts_with($path,'/admin/books/import')) return $method==='GET'?'books.view':'books.create';
        if (str_starts_with($path,'/admin/books')) {
            if ($method==='GET') return 'books.view';
            if ($path==='/admin/books/delete') return 'books.deactivate';
            if (str_ends_with($path,'/copies')) return 'books.update';
            return (is_scalar($_POST['id']??null) && (int)$_POST['id']>0)?'books.update':'books.create';
        }
        return null;
    }
}
