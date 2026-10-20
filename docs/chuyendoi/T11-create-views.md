# Task T11: Tạo View `public/cart`, `public/checkout`, `admin/orders`, `customer/orders`

**Mục tiêu:** Tạo các giao diện cần thiết cho luồng bán sách.

## Các thay đổi chi tiết

### 1. Public views (khách hàng)

| File | Mô tả |
|------|-------|
| `public/cart.php` | Xem giỏ hàng, chọn số lượng, xóa sản phẩm |
| `public/checkout.php` | Form checkout, chọn phương thức thanh toán, xác nhận đơn |

### 2. Admin views

| File | Mô tả |
|------|-------|
| `admin/orders.php` | Danh sách đơn hàng, lọc theo trạng thái, cập nhật trạng thái |
| `admin/orders/show.php` | Chi tiết đơn hàng, danh sách sách trong đơn |
| `admin/books.php` | Quản lý sách (thêm/sửa/xóa, giá, tồn kho) |
| `admin/customers.php` | Danh sách khách hàng |

### 3. Customer views

| File | Mô tả |
|------|-------|
| `customer/dashboard.php` | Trang tài khoản khách hàng |
| `customer/orders.php` | Lịch sử đơn hàng của khách hàng |

### 4. Layout & Partials

- Cập nhật menu navigation theo 3 vai trò
- Thêm flash message hiển thị thành công/lỗi
- Giữ nguyên `layouts/app.php`, `layouts/public.php`

## Definition of Done

- [ ] Tất cả view files được tạo trong thư mục tương ứng
- [ ] Menu navigation phân quyền đúng vai trò
- [ ] Flash message hiển thị thành công/lỗi
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
