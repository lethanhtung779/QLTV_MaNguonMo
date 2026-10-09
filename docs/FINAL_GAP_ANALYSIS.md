# Kiểm toán cuối — 08/10/2026

Kiểm toán source PHP đang có, giữ giao diện PAPER & PAGE và database `paper_page_library`. Source thật nằm trong thư mục con `QLTV_MaNguonMo-main` của workspace. Project không có `.git`; không thể xác minh Git diff/commit hay phân biệt thay đổi của các đợt trước bằng Git.

## Database đã đọc thực tế

MySQL 8.4.3; 21 bảng, 31 khóa ngoại; migration 001–006. Trước và sau đợt sửa: 30 đầu sách, 105 bản sao, 4 độc giả, 4 tài khoản, 5 phiếu, 6 mục mượn và 1 đặt trước. Không có copy `on_loan` lệch với `loan_items` đang mở. Không chạy reset, seed lại hoặc kiểm thử ghi dữ liệu vào database chính.

## Khoảng trống và xử lý

| Mức | Phát hiện trước sửa | Kết quả hiện tại |
|---|---|---|
| P0 | Modal thêm sách/độc giả/thể loại đặt ID rỗng, server yêu cầu số | ID tạo mới là 0; tạo sách/độc giả qua browser PASS |
| P0 | Cài mới seed sách sau migration kho nên lần đầu thiếu copy/metadata | Seed mới tạo copy và metadata trong transaction; 9 sách/63 copy ngay lần đầu, chạy lại giữ nguyên |
| P0 | Session chưa strict/timeout, quyền tài khoản chỉ lấy từ session | Strict ID, cookie flags, timeout 1.800 giây; require_auth đọc lại tài khoản/role |
| P0 | Giới hạn đăng nhập gắn riêng session, có thể đổi cookie | Bổ sung bộ đếm email/IP dạng hash ở cache với flock; không lưu email/password/token |
| P0 | Ngừng sách dựa `loans.book_id` legacy, chặn cả lịch sử đã đóng và bỏ sót sách khác trong phiếu | Kiểm tra copy/item đang mở + đặt trước; khóa đầu sách/transaction; bảo toàn lịch sử |
| P0 | PDOException kế thừa RuntimeException có thể lộ thông báo SQL ở phí/gia hạn | Trả thông báo chung cho lỗi PDO |
| P1 | Service hỗ trợ nhận reserved copy nhưng dropdown chỉ có available | Thêm reserved copy còn hiệu lực và nhãn thẻ được giữ; thủ thư nhận đúng người qua native form PASS |
| P1 | Không có màn quản lý từng bản sao/vị trí kệ | Thêm/sửa mã, barcode, kệ, tình trạng, ngừng/phục hồi; không sửa tay copy đang mượn/giữ chỗ |
| P1 | Chưa có CRUD tác giả/NXB độc lập | Hai màn có tìm kiếm/phân trang; đổi tên đồng bộ books legacy; không xóa mục đang dùng |
| P1 | Danh mục/sách/độc giả tải toàn bộ | LIMIT/OFFSET server, giữ bộ lọc; catalog/sách sắp xếp whitelist |
| P1 | Thiếu lịch sử độc giả cho staff và khôi phục thẻ | Trang lịch sử/phí; lọc thẻ ngừng hoạt động, sửa trạng thái để kích hoạt lại |
| P1 | Ngừng thẻ chưa khóa đồng thời hoặc kiểm tra đặt trước/dư nợ | Transaction khóa reader; chặn khi còn sách, đặt trước hoặc nợ |
| P1 | Hồ sơ/đổi mật khẩu chỉ cho reader | Thêm /profile cho admin/thủ thư; reader giữ /account/profile |
| P1 | Thiếu báo cáo kho/phí riêng | Preview và CSV UTF-8 cho tồn kho/phí; chống formula injection, giới hạn 10.000 dòng |
| P1 | Chưa có reminder sắp hạn/quá hạn | Tạo khi mở account/notifications; CLI maintenance cho Task Scheduler; dedupe theo item/hạn trả |
| P1 | KPI phiếu đếm item; theo thể loại dùng tổng legacy | COUNT DISTINCT loan_id cho phiếu; kho thể loại đếm copy vật lý |
| P1 | Filter tablet, thông tin DB dài, font mobile nhỏ | Sửa wrap/minmax và font form/bảng; kiểm tra 5 viewport |
| P1 | Tài liệu mâu thuẫn với test, test ghi vào DB dev chính | Cập nhật báo cáo; mọi script MySQL ghi bị guard theo DB_NAME test riêng |

## Phạm vi giữ nguyên/chưa có

Mượn nhiều bản, trả từng phần, gia hạn/policy history, FIFO đặt trước/hết hạn, ledger phí/thanh toán một phần/miễn giảm, notification ownership và CSV mượn đã có; đã kiểm tra lại trên MySQL thật. PDF, upload ảnh, REST API và permission matrix cấu hình không thuộc phạm vi P0/P1 hiện tại và chưa triển khai. Ảnh bìa dùng ISBN/Open Library và fallback. Chưa chứng nhận bảo mật độc lập, production TLS/backup restore hoặc mức tải lớn.

Git diff: **BLOCKED** vì thư mục không phải repository. Không có FAIL còn tồn tại trong bộ nghiệm thu đã chạy; xem TEST_REPORT.md. Các báo cáo cũ trong PROJECT_AUDIT.md là lịch sử, báo cáo này là hiện trạng cuối.
