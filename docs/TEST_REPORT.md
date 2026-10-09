# Báo cáo nghiệm thu — 08/10/2026

Môi trường: Windows/Laragon PHP 8.3.33, PDO MySQL/mbstring, MySQL 8.4.3; Edge headless qua Playwright. HTTP test: 127.0.0.1:8011, database riêng `paper_page_test_20261008_220515_4e0e`. Test cài mới dùng database `paper_page_test_install_*` khác. Không ghi/xóa/reset dữ liệu database chính.

| Kiểm thử | Kết quả cuối | Bằng chứng |
|---|---|---|
| PHP lint | PASS | 92 file PHP, 0 lỗi |
| Smoke PHP | PASS | tests/run.php: 10 assertions |
| Session/CSRF/XSS/input/pagination/throttle | PASS | tests/security.php: 15 assertions; strict session, rotate ID, idle timeout, token cũ vô hiệu, limiter tồn tại qua instance mới |
| Gia hạn/reservation/FIFO/expiry/notifications | PASS | tests/mysql_phase3.php: 18 assertions |
| Phí/thu tiền/phân bổ/miễn giảm/metadata/CSV | PASS | tests/mysql_phase4.php: 21 assertions |
| Kho/CRUD metadata/archival/KPI/rollback | PASS | tests/mysql_completion.php: 26 assertions |
| Reminder và quyền sở hữu | PASS | tests/mysql_reminders.php: 5 kiểm tra sắp hạn/quá hạn chống trùng, notice ownership, renewal ownership, bản reserved hiển thị form staff |
| Hai kết nối MySQL cạnh tranh | PASS | tests/mysql_concurrency.php: 2 connection IDs, quan sát 2 row-lock waiters; 1 accepted/1 rejected, đúng 1 active item |
| Cài mới/chạy setup lần hai | PASS | tests/setup_fresh.php: 9 titles, 63 copies, 9 author links ngay lần đầu; chạy lại không tăng |
| CLI maintenance | PASS | Chạy trên DB test: expiry/fines/reminder hoàn tất |
| JavaScript syntax | PASS | node --check cho public/assets/js/app.js, js/main.js, tests/browser_completion.cjs |
| Browser/HTTP E2E | PASS | 273 checks; 110 page/viewport renders, 0 JavaScript error, không tràn ngang |
| Database chính chỉ đọc | PASS | 30 books/105 copies/4 readers/4 users/5 loans/6 items/1 reservation trước và sau; 0 inventory mismatch, 31 FK |
| Git status/diff | BLOCKED | Không có .git ở source hoặc workspace; không thể tạo diff đối chiếu commit |

Browser kiểm tra 22 route/màn × 5 viewport: 1440, 1366, 1024, 768, 390px. Bao gồm public home/login/register/catalog/book, admin dashboard/books/copies/readers/history/authors/publishers/categories/loans/fines/statistics/settings/notifications/profile và reader account/profile/notifications. Kiểm tra modal tạo sách/độc giả/thể loại/mượn ở mobile, sidebar mở/đóng, form tạo mới, CSV, CSRF và trái quyền. Có ảnh kho desktop/mobile và log trong storage/; đây là artifact local bị ignore.

## Đối chiếu 20 luồng yêu cầu

| # | Luồng | Kết quả / test |
|---|---|---|
| 1 | Admin đăng nhập | PASS — browser |
| 2 | Thêm sách | PASS — native modal browser |
| 3 | Thêm bản sao | PASS — browser + MySQL completion |
| 4 | Thêm độc giả | PASS — native modal browser |
| 5 | Thủ thư lập phiếu | PASS — thủ thư checkout native form nhận bản đã giữ chỗ; admin checkout nhiều bản qua HTTP |
| 6 | Mượn nhiều bản | PASS — browser hai bản, MySQL completion |
| 7 | Tồn kho | PASS — đối chiếu rows/available/items |
| 8 | Trả một phần | PASS — HTTP còn một item mở |
| 9 | Gia hạn phần chưa trả | PASS — HTTP + policy/history MySQL |
| 10 | Trả hết | PASS — HTTP header đóng |
| 11 | Lịch sử | PASS — reload và trang lịch sử reader |
| 12 | Tình huống quá hạn | PASS — tạo loan ngày cũ qua HTTP trên DB test |
| 13 | Tiền phạt | PASS — HTTP tính 2 ngày, ledger MySQL |
| 14 | Thanh toán | PASS — native form thu một phần, chặn overpayment, thu phần còn |
| 15 | Thống kê | PASS — distinct headers và physical inventory đối chiếu SQL |
| 16 | Reader chỉ thấy dữ liệu mình | PASS — role matrix, account không hiện fixture reader khác, ownership service |
| 17 | Reload dữ liệu bền | PASS — item/history/payment vẫn hiển thị |
| 18 | Cạnh tranh hai connection | PASS — row-lock waiters và unique active copy |
| 19 | Transaction thất bại | PASS — duplicate barcode và checkout có copy mất rollback không ghi dở |
| 20 | Mobile | PASS — 390px, modal/menu/form và bảng cuộn cục bộ |

Các FAIL trung gian đã sửa và chạy lại: ID rỗng modal, filter 768px, settings chứa tên DB dài ở 1024px. Một assertion E2E đọc flash sau khi đã redirect/reload được sửa trong test; nghiệp vụ gia hạn vẫn lưu đúng.

Giới hạn: kiểm tra form đăng ký/hồ sơ/mật khẩu có render và unit/security nhưng chưa có bộ exhaustive mutation cho mọi field; không có scanner bảo mật độc lập, kiểm thử tải, production/TLS/restore backup hoặc mọi khác biệt MariaDB. PDF không có trong phạm vi. Số PASS chỉ áp dụng cho tests nêu trên.
