# Paper & Page — Hệ thống quản lý thư viện

Ứng dụng quản lý thư viện bằng **PHP 8.2+**, **PDO MySQL** và **MySQL 8 / MariaDB tương thích**. Dữ liệu nghiệp vụ được lưu ở database; không dùng `localStorage` làm nguồn dữ liệu ứng dụng PHP.

## Chức năng

- Trang công khai bằng tiếng Việt: trang chủ, tìm kiếm/lọc danh mục, chi tiết sách, tình trạng bản sách, thể loại và quy định mượn.
- Đăng nhập PHP Session, đăng ký thẻ độc giả có thể bật/tắt, phân quyền quản trị viên/thủ thư/độc giả, CSRF, băm mật khẩu và giới hạn thử đăng nhập.
- Dashboard quản trị, thống kê theo thể loại/tháng, nhật ký hoạt động và các chỉ số sách/độc giả/mượn-trả/quá hạn.
- CRUD sách, độc giả, thể loại, tác giả/nhà xuất bản; phân trang server catalog/độc giả, lọc/sắp xếp; giữ lịch sử khi ngừng lưu hành.
- Trang quản lý từng bản sao, barcode/vị trí kệ/tình trạng, chặn sửa copy đang mượn/giữ chỗ; kho từng bản sao có mã riêng; một phiếu chứa nhiều bản sao, trả từng bản và giữ lịch sử khi phiếu còn bản chưa trả.
- Gia hạn từng bản theo chính sách có phiên bản và lịch sử; chặn gia hạn quá hạn, hết lượt hoặc khi có người đặt trước.
- Hàng đợi đặt trước FIFO gắn bản sao cụ thể; tự cấp bản khi trả/thêm tồn, thời hạn nhận, tự giải phóng khi hết hạn.
- Sổ phí MySQL cho quá hạn, sách hỏng/mất; thu tiền có phân bổ theo khoản cũ nhất, chặn thu vượt nợ, miễn/giảm có lý do và audit trail.
- Metadata tác giả/nhà xuất bản chuẩn hóa; một đầu sách hỗ trợ nhiều tác giả (phân cách bằng dấu `;`) mà vẫn giữ trường legacy tương thích.
- Báo cáo tồn kho/phí và CSV; thống kê lượt mượn có bộ lọc khoảng ngày/trạng thái và xuất CSV UTF-8 có chống formula injection; dashboard có KPI dư nợ.
- Thông báo sắp hạn/quá hạn khi mở account/notifications, CLI database/maintenance.php; thông báo MySQL có trạng thái đã đọc/chưa đọc và khóa chống tạo trùng; độc giả xem thẻ, lịch sử, dư nợ/chi tiết phí, lịch sử thanh toán, đặt trước, gia hạn, hồ sơ và đổi mật khẩu.
- Giao diện PAPER & PAGE: xanh rừng, giấy kem, điểm nhấn màu đồng, responsive, ảnh bìa Open Library theo ISBN và bìa dự phòng.

## Yêu cầu môi trường

- PHP 8.2 trở lên với `pdo`, `pdo_mysql`, `mbstring`.
- MySQL 8.x hoặc MariaDB tương thích, InnoDB và tài khoản có quyền tạo database/bảng khi chạy setup.
- Apache với `mod_rewrite` hoặc PHP built-in server để phát triển.
- Composer không bắt buộc khi chạy ứng dụng; autoload PSR-4 gọn được cung cấp trong `bootstrap/autoload.php`.

## Chạy trên Windows (PowerShell)

1. Cài PHP 8.2+ (hoặc dùng PHP đi kèm XAMPP/Laragon), MySQL/MariaDB và bật extension `pdo_mysql`, `mbstring`.
2. Mở PowerShell tại thư mục project, sao chép cấu hình:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Sửa `.env`: đặt tài khoản MySQL, tên database, email quản trị và mật khẩu quản trị riêng mạnh. Không đưa `.env` lên Git hoặc web.
4. Khởi động MySQL rồi áp dụng migration nền tảng và nạp dữ liệu danh mục/sách ban đầu:

   ```powershell
   php database/setup.php
   ```

   Các migration `001`–`006` được ghi nhận trong bảng `schema_migrations`; chạy lại lệnh này không xóa dữ liệu hiện có.

5. Seed một tài khoản quản trị local:

   ```powershell
   php database/seed.php
   ```

   Mật khẩu quản trị được băm trước khi lưu. Ứng dụng không hiển thị tài khoản mẫu trên trang đăng nhập.

6. Chạy ứng dụng:

   ```powershell
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

   Mở `http://127.0.0.1:8000`. Với Apache, đặt document root vào thư mục `public/` là cấu hình khuyến nghị. File `.htaccess` tại root cũng hỗ trợ chuyển tiếp URL và chặn truy cập các thư mục nhạy cảm khi máy chủ cho phép override.

Đặt `APP_ENV=production` và `APP_DEBUG=false` khi triển khai thật. Đóng đăng ký công khai bằng `ALLOW_PUBLIC_REGISTRATION=false`. Cấu hình vận hành nằm trong `.env`; trang Cài đặt chỉ hiển thị trạng thái và không tiết lộ mật khẩu kết nối.

## Kiểm thử

```powershell
php tests/lint.php
php tests/run.php
node --check public/assets/js/app.js
```

