# Task T10: Cập nhật `NotificationController` – thông báo + gửi email

**Mục tiêu:** Cập nhật module thông báo để gửi email khi trạng thái đơn hàng thay đổi.

## Các thay đổi chi tiết

### 1. Loại bỏ thông báo thư viện cũ

- Không còn thông báo quá hạn, đặt trước sẵn sàng, v.v.

### 2. Thêm thông báo đơn hàng

| Sự kiện | Người nhận | Nội dung email |
|---------|-----------|----------------|
| Đơn hàng mới (`pending`) | Khách hàng | "Đơn hàng #XXX đã được đặt thành công" |
| Đơn hàng đang xử lý (`processing`) | Khách hàng | "Đơn hàng #XXX đang được xử lý" |
| Đơn hàng đã giao (`delivered`) | Khách hàng | "Đơn hàng #XXX đã được giao thành công" |
| Đơn hàng bị hủy (`cancelled`) | Khách hàng | "Đơn hàng #XXX đã bị hủy" |

### 3. Gửi email

- Sử dụng PHP `mail()` hoặc thư viện email có sẵn trong project
- Template email đơn giản (HTML/text)
- Ghi log thông báo vào bảng `notifications` hoặc `activity_logs`

### 4. Tích hợp với OrderService

- Khi `placeOrder` xong → gửi email xác nhận đơn hàng
- Khi `updateStatus` thay đổi → gửi email thông báo trạng thái mới

## Definition of Done

- [ ] Cập nhật `NotificationController` hỗ trợ gửi email
- [ ] Tích hợp gửi email khi tạo đơn hàng
- [ ] Tích hợp gửi email khi cập nhật/hủy đơn hàng
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
