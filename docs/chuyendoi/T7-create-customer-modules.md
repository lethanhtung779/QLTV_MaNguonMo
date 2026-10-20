# Task T7: Tạo `CustomerController`, `CustomerRepository`

**Mục tiêu:** Xây dựng module quản lý khách hàng và tài khoản khách hàng.

## Các thay đổi chi tiết

### 1. `CustomerRepository`

| Phương thức | Mô tả |
|-------------|-------|
| `all($search, $limit, $offset)` | Lấy danh sách khách hàng |
| `find($id)` | Lấy chi tiết khách hàng |
| `findByUser($userId)` | Lấy khách hàng theo user_id |
| `create($data)` | Tạo khách hàng mới |
| `update($id, $data)` | Cập nhật thông tin khách hàng |

### 2. `CustomerController`

| Route | Phương thức | Action | Mô tả |
|-------|-------------|--------|-------|
| `/account` | GET | `dashboard` | Trang tài khoản khách hàng |
| `/account/orders` | GET | `orders` | Lịch sử đơn hàng |
| `/account/profile` | GET | `profile` | Thông tin cá nhân |
| `/account/profile` | POST | `updateProfile` | Cập nhật hồ sơ |
| `/admin/customers` | GET | `index` | Danh sách khách hàng (admin) |
| `/admin/customers` | POST | `store` | Tạo khách hàng (admin) |
| `/admin/customers/{id}` | PUT | `update` | Sửa khách hàng (admin) |

## Definition of Done

- [ ] Tạo 2 file: `CustomerController.php`, `CustomerRepository.php`
- [ ] Trang tài khoản khách hàng (`/account`)
- [ ] Lịch sử đơn hàng (`/account/orders`)
- [ ] Quản lý khách hàng admin (`/admin/customers`)
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
