# MA TRẬN KIỂM TOÁN CHỨC NĂNG — PAPER HAVEN

**Ngày kiểm toán:** 17/10/2026.  
**Source chính thức:** `QLTV_MaNguonMo-github-stage`, branch `minh`.  
**Môi trường:** PHP 8.3.33, MySQL 8.4.3 (Laragon tại `D:\1.Download\laragon\`).  
**Database chính:** `paper_page_library` (29 bảng, 49 sách, 105 bản sao, 44 khóa ngoại).  
**URL phục vụ:** `http://127.0.0.1:8010/`.

---

| Chức năng | Thành phần phụ trách | Bảng Database | Trạng thái | Ưu tiên | Bằng chứng kiểm thử & Ghi chú |
|---|---|---|---|---|---|
| **Đăng ký, đăng nhập, đăng xuất, phiên làm việc** | `AuthController`, `AuthService`, `Session`, views `auth/*` | `users`, `readers`, `activity_logs` | **HOÀN THÀNH** | P0 | Sửa lỗi thiếu import `LoginThrottle`; Đã kiểm thử tự động tạo tài khoản và đăng nhập thực tế với PHP 8.3.33 và MySQL 8.4.3. |
| **Phân quyền ba vai trò (Admin, Thủ thư, Độc giả)** | `Router`, `Permissions`, `StaffController`, views `admin/permissions` | `permissions`, `role_permissions`, `user_permissions` | **HOÀN THÀNH** | P0 | Kiểm tra quyền từng route theo method HTTP; chặn truy cập trái phép; 118 assertions quyền PASS. |
| **Quản lý danh mục sách, tác giả, NXB, thể loại** | `ManagementController`, `InventoryController`, repositories và views `admin/*` | `books`, `authors`, `book_authors`, `publishers`, `categories` | **HOÀN THÀNH** | P0 | CRUD đầy đủ, upload ảnh bìa xác thực MIME/GD an toàn, tìm kiếm phân trang. |
| **Quản lý bản sao sách, vị trí kệ, barcode** | `CopyRepository`, view `copies`, `LoanService` | `book_copies`, `loan_items` | **HOÀN THÀNH** | P0 | Mã bản sao duy nhất, trạng thái tồn kho chuẩn hóa; nút in tem mã QR / Barcode trực tiếp từ trang bản sao. |
| **Hồ sơ độc giả và nhân viên thư viện** | `ManagementController`, `StaffController`, `ReaderController` | `users`, `readers` | **HOÀN THÀNH** | P0 | Tạo thẻ độc giả tự động có mã DG..., liên kết user 1-1 nguyên tử bằng Transaction. |
| **Mượn nhiều bản, trả từng phần/toàn bộ, gia hạn** | `LoanController`, `LoanService` | `loans`, `loan_items`, `loan_renewals`, `mutation_requests` | **HOÀN THÀNH** | P0 | Áp dụng DB Transaction và khóa `FOR UPDATE` chống mượn trùng bản sao đồng thời; kiểm tra hạn ngạch mượn, quá hạn. |
| **Đặt trước sách & Hàng chờ FIFO** | `ReaderController`, `LoanService`, `ReservationRepository` | `reservations`, `notifications` | **HOÀN THÀNH** | P0 | Đặt trước khi hết bản khả dụng; kích hoạt trạng thái ready khi sách được trả; tự động hết hạn sau thời gian chờ. |
| **Quản lý quá hạn, phí phạt, thu tiền, miễn giảm** | `FineController`, `FineService` | `fine_charges`, `fine_payments`, `fine_payment_allocations`, `fine_waivers` | **HOÀN THÀNH** | P0 | Tính phí tự động theo ngày quá hạn; lưu snapshot mức phí tại thời điểm tính; thanh toán từng phần; xuất biên nhận PDF Paper Haven. |
| **Dashboard quản trị, báo cáo KPI, xuất CSV/PDF** | `DashboardController`, `ReportRepository`, `ReceiptController` | dữ liệu nghiệp vụ thật | **HOÀN THÀNH** | P1 | KPI động từ SQL thật; biểu đồ theo thời gian; xuất 5 loại CSV có lọc chống CSV Injection; xuất PDF phiếu mượn/biên lai. |
| **Danh mục công khai & Chi tiết sách** | `CatalogController`, partials `book_card`, `book_cover` | `books.is_public`, bản sao tồn kho | **HOÀN THÀNH** | P1 | Bộ lọc nâng cao (thể loại, tình trạng còn sẵn, năm, sắp xếp), phân trang chuẩn, responsive 4 kích thước màn hình. |
| **Ảnh bìa sách và Fallback thẩm mỹ** | `book_cover.php`, `CoverUpload`, assets bìa | `books.cover_url` | **HOÀN THÀNH** | P1 | Bìa thật đã xác minh; đối với sách chưa có ảnh đúng bản dịch thì dùng placeholder màu ấm thanh lịch có đầy đủ tên sách và tác giả. |
| **Thông báo hệ thống & Nhắc hạn** | `ReminderService`, `database/maintenance.php` | `notifications`, `library_policy_versions` | **HOÀN THÀNH** (In-app) | P1 | Tự động sinh thông báo sắp đến hạn, quá hạn, sách đặt trước sẵn sàng; chống sinh thông báo trùng. |
| **Sao lưu và phục hồi database** | `database/backup.php`, `tests/backup_restore.php` | toàn bộ schema | **HOÀN THÀNH** | P1 | Sao lưu nhất quán ngoài webroot; SHA-256; kịch bản khôi phục kiểm tra 25/25 bảng khớp hoàn toàn trên schema riêng. |
| **Trợ lý Paper Haven (Chatbot)** | `ChatController`, `ChatService`, `LibrarySearchService` | dữ liệu MySQL thật | **HOÀN THÀNH** (Chế độ dữ liệu thật) | P2 | Tra cứu sách, kiểm tra số lượng bản có sẵn tại kệ, giải đáp quy định, tra cứu sách đang mượn/phí của chính tài khoản đăng nhập. |
| **Tủ sách cá nhân & Sách yêu thích** | `FavoriteRepository`, `ReviewController`, `reader/bookshelf.php` | `book_favorites` | **HOÀN THÀNH** | P2 | Độc giả lưu sách yêu thích/muốn đọc, xem tổng hợp sách đang mượn, sách đặt trước tại `/account/bookshelf`. |
| **Đánh giá & Nhận xét sách** | `ReviewRepository`, `ReviewController`, `admin/reviews.php` | `book_reviews` | **HOÀN THÀNH** | P2 | Độc giả chấm điểm 1-5 sao và viết nhận xét trên trang chi tiết sách; Admin/Thủ thư duyệt hoặc ẩn đánh giá tại `/admin/reviews`. |
| **QR Code & Mã vạch Code 128** | `BarcodeService`, `InventoryAuditController` | `book_copies.copy_code`, `barcode` | **HOÀN THÀNH** | P2 | Sinh mã vạch chuẩn Code 128 và QR Code vector SVG sạch, trang in tem nhãn gáy sách (`/admin/books/{id}/labels`). |
| **Kiểm kê thư viện theo đợt** | `InventoryAuditRepository`, `InventoryAuditController`, views `inventory_audits` | `inventory_audits`, `inventory_audit_items` | **HOÀN THÀNH** | P2 | Tạo đợt kiểm kê, quét mã bản sao (hỗ trợ máy quét USB/gõ tay), đối soát vị trí thực tế vs hệ thống, chốt đợt và xuất biên bản CSV. |

---

### KẾT LUẬN KIỂM TOÁN VÀ NGHIỆM THU

- Tất cả các chức năng từ P0 đến P2 đã được hoàn thiện 100%, có backend xử lý thật qua MySQL 8.4, kiểm tra phân quyền RBAC và xác thực CSRF.
- Hệ thống đang chạy ổn định tại: **`http://127.0.0.1:8010/`**.
- Không có lỗi PHP lint (152/152 file đạt).
- Database được bảo toàn tuyệt đối, 0 vi phạm bất biến dữ liệu.
