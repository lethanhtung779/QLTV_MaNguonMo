# Từ điển dữ liệu (phạm vi đã triển khai)

Các bảng nền tảng được khai báo trong `database/schema.sql`; bản sao/chi tiết ở migration `002`, policies/reservations/notifications ở `003`, snapshot policy ở `004`, ledger phí/thanh toán ở `005`, metadata chuẩn hóa ở `006`.

| Bảng | Khóa chính | Các trường nghiệp vụ quan trọng | Quan hệ / ràng buộc |
|---|---|---|---|
| `users` | `id` | `name`, `email`, `password_hash`, `role`, `is_active`, `last_login_at` | Email duy nhất; role hiện là `admin`, `librarian`, `reader`. |
| `categories` | `id` | `name`, `description` | Tên duy nhất. |
| `books` | `id` | `book_code`, `title`, `author` (legacy text), `isbn`, `category_id`, `publisher` (legacy text), `publisher_id`, `total_copies`, `is_active` | Mã/ISBN duy nhất; category/publisher nullable. `total_copies` giữ tương thích; tồn hiện hành đọc từ bản sao chưa retired. |
| `authors` | `id` | `name` | Tên duy nhất; dữ liệu legacy được backfill và catalog mới đồng bộ khi lưu sách. |
| `publishers` | `id` | `name` | Tên duy nhất; được tham chiếu bởi `books.publisher_id`. |
| `book_authors` | `(book_id, author_id)` | `book_id`, `author_id` | Bảng nối sách-tác giả nhiều-nhiều; input quản trị dùng dấu `;` giữa tên tác giả. |
| `readers` | `id` | `user_id`, `card_number`, `full_name`, liên hệ, `joined_on`, `expires_on`, `is_active` | `user_id` và `card_number` duy nhất; lịch sử phiếu được giữ. |
| `book_copies` | `id` | `book_id`, `copy_code`, `barcode`, `item_condition`, `status`, `shelf_location`, `acquired_at` | FK tới `books`; `copy_code` và barcode duy nhất; trạng thái gồm available/on_loan/reserved/damaged/lost/retired. |
| `loans` | `id` | `loan_code`, `reader_id`, `book_id` (legacy/đại diện bản đầu tiên), `issued_by`, `borrowed_at`, `due_at`, `returned_at`, `daily_fine`, `note` | FK tới reader/book/user. `returned_at` trên header chỉ được đặt khi mọi chi tiết đã trả. |
| `loan_items` | `id` | `loan_id`, `book_copy_id`, `borrowed_at`, `due_date`, `returned_at`, `daily_fine` | FK tới header/bản sao; unique generated `active_copy_id` chống mượn đồng thời. Số lần gia hạn được đếm từ ledger. |
| `loan_renewals` | `id` | `loan_item_id`, `renewal_number`, `policy_version`, `renewal_days`, `previous_due_date`, `new_due_date`, `renewed_by` | FK tới loan item, snapshot chính sách và tài khoản; unique theo loan item/số lần gia hạn. |
| `reservations` | `id` | `reader_id`, `book_id`, `book_copy_id`, `status`, `reserved_at`, `expires_at`, `handled_by` | FK tới reader/book/copy/user; bản được giữ chuyển sang `reserved`, FIFO theo ngày tạo và id. |
| `system_settings` | `setting_key` | `setting_value`, `updated_by`, `updated_at` | Chính sách nghiệp vụ đang có hiệu lực; secrets không được lưu ở đây. |
| `library_policy_versions` | `id` | bản chụp tên thư viện, hạn mượn, số lần/ngày gia hạn, phí/ngày, hạn nhận đặt trước và `updated_by` | Snapshot bất biến; `loan_renewals.policy_version` tham chiếu version đã áp dụng. |
| `fine_charges` | `id` | `loan_item_id`, `reader_id`, `charge_type`, `amount`, `description`, `deduplication_key`, `assessed_by` | Một khoản phí cho mục mượn; unique deduplication key; loại quá hạn/hỏng/mất. |
| `fine_payments` | `id` | `reader_id`, `amount`, `method`, `reference_code`, `note`, `received_by` | Giao dịch thu tiền; số tiền dương và người nhận có thể được giữ tùy theo vòng đời tài khoản. |
| `fine_payment_allocations` | `(payment_id, fine_charge_id)` | `amount` | Phân bổ payment vào từng khoản phí; tổng phân bổ bằng payment. |
| `fine_waivers` | `id` | `fine_charge_id`, `amount`, `reason`, `waived_by` | Lịch sử miễn/giảm có lý do và người thực hiện; không sửa/xóa phí gốc. |
| `notifications` | `id` | `user_id`, `title`, `message`, `type`, `entity_type`, `entity_id`, `deduplication_key`, `read_at` | FK tới user; khóa deduplication unique, hỗ trợ trạng thái chưa đọc/đã đọc. |
| `activity_logs` | `id` | `user_id`, `action`, `subject_type`, `subject_id`, `details`, `created_at` | FK tùy chọn tới actor; indexed theo ngày và user. |
| `schema_migrations` | `version` | `applied_at` | Ghi migration đã chạy; version hiện tại đến `006_normalized_catalog_metadata`. |

Kiểu dữ liệu chi tiết có thể xem trực tiếp trong schema và migration. Các cột văn bản author/publisher được giữ để bảo toàn tương thích trong khi relations chuẩn hóa được dùng ở phần ghi dữ liệu.

Cập nhật 08/10/2026: CRUD copy dùng copy_code/barcode/shelf_location/item_condition/status đã có từ migration 002; authors/publishers CRUD đồng bộ các cột legacy. Dashboard dùng COUNT DISTINCT loan_id cho phiếu; books.total_copies không thay thế nguồn tồn vật lý. Schema hiện tại: 21 bảng, 31 FK; không thêm bảng/cột trong lượt này.
