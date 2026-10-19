# Nghiệm thu chatbot — 12/10/2026

Source: QLTV_MaNguonMo-github-stage, branch minh, không push/merge. Main: 8010 / paper_page_library; QA: 8015 / paper_page_test_20261011_214305_37d8. Tất cả kết quả dưới đây thực thi trong phiên chatbot này; không lấy 108 sprint checks cũ làm bằng chứng mới.

| Kiểm tra | Kết quả | Bằng chứng dưới storage/backups/chatbot-20261012 |
|---|---|---|
| PHP lint toàn bộ source | PASS 140 file, 0 lỗi | lint-final.log |
| MySQL chatbot: tìm có/không dấu, chủ đề, tồn kho, ngữ cảnh, policy, phân quyền, input, fallback | PASS 38 assertions | mysql-chat-results.json, mysql-chat.log |
| Hai độc giả riêng biệt, phí snapshot/miễn, giả reader_id, thu hồi quyền, rollback | PASS 12 assertions | isolation-results.json, isolation.log |
| Chrome 155, zoom 100%, public/3 vai trò, 1440/1024/768/390, ảnh, bàn phím, lỗi mạng, CSRF/length/rate | PASS 65 assertions | browser-chat-results.json, browser-chat-final.log, chat-*.png |
| Browser nghiệp vụ toàn hệ thống chạy lại trên QA | PASS 273 checks, 110 renders | browser-core-results.json, browser-core-regression.log |
| MySQL nghiệp vụ phase3 / trả toàn bộ nguyên tử | PASS 18 / 7 assertions | mysql-phase3.log, mysql-return-all.log |
| Giới hạn tiền/phí và nhắc hạn fixture | PASS 5 / 5 expectations | mysql-fine-limits.log, mysql-reminders-fixture.log |
| Khôi phục SQL backup trong database kiểm thử | PASS 25/25 bảng chính xác | restore.log, restore-*.json |
| Database chính sau chatbot so với trước, toàn bộ hàng/timestamp | PASS 25/25 bảng không đổi | main-preservation.json, main-preservation.log |
| Backup source ZIP đọc/CRC và file nền/dependency không đổi | PASS | source-integrity.json |
| API OpenAI live và timeout mạng thật | BLOCKED — thiếu API key/model | Chỉ test transport mock; không gọi API live |

## Lỗi đã xác minh và xử lý

- `IntentClassifier.php`: bỏ phụ thuộc iconv gây hỏng chuẩn hóa tiếng Việt trên Windows; chuyển bảng dấu rõ ràng. Bổ sung tách ý định muốn học và đang mượn, giữ ngữ cảnh cuốn được chọn. Regression trong MySQL chatbot.
- `ChatAuthorizationService.php`/`ChatRepository.php`: quyền và owner lấy lại từ database, bỏ reader_id client/session giả; kiểm tra hai người dùng bằng fixture rollback độc lập.
- `FineService.php`/`FineRepository.php`: đường xem phí chỉ đọc, tính đúng snapshot và khoản trả/miễn, báo cáo tổng hợp theo tập hợp. Regression so với hàm chuẩn trong transaction rollback.
- `ChatController.php`/`ChatRateLimiter.php`: CSRF, body/type/length và giới hạn bền theo IP; đổi session không vượt giới hạn. Các mã lỗi được gọi HTTP thật.
- `chat.js`: render an toàn, lỗi mạng phục hồi nút gửi, refresh phạm vi/CSRF khi focus, không localStorage. Kiểm tra backend đổi danh tính có bằng chứng; hành vi xóa DOM khi đổi tài khoản qua tab chưa có bài test trình duyệt riêng, cần xác minh bổ sung nếu thay đổi auth flow.
- Bộ browser cũ: scope nút submit vào form đăng nhập vì có thêm form chat; scope form copies vì panel bìa từ sprint trước đứng trước form. Không bỏ assertion. Reservation fixture cũ đã được làm mới trong database QA rồi chạy lại đủ 273 checks.

Không thay kiến trúc mượn/trả, palette/font, nâng dependency hoặc thêm bảng chat. Không ghi nghiệp vụ từ chatbot; gia hạn/đặt trước/thu tiền là hướng dẫn/link. Main vẫn 49 sách tổng, 28 công khai, 105 bản sao. Ba sách chưa có bìa xác minh thuộc báo cáo bìa trước, không được ẩn hoặc giả bìa trong sprint này. Các file snapshot database/bằng chứng riêng tư nằm trong storage bị Git ignore, không phát hành thông tin tài khoản.

## Chạy lại

Dùng PHP 8.3 hiện có, cấu hình DB_NAME là database QA (các test MySQL có guard), chạy `tests/mysql_chatbot.php`, `tests/mysql_chatbot_isolation.php`; browser chạy `tests/browser_chatbot.cjs`, core chạy `tests/browser_completion.cjs` với QA_BASE_URL=8015 và Chrome hiện có. Không chạy test tạo/sửa fixture trên database chính. Trước khi lặp core, fixture đặt trước cần còn hiệu lực; `tests/mysql_reminders.php` tạo fixture QA tương ứng.
