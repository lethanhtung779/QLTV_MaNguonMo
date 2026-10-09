# Hướng dẫn sử dụng

Khách: mở trang chủ/catalog, tìm tên/tác giả/ISBN/mã, lọc thể loại/khả dụng, chọn sort và phân trang. Tìm không dấu hoạt động với utf8mb4_unicode_ci đã kiểm tra. Mở sách xem mô tả và số bản sẵn sàng; cover không tải có fallback.

Độc giả: đăng nhập vào /account, xem lịch sử/bản đang mượn/phí/payment/đặt trước. Gia hạn từng bản theo policy; hủy yêu cầu của mình; /account/profile cập nhật liên hệ/đổi mật khẩu. Thông báo sắp/quá hạn/đặt trước và đánh dấu đã đọc ở /notifications.

Thủ thư/admin:

1. Sách: Thêm sách nhập metadata/tổng copy; sửa/ngừng đầu sách. Ngừng được khi không còn copy đang mượn/đặt trước; lịch sử vẫn giữ.
2. Bản sao: nút Bản sao trên thẻ sách, thêm/sửa mã/barcode/kệ/tình trạng. Mã/barcode duy nhất. Hỏng/mất không cho mượn; retired không tính tồn phục vụ. Copy đang mượn/giữ chỗ xử lý bằng Mượn/Trả/đặt trước.
3. Tác giả/NXB: thêm, tìm, đổi tên; tên đổi đồng bộ với books; chỉ xóa mục chưa được dùng. Sách nhiều tác giả nhập dấu chấm phẩy.
4. Độc giả: thêm/sửa thông tin, ngày hết hạn/trạng thái. Click tên xem lịch sử và dư phí. Lọc Ngừng hoạt động để khôi phục thẻ bằng Sửa → Đang hoạt động. Không ngừng khi còn sách, đặt trước hoặc nợ.
5. Mượn/Trả: chọn reader và nhiều copy khả dụng hoặc copy có nhãn Giữ cho đúng thẻ. Trả từng bản/chọn tình trạng; phiếu chỉ hoàn tất khi trả hết. Gia hạn từng bản chưa quá hạn, chưa hết lượt và không có queue đặt trước.
6. Phí: chọn reader, nhập tiền/phương thức/reference; thu một phần được, vượt dư nợ bị từ chối. Payment lịch sử được giữ. Admin có thể miễn/giảm có lý do.
7. Thống kê: KPI phân biệt đầu sách/copy/phiếu; báo cáo mượn lọc ngày/trạng thái; kho/phí có preview riêng và CSV tối đa 10.000 dòng.
8. /profile cập nhật hồ sơ/đổi mật khẩu staff. Chỉ admin mở Cài đặt để sửa policy; phiên bản chính sách và renewal history được giữ.

Phiên hết hạn sau 1.800 giây không hoạt động (cấu hình SESSION_TIMEOUT). Đăng nhập sai nhiều lần bị chặn 5 phút theo email/IP và session. Không chia sẻ mật khẩu/token/.env.

Chưa có PDF, upload ảnh, REST API, UI tạo tài khoản nhân viên/permission matrix. Các mục này ngoài nghiệp vụ P0/P1 của bản bàn giao.
