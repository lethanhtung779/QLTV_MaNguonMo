# Task T13: Kiểm thử toàn bộ luồng đăng nhập → giỏ hàng → checkout → đơn hàng

**Mục tiêu:** Kiểm thử end-to-end toàn bộ luồng bán sách để đảm bảo hoạt động chính xác.

## Các kịch bản kiểm thử

### 1. Đăng nhập & Đăng ký

| Kịch bản | Kết quả mong đợi |
|----------|-----------------|
| Đăng nhập với vai trò `admin` | Điều hướng đến `/admin` |
| Đăng nhập với vai trò `nhan_vien` | Điều hướng đến `/admin/orders` |
| Đăng nhập với vai trò `nguoi_mua` | Điều hướng đến `/account` |
| Đăng ký khách hàng mới | Tạo tài khoản `nguoi_mua` + bản ghi `customers` |
| Đăng ký email trùng | Thông báo lỗi "Email đã tồn tại" |

### 2. Giỏ hàng & Đặt hàng

| Kịch bản | Kết quả mong đợi |
|----------|-----------------|
| Thêm sách vào giỏ | Sách hiển thị trong giỏ hàng |
| Xóa sách khỏi giỏ | Sách bị xóa khỏi giỏ |
| Đặt hàng (COD) | Tạo đơn hàng `pending`, trừ tồn kho |
| Đặt hàng (Chuyển khoản) | Tạo đơn hàng `pending`, `payment_status = unpaid` |
| Đặt hàng tồn kho không đủ | Thông báo lỗi "Sách hết hàng" |

### 3. Quản lý đơn hàng (Nhân viên/Admin)

| Kịch bản | Kết quả mong đợi |
|----------|-----------------|
| Xem danh sách đơn hàng | Hiển thị tất cả đơn hàng |
| Cập nhật trạng thái `pending` → `processing` | Đơn hàng thay đổi trạng thái, gửi email |
| Hủy đơn hàng | Hoàn lại tồn kho, gửi email thông báo |

### 4. Quản lý sách (Admin)

| Kịch bản | Kết quả mong đợi |
|----------|-----------------|
| Thêm sách mới | Lưu giá bán, tồn kho |
| Sửa thông tin sách | Cập nhật đúng thông tin |
| Ngừng bán sách | Sách không hiển thị trong catalog public |

## Definition of Done

- [ ] Tất cả kịch bản kiểm thử trên đã chạy
- [ ] Không có lỗi nghiêm trọng (bug chặn luồng chính)
- [ ] Email được gửi đúng khi tạo/hủy/cập nhật đơn hàng
- [ ] Tồn kho được trừ/högeri đúng
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
