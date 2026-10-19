# Hoàn thiện đăng nhập/đăng ký — 12/10/2026

Đăng nhập dùng chung cho ba vai trò, hướng dẫn phân biệt Độc giả tự đăng ký và nhân viên được Admin cấp; tự chuyển trang theo quyền. Thêm hướng dẫn khi quên mật khẩu (liên hệ hỗ trợ, chưa có email reset). Không hiển thị khóa hoặc mật khẩu demo trên website.

AuthController giữ email khi đăng nhập lỗi, tên/email/điện thoại khi đăng ký lỗi, không lưu mật khẩu vào dữ liệu form. Đăng ký thành công điền sẵn email trên trang login. Không có role selector cấp quyền từ client. CSS chỉ thêm auth-help dùng token hiện hữu.

Kiểm chứng thực thi: lint 3 file PHP PASS; Chrome 10 checks PASS: tạo reader trên QA8015, đăng nhập tài khoản vừa tạo, giữ email khi lỗi và mật khẩu trống, login/register 1440/390 không tràn, mở hướng dẫn. Bằng chứng storage/backups/auth-ux-20261012/results.json, screenshot login/register và backup trước sửa. QA server đã khởi động lại với DB_NAME paper_page_test_20261011_214305_37d8. Không tạo tài khoản test trên main.

File sửa: app/Controllers/AuthController.php, app/Views/auth/login.php, app/Views/auth/register.php, public/assets/css/app.css. git diff --check cho các file này sạch; kiểm tra toàn tree phát hiện trailing whitespace trong docs/PROJECT_AUDIT.md ngoài phạm vi lần sửa, giữ nguyên. Không đổi dependency/palette/font; không push.
