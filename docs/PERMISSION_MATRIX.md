# Ma trận quyền

Backend: Router → require_permission → Permissions::allows. Active/role đọc lại từ MySQL mỗi yêu cầu. Thay đổi quyền có hiệu lực trong phiên đã đăng nhập; quyền không tồn tại bị từ chối.

| Quyền | Admin | Thủ thư mặc định | Độc giả |
|---|---|---|---|
| books.view/create/update/deactivate | Có | Có, có thể thu hồi riêng | Catalog công khai |
| metadata.manage | Có | Có, có thể thu hồi | Không |
| readers.view/create/update/suspend | Có | Có, có thể thu hồi riêng | Hồ sơ cá nhân |
| loans.view/create/return/renew | Có | Có, có thể thu hồi riêng | Lịch sử/gia hạn của mình |
| reservations.manage | Có | Có, có thể thu hồi | Đặt/hủy yêu cầu của mình |
| fines.view/collect | Có | Có, có thể thu hồi riêng | Phí/biên nhận của mình |
| reports.view | Có | Có, có thể thu hồi | Không |
| users.manage/roles.manage | Có | Không thể cấp | Không |
| fines.waive/settings.manage/audit.view | Có | Không thể cấp | Không |

permissions, role_permissions chứa danh mục/default; user_permissions.allowed ghi override từng nhân viên. Admin dùng toàn bộ quyền đã khai báo để tránh khóa quản trị. Giao diện sửa quyền nghiệp vụ Thủ thư; không hỗ trợ vai trò tùy ý.

Tạo/sửa sách và độc giả phân biệt bằng ID. Đổi trạng thái thẻ còn yêu cầu readers.suspend dù có readers.update. Sửa bản sao cần books.update. CSV cần reports.view; CSV độc giả/sách còn kiểm tra readers.view/books.view. PDF staff cần loans.view/fines.view; PDF độc giả đối chiếu readers.user_id với session.

Nhân viên không thể tự khóa/hạ vai trò tài khoản đang dùng. Transaction khóa danh sách Admin, ngăn mất Admin hoạt động cuối cùng. Mọi thay đổi nhân viên/quyền ghi nhật ký, không ghi mật khẩu/hash.

Bằng chứng: mysql_permissions.php 118 assertions; browser sprint thu hồi toàn bộ quyền và kiểm tra GET/POST trả 403 trong phiên hiện tại, cấp lại truy cập thành công. Giả cấp users.manage bị từ chối.
