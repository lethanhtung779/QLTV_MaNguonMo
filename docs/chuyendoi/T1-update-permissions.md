# Task T1: Cập nhật `app/Core/Permissions.php` – quyền bán sách

**Mục tiêu:** Thay thế toàn bộ hệ thống quyền quản lý thư viện bằng quyền bán sách phù hợp với 3 vai trò: `admin`, `nhan_vien`, `nguoi_mua`.

## Các thay đổi chi tiết

### 1. Cập nhật `LABELS` (danh sách quyền)

Thay thế bằng các quyền sau:

| Mã quyền | Nhãn hiển thị | Mô tả |
|----------|--------------|-------|
| `books.view` | Xem sách | Tra cứu, xem chi tiết sách |
| `books.create` | Thêm sách | Thêm sách mới vào catalog |
| `books.update` | Sửa sách | Cập nhật thông tin sách, giá, tồn kho |
| `books.deactivate` | Ngừng bán | Ngừng kinh doanh sách |
| `orders.view` | Xem đơn hàng | Xem danh sách đơn hàng |
| `orders.create` | Tạo đơn hàng | Tạo đơn hàng mới |
| `orders.update` | Cập nhật đơn hàng | Thay đổi trạng thái đơn hàng |
| `orders.cancel` | Hủy đơn hàng | Hủy đơn hàng và hoàn lại tồn kho |
| `customers.view` | Xem khách hàng | Xem danh sách khách hàng |
| `customers.create` | Tạo khách hàng | Tạo tài khoản khách hàng mới |
| `customers.update` | Sửa khách hàng | Cập nhật thông tin khách hàng |
| `payments.view` | Xem thanh toán | Xem lịch sử thanh toán |
| `payments.collect` | Thu tiền | Ghi nhận thanh toán đơn hàng |
| `reports.view` | Xem báo cáo | Xem báo cáo doanh thu |
| `users.manage` | Quản lý nhân viên | Thêm/sửa/xóa nhân viên |
| `roles.manage` | Cấp quyền | Phân quyền cho nhân viên |
| `settings.manage` | Cài đặt | Cấu hình hệ thống |
| `audit.view` | Xem nhật ký | Xem log hoạt động |

### 2. `ADMIN_ONLY` (chỉ admin mới được phép)

Giữ nguyên:
- `users.manage`
- `roles.manage`
- `settings.manage`
- `audit.view`

### 3. Hàm `route()` mapping route sang quyền

Cập nhật mapping cho các route `/admin/*`:

| Route | Phương thức | Quyền yêu cầu |
|-------|-------------|---------------|
| `/admin` | GET | `reports.view` |
| `/admin/users` | GET/POST | `users.manage` |
| `/admin/permissions` | GET/POST | `roles.manage` |
| `/admin/activity` | GET | `audit.view` |
| `/admin/settings` | GET/POST | `settings.manage` |
| `/admin/statistics` | GET | `reports.view` |
| `/admin/fines` | GET | `payments.view` |
| `/admin/orders` | GET | `orders.view` |
| `/admin/orders/create` | POST | `orders.create` |
| `/admin/orders/update` | POST | `orders.update` |
| `/admin/orders/cancel` | POST | `orders.cancel` |
| `/admin/customers` | GET | `customers.view` |
| `/admin/books` | GET | `books.view` |
| `/admin/books/create` | POST | `books.create` |
| `/admin/books/update` | POST | `books.update` |
| `/admin/books/deactivate` | POST | `books.deactivate` |

### 4. Điều kiện về vai trò

- `admin`: tự động được phép tất cả quyền (trừ khi kiểm tra rõ ràng)
- `nhan_vien`: được phép các quyền nghiệp vụ, không được `ADMIN_ONLY`
- `nguoi_mua`: không được truy cập `/admin/*`

## Definition of Done

- [ ] File `app/Core/Permissions.php` được cập nhật với `LABELS` mới
- [ ] `ADMIN_ONLY` giữ nguyên 4 quyền quản trị
- [ ] Hàm `route()` mapping đúng các route bán sách
- [ ] Kiểm tra vai trò `nguoi_mua` không thể truy cập `/admin/*`
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