`tests/run.php` độc lập database. Tất cả test MySQL ghi dữ liệu yêu cầu DB_NAME theo mẫu paper_page_test_...; không chạy vào database ứng dụng. Quy trình:

```powershell
php tests/prepare_test_database.php
$env:DB_NAME = (Get-Content storage/test_database_name.txt -Raw).Trim()
php tests/security.php
php tests/mysql_completion.php
php tests/mysql_phase3.php
php tests/mysql_phase4.php
php tests/mysql_concurrency.php
php tests/setup_fresh.php
php tests/prepare_http_fixtures.php
php tests/mysql_reminders.php
Remove-Item Env:DB_NAME
```

Lượt nghiệm thu 08/10/2026: lint 92 PHP, smoke 10, security 15, MySQL phase3 18/phase4 21/completion 26/reminder 5; concurrency và cài mới PASS. Edge/Playwright chạy 273 checks và 110 trang/viewport tại 1440/1366/1024/768/390px. Xem [TEST_REPORT](docs/TEST_REPORT.md) và [bàn giao](docs/FINAL_HANDOVER.md).

## Cấu trúc chính

```text
app/
  Controllers/       Điều phối request và kiểm tra quyền
  Core/              Router, PDO, session, CSRF, view, config
  Repositories/      Truy vấn MySQL
  Services/          Nghiệp vụ đăng nhập và mượn/trả
  Views/             Layout, trang công khai, quản trị, độc giả
bootstrap/           Autoload và cấu hình ứng dụng
database/            Schema, migration/setup, seed sách và tạo quản trị viên
docs/                Audit và design system
public/              Document root, front controller và tài sản tĩnh
tests/               Kiểm tra cú pháp và smoke test
```

## Dữ liệu và giới hạn

- Sách/thể loại mẫu được nạp bằng `database/setup.php`; không có reader/admin mặc định nếu chưa chạy seed/đăng ký.
- Fixture demo mở rộng chỉ chạy opt-in ở local: đặt `SEED_DEMO_DATA=true`, sau đó chạy `php database/seed_demo.php`. Lệnh này thêm 21 sách gắn nhãn demo, tài khoản thủ thư/độc giả riêng và các phiếu/đặt trước/phí mẫu; mật khẩu thiếu được tạo và lưu trong `.env` mà không in ra terminal. Chỉ chạy trên database dev.
- Setup ghi version migration vào `schema_migrations`; `002` tạo bản sao và chi tiết mượn; `003` thêm chính sách, reservation và thông báo; `004` lưu snapshot gia hạn; `005` thêm ledger phí/thanh toán/miễn giảm; `006` chuẩn hóa authors/publishers và backfill liên kết cho catalog hiện có. Bảng `loans` và cột metadata legacy được giữ tương thích. Không chạy lệnh reset trên database có dữ liệu.
- PDF export và RBAC granular chưa được triển khai. Checkout/payment có transaction, row lock và constraint; kiểm thử concurrency hai session thật đã PASS trên DB test riêng. Đây là giới hạn ngoài các luồng hiện tại; xem [docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md) và [docs/TEST_REPORT.md](docs/TEST_REPORT.md).
- Seed nền tạo 9 sách mẫu; fixture opt-in `database/seed_demo.php` bổ sung 21 sách demo và các tài khoản/luồng nghiệp vụ tổng hợp. Dữ liệu fixture được đánh dấu trong mã sách, thẻ độc giả và ghi chú phiếu.
- Ảnh bìa ISBN gọi Open Library; ảnh không khả dụng sẽ chuyển về bìa CSS. Ảnh hero tải từ Unsplash; nội dung chính vẫn dùng được khi ảnh ngoài không tải.
- Chính sách nghiệp vụ có thể cập nhật trong trang Cài đặt; thông tin bí mật/môi trường vẫn chỉ cấu hình trong `.env`.
- Trạng thái hỗ trợ php/MySQL thực tế phụ thuộc phiên bản/cấu hình cài đặt. Hãy chạy các kiểm tra trên máy mục tiêu trước khi triển khai production.
- Các `index.html`, `dashboard.html`, `css/` và `js/` cũ là tài liệu của bản frontend trước đây; entry point PHP/MySQL chính thức là `public/index.php`. Root `.htaccess` chặn các trang frontend cũ khi chạy Apache.

Tài liệu đồ án: [audit](docs/PROJECT_AUDIT.md), [yêu cầu](docs/REQUIREMENTS.md), [kiến trúc](docs/SYSTEM_ARCHITECTURE.md), [use cases](docs/USE_CASES.md), [quy tắc nghiệp vụ](docs/BUSINESS_RULES.md), [thiết kế](docs/DESIGN_SYSTEM.md), [database](docs/DATABASE_DESIGN.md), [data dictionary](docs/DATA_DICTIONARY.md), [ERD](docs/ERD.md), [sơ đồ hệ thống](docs/DIAGRAMS.md), [cài đặt](docs/INSTALLATION_GUIDE.md), [hướng dẫn người dùng](docs/USER_GUIDE.md), [routes](docs/API_DOCUMENTATION.md), [bảo mật](docs/SECURITY_CHECKLIST.md), [triển khai](docs/DEPLOYMENT_GUIDE.md), [kiểm thử](docs/TEST_REPORT.md), [bàn giao](docs/FINAL_HANDOVER.md), [migration](docs/MIGRATION_GUIDE.md) và [kế hoạch](docs/IMPLEMENTATION_PLAN.md).
