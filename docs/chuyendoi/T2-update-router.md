# Task T2: Cập nhật `app/Core/Router.php` – route bán sách

**Mục tiêu:** Thay thế toàn bộ routing quản lý thư viện bằng routing bán sách, loại bỏ các route không cần thiết (mượn/trả, phí phạt, đặt trước, kiểm kê, chat AI).

## Các thay đổi chi tiết

### 1. Loại bỏ các route sau

| Route | Lý do |
|-------|-------|
| `/admin/loans` | Không còn mượn/trả sách |
| `/admin/fines` | Không còn phí phạt |
| `/admin/reservations` | Không còn đặt trước |
| `/admin/inventory/*` | Không còn kiểm kê |
| `/chat/*` | Loại bỏ chat AI |
| `/admin/metadata-quality` | Không cần |
| `/admin/reviews/moderate` | Có thể giữ review nhưng đơn giản hóa |

### 2. Thêm/Cập nhật route bán sách

| Route | Phương thức | Controller | Action | Mô tả |
|-------|-------------|------------|--------|-------|
| `/` | GET | `CatalogController` | `home` | Trang chủ public |
| `/catalog` | GET | `CatalogController` | `index` | Danh mục sách |
| `/book/{id}` | GET | `CatalogController` | `show` | Chi tiết sách |
| `/cart` | GET | `CartController` | `index` | Xem giỏ hàng |
| `/cart/add` | POST | `CartController` | `add` | Thêm vào giỏ |
| `/cart/remove` | POST | `CartController` | `remove` | Xóa khỏi giỏ |
| `/checkout` | GET | `CheckoutController` | `index` | Trang checkout |
| `/checkout` | POST | `CheckoutController` | `store` | Tạo đơn hàng |
| `/order/success` | GET | `OrderController` | `success` | Trang thành công |
| `/admin/orders` | GET | `OrderController` | `index` | Danh sách đơn hàng (admin) |
| `/admin/orders/{id}` | GET | `OrderController` | `show` | Chi tiết đơn hàng |
| `/admin/orders/{id}/status` | POST | `OrderController` | `updateStatus` | Cập nhật trạng thái |
| `/admin/orders/{id}/cancel` | POST | `OrderController` | `cancel` | Hủy đơn hàng |
| `/admin/payments` | GET | `PaymentController` | `index` | Danh sách thanh toán |
| `/admin/customers` | GET | `CustomerController` | `index` | Danh sách khách hàng |
| `/admin/books` | GET | `BookController` | `index` | Quản lý sách |
| `/admin/books` | POST | `BookController` | `store` | Thêm sách |
| `/admin/books/{id}` | PUT/PATCH | `BookController` | `update` | Sửa sách |
| `/admin/books/{id}/deactivate` | POST | `BookController` | `deactivate` | Ngừng bán |
| `/admin/users` | GET/POST | `StaffController` | `index/save` | Quản lý nhân viên |
| `/admin/permissions` | GET/POST | `StaffController` | `permissions/savePermissions` | Phân quyền |
| `/admin/settings` | GET/POST | `ManagementController` | `settings/saveSettings` | Cài đặt |
| `/login` | GET/POST | `AuthController` | `loginForm/login` | Đăng nhập |
| `/logout` | POST | `AuthController` | `logout` | Đăng xuất |
| `/register` | GET/POST | `AuthController` | `registerForm/register` | Đăng ký khách hàng |
| `/account` | GET | `CustomerController` | `dashboard` | Tài khoản khách hàng |
| `/account/orders` | GET | `CustomerController` | `orders` | Lịch sử đơn hàng |

### 3. Middleware/Auth

- Route `/admin/*`: yêu cầu auth (`admin` hoặc `nhan_vien`)
- Route `/account/*`, `/cart`, `/checkout`: yêu cầu auth (`nguoi_mua`)
- Route public (`/`, `/catalog`, `/book/{id}`, `/login`, `/register`): không yêu cầu auth

## Definition of Done

- [ ] File `app/Core/Router.php` được cập nhật với route bán sách
- [ ] Loại bỏ hết route mượn/trả, phí phạt, đặt trước, kiểm kê, chat
- [ ] Middleware auth đúng vai trò cho từng nhóm route
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
