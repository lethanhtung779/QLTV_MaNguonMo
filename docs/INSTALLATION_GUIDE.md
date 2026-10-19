# Cài đặt và triển khai

1. Clone nhánh được phê duyệt; sprint chưa push. Workspace hiện tại chọn QLTV_MaNguonMo-github-stage.
2. Terminal Laragon, bật MySQL; kiểm tra php -v, php -m, composer --version.
3. composer install --no-interaction --prefer-dist. PDF dependency khai báo trong composer.lock.
4. Copy .env.example → .env. Đặt DB_NAME riêng, kết nối MySQL, Admin email/mật khẩu mạnh. Không commit .env.
5. php database/setup.php rồi php database/seed.php. Migrations 001–008 theo thứ tự, không reset.
6. Demo local: APP_ENV=local, SEED_DEMO_DATA=true, php database/seed_demo.php. Mật khẩu sinh riêng vào .env. Admin tạo Thủ thư qua giao diện; form thẻ mới có email/mật khẩu Độc giả.
7. php -S 127.0.0.1:8010 -t public public/index.php. Mở http://127.0.0.1:8010/login; assets tại /assets/ phải trả 200.
8. Apache: DocumentRoot public/, AllowOverride All, bật rewrite; không trỏ vào root source.
9. upload_max_filesize ≥5M, post_max_size ≥8M; GD/fileinfo/DOM/mbstring/PDO MySQL hoạt động. Web server cần quyền ghi storage/cache, logs, uploads; không cho thực thi upload.
10. Public deployment: HTTPS, APP_ENV=production, APP_DEBUG=false, tắt demo seed. Session HttpOnly/Lax, Secure khi HTTPS. Reverse proxy cần cấu hình HTTPS đúng ở web server.
11. Lập lịch php database/maintenance.php mỗi ngày/giờ. Sprint không tự cấu hình Windows Task Scheduler.
12. Chạy `php database/backup.php`; script tạo SQL consistent snapshot cùng file `.sha256` và `.json` metadata trong `storage/backups`. Kiểm tra restore bằng `php tests/backup_restore.php <đường-dẫn-sql>`; script luôn tạo schema test mới. Sao lưu cả upload sang vị trí riêng có kiểm soát. SQL có hash mật khẩu và dữ liệu độc giả, không công khai.

## Nâng cấp

Sao lưu trước; setup thêm bảng quyền và cột tình trạng trả. Bản trả lịch sử trước migration 008 giữ return_condition=NULL, không suy diễn từ copy hiện tại. Không reset. Seed Admin không đổi mật khẩu tài khoản đã tồn tại.

## Cài mới được nghiệm thu

Clone GitHub nhánh minh ở commit e78f09a vào thư mục riêng. Vì chưa push sprint, áp dụng source local đang nghiệm thu lên clone; không copy vendor/storage/.env cũ. Composer từ lock, .env riêng, DB paper_page_test_clone_..., migration/Admin seed/demo seed, server 8012. CRUD/mượn/trả/roles/assets được test trên bản này. Remote hiện tại chưa có thay đổi sprint.

## QA riêng

prepare_test_database.php tạo snapshot DB paper_page_test_...; đặt DB_NAME theo storage/test_database_name.txt. Test ghi dữ liệu từ chối tên khác. Browser tests cần Playwright riêng:

~~~powershell
npm install --prefix storage/browser-qa playwright
$env:QA_BASE_URL="http://127.0.0.1:8011"
$env:QA_BROWSER="C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
~~~

Server phải dùng DB_NAME test, chuẩn bị fixtures theo README. PDF QA cần pip install pymupdf. setup_fresh.php kiểm tra cài mới/idempotency. MariaDB, Linux/Nginx, HTTPS public và tải lớn chưa được nghiệm thu độc lập.
