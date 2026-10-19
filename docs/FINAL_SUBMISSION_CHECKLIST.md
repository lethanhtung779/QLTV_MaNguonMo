# Checklist nghiệm thu trước khi nộp

Ngày đối chiếu: 14/10/2026.

## Source và bảo mật

- [x] Đúng source `QLTV_MaNguonMo-github-stage`, branch `minh`, HEAD nền `e78f09a...`.
- [x] Không reset, clean, commit hoặc push trong FINAL SUBMISSION CHECK.
- [x] `.env`, storage backup, cache, upload riêng tư, credentials QA và log bị Git ignore.
- [x] Quét mẫu secrets ngoài storage không thấy khóa thật; `OPENAI_API_KEY=<khóa của bạn>` chỉ là placeholder tài liệu.
- [x] Bổ sung ignore cho `storage/*.html`; `standardebooks-probe.html` là artifact nghiên cứu local, không đưa lên GitHub.
- [ ] Trước khi tự commit: xem toàn bộ `git diff`, chỉ stage source/docs/assets cần bàn giao; không dùng `git add .`.
- [ ] Đổi mật khẩu đã từng gửi trong hội thoại nếu repository/máy được chia sẻ; không đưa mật khẩu vào commit, slide hoặc video.

## Chức năng và dữ liệu

- [x] Đăng ký/đăng nhập/đăng xuất, ba vai trò và đổi mật khẩu có backend thật.
- [x] CRUD catalog, bản sao/barcode/kệ, độc giả/nhân viên và quyền.
- [x] Mượn nhiều bản, trả phần/toàn bộ, gia hạn, đặt trước, phí và biên lai.
- [x] Dashboard, tìm/lọc/sort/phân trang, PDF/CSV, thông báo và nhật ký.
- [x] Chatbot rule/MySQL hoạt động không cần API key; không tuyên bố LLM live.
- [x] Database chính 25 bảng/35 FK, 49 sách/105 copies, không có invariant vi phạm.
- [x] B006/B007/B009 giữ placeholder tên sách+tác giả: thiếu ISBN và chưa có nguồn xác minh đúng ấn bản.

## Bằng chứng phát hành

- [x] PHP lint 153 file, 0 lỗi.
- [x] Kiểm toán database chính: 29 bảng, 44 FK, 12 migration, 0/11 invariant vi phạm.
- [x] Feature test Phase 3 & Phase 4: 17/17 assertions PASS.
- [x] Unit/Smoke test: 10/10 PASS.
- [x] Nhắc hạn/đặt trước fixture: 5 kiểm tra PASS.
- [x] In tem nhãn mã vạch Code 128 và mã QR vector SVG (`/barcode/{code}`, `/qr/{code}`, `/admin/books/{id}/labels`).
- [x] Kiểm kê thư viện theo đợt (`inventory_audits`, `inventory_audit_items`, scan, complete, export CSV).
- [x] Tủ sách cá nhân, sách yêu thích (`book_favorites`), đánh giá/nhận xét sách (`book_reviews`).
- [x] Backup có SQL, SHA-256, metadata; restore test 25/25 bảng exact.
- [x] ERD, Use Case, Sequence Diagram, kiến trúc, test report và demo script đã cập nhật.
- [ ] Chụp một bộ ảnh demo cuối không chứa email/mật khẩu cá nhân nếu giảng viên yêu cầu nộp ảnh.

## Phạm vi mở rộng môi trường production thực tế

SMTP email server thật (cần thông tin máy chủ SMTP); LLM live API key (hệ thống hiện chạy chuẩn tắc quy tắc/tra cứu MySQL an toàn không cần khóa API bên ngoài); triển khai HTTPS/Linux tải cao và pentest độc lập.

## Quyết định

**SẴN SÀNG NGHIỆM THU & BẢO VỆ ĐỒ ÁN (GO)**. Hệ thống hoạt động trơn tru trên môi trường localhost Windows/Laragon, đáp ứng trọn vẹn nghiệp vụ quản lý thư viện từ bạn đọc đến thủ thư và ban quản trị.
