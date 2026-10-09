# Thiết kế database

## Hiện trạng và nguyên tắc

Ứng dụng dùng MySQL/InnoDB, UTF8MB4, PDO và khóa ngoại. Các migration `001_initial_schema` đến `006_normalized_catalog_metadata` bổ sung schema theo version, không xóa bảng hoặc dữ liệu cũ. Bảng `loans` cùng các trường `book_id`, `due_at`, `returned_at` được giữ tương thích lịch sử; dữ liệu nghiệp vụ hiện tại được đọc từ `loan_items`.

## Quan hệ đã triển khai

- `books` 1–N `book_copies`; mã bản sao duy nhất giúp nhận diện từng hiện vật.
- `books` N–N `authors` qua `book_authors`; tên tác giả legacy trên `books.author` được đồng bộ để giữ tương thích và hỗ trợ tra cứu cũ. Nhập nhiều tác giả bằng dấu chấm phẩy.
- `publishers` 1–N `books` qua `books.publisher_id`; `books.publisher` vẫn được giữ làm bản sao legacy trong giai đoạn chuyển đổi.
- `readers` 1–N `loans`; mỗi phiếu có một người mượn.
- `users` 1–N `loans` qua nhân viên lập phiếu (nullable để giữ lịch sử khi tài khoản bị gỡ).
- `loans` 1–N `loan_items`; mỗi mục gắn một bản sao cụ thể, ngày mượn, hạn trả, ngày trả và mức phạt/ngày được chụp tại thời điểm lập phiếu.
- `loan_items` N–1 `book_copies`; lịch sử không bị xóa khi bản sao được trả.
- `reservations` gắn độc giả, đầu sách và (khi đã sẵn sàng) bản sao được giữ; FIFO được xác định bằng `reserved_at, id`.
- `loan_items` 1–N `loan_renewals`; mỗi lần gia hạn giữ hạn cũ/mới, số thứ tự, số ngày áp dụng và phiên bản chính sách bất biến.
- `system_settings` lưu chính sách đang hiệu lực; mỗi lần lưu tạo snapshot trong `library_policy_versions`. Thông tin mật khẩu và kết nối vẫn ở `.env`.
- `users` 1–N `notifications`; khóa deduplication ngăn phát sinh lại cùng một sự kiện, `read_at` thể hiện trạng thái đọc.
- `loan_items` 1–N `fine_charges`; phí quá hạn được đồng bộ lũy kế theo bản mượn, còn phí sách hỏng/mất phát sinh khi nhận trả. Khóa deduplication giúp đồng bộ lặp lại không nhân đôi khoản phí.
- `readers` 1–N `fine_payments`; mỗi khoản thu được phân bổ vào một hoặc nhiều khoản phí cũ nhất qua `fine_payment_allocations`. `fine_waivers` ghi số tiền, lý do và người duyệt.
- `system_settings` giữ chính sách phí sách hỏng/mất; snapshot `library_policy_versions` bổ sung hai mức phí này cho phiên bản chính sách.
- `activity_logs` liên kết tùy chọn đến người thực hiện.

## Ràng buộc và đồng thời

- Khóa ngoại dùng `RESTRICT` cho bản ghi lịch sử mượn/bản sao và `SET NULL` khi tài khoản nhân viên không còn.
- `loan_items.active_copy_id` là cột generated, bằng mã bản sao khi chưa trả và `NULL` sau khi trả; unique index ngăn cùng một bản sao có hai mục mượn đang mở.
- Bản sao bị khóa bằng `SELECT ... FOR UPDATE` trước khi checkout/return. Thao tác đầu phiếu, chi tiết, đổi trạng thái bản sao và audit log cùng chạy trong transaction.
- Các chỉ số khả dụng được truy vấn từ trạng thái `book_copies`; bản đã gắn vào reservation ở trạng thái `reserved` nên không bị trừ tồn lần thứ hai.
- Thu tiền/miễn giảm khóa hàng độc giả, kiểm tra số dư còn lại và cập nhật payment/allocation hoặc waiver trong cùng transaction; không cho ghi nhận vượt nợ.
- Không xóa cứng bản sao/đầu sách có lịch sử; giảm số lượng chỉ chuyển bản sẵn sàng sang `retired`.

## Phần chưa triển khai

Báo cáo PDF và role table riêng chưa có trong schema hiện tại. Vai trò hiện là `ENUM` trong `users`; RBAC granular ngoài phạm vi hiện tại; concurrency hai session đã PASS trên DB test riêng. Trang quản lý copy cập nhật shelf_location/barcode/status/item_condition có transaction, chặn sửa copy đang mượn/giữ chỗ. Không có migration mới trong lượt hoàn thiện này.
