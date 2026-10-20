# Task T5: Sửa `AuthController` – hỗ trợ 3 vai trò, đăng ký `nguoi_mua`

**Mục tiêu:** Cập nhật AuthController để hỗ trợ 3 vai trò (admin, nhan_vien, nguoi_mua) và cho phép khách hàng đăng ký.

## Các thay đổi chi tiết

### 1. Đăng nhập (`login`)

- Hỗ trợ 3 vai trò: `admin`, `nhan_vien`, `nguoi_mua`
- Sau đăng nhập, điều hướng theo vai trò:
  - `admin` → `/admin`
  - `nhan_vien` → `/admin/orders`
  - `nguoi_mua` → `/account`

### 2. Đăng ký (`register`)

- Chỉ cho phép đăng ký với vai trò `nguoi_mua`
- Kiểm tra email trùng lặp
- Hash mật khẩu trước khi lưu
- Tạo luôn bảng ghi trong `customers` (liên kết với `users` qua `user_id`)

### 3. Điều hướng theo vai trò (`homeForRole`)

```php
private function homeForRole(string $role): string
{
    return match ($role) {
        'admin' => '/admin',
        'nhan_vien' => '/admin/orders',
        'nguoi_mua' => '/account',
        default => '/login',
    };
}
```

## Definition of Done

- [ ] `AuthController::login()` hỗ trợ 3 vai trò
- [ ] `AuthController::register()` tạo tài khoản `nguoi_mua` và bản ghi `customers`
- [ ] Điều hướng sau đăng nhập đúng theo vai trò
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
