# Task T8: Tạo `PaymentController`, `PaymentRepository`

**Mục tiêu:** Xây dựng module quản lý thanh toán đơn hàng (COD và Chuyển khoản).

## Các thay đổi chi tiết

### 1. `PaymentRepository`

| Phương thức | Mô tả |
|-------------|-------|
| `all($orderId, $limit, $offset)` | Lấy danh sách thanh toán |
| `find($id)` | Lấy chi tiết thanh toán |
| `create($data)` | Ghi nhận thanh toán mới |
| `updateStatus($orderId, $status)` | Cập nhật trạng thái thanh toán đơn hàng |

### 2. `PaymentController`

| Route | Phương thức | Action | Mô tả |
|-------|-------------|--------|-------|
| `/admin/payments` | GET | `index` | Danh sách thanh toán |
| `/admin/payments` | POST | `store` | Ghi nhận thanh toán |
| `/admin/payments/{id}` | GET | `show` | Chi tiết thanh toán |

### 3. Hình thức thanh toán

| Mã | Tên | Mô tả |
|----|-----|-------|
| `cod` | Tiền mặt khi nhận hàng | Thanh toán khi nhận hàng |
| `bank_transfer` | Chuyển khoản | Chuyển khoản ngân hàng |

## Definition of Done

- [ ] Tạo 2 file: `PaymentController.php`, `PaymentRepository.php`
- [ ] Trang danh sách thanh toán admin
- [ ] Ghi nhận thanh toán đơn hàng
- [ ] Cập nhật `payment_status` trong bảng `orders`
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
