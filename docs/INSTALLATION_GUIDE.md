# Cài đặt Laragon Windows

PHP 8.2+ với PDO MySQL/mbstring, MySQL 8.x, Apache rewrite hoặc PHP built-in server. Đã kiểm chứng PHP 8.3.33/MySQL 8.4.3. Source thật là thư mục có public/index.php, app/ và database/.

## Cài máy mới

```powershell
Copy-Item .env.example .env
```

Sửa .env riêng: DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD; APP_URL/APP_BASE_PATH theo vị trí; SESSION_TIMEOUT mặc định 1800 giây; SEED_ADMIN_EMAIL/PASSWORD dùng mật khẩu mạnh riêng. Không đưa .env lên web/Git/báo cáo.

```powershell
php database/setup.php
php database/seed.php
php -S 127.0.0.1:8000 -t public public/index.php
```

Setup áp dụng migration 001–006 và tạo 9 sách/63 copy/metadata ngay lần đầu; chạy lại không reset dữ liệu. Seed admin tạo/cập nhật role, không reset password tài khoản đã tồn tại. Với database hiện tại đã setup/seed, chỉ cần bật MySQL và chạy web server. PHP không nằm PATH: dùng Terminal Laragon hoặc gọi php.exe trong bin/php phiên bản đã chọn.

Apache DocumentRoot trỏ public/, bật mod_rewrite/AllowOverride. APP_BASE_PATH dùng khi chạy dưới đường dẫn con. Runtime cần quyền ghi storage/logs và storage/cache/login_attempts. Production: APP_ENV=production, APP_DEBUG=false, HTTPS và tài khoản DB giới hạn quyền.

## Demo

Chỉ cho database local mới cần demo: đặt SEED_DEMO_DATA=true rồi php database/seed_demo.php. Fixture thêm 21 sách, tài khoản staff/reader và phiếu mẫu; không reset. Email/mật khẩu lấy riêng từ SEED_DEMO_* trong .env; không in/chia sẻ password công khai. Database hiện tại đã có demo, không cần chạy lại.

## Kiểm thử an toàn

```powershell
php tests/lint.php
php tests/run.php
php tests/security.php
node --check public/assets/js/app.js
php tests/prepare_test_database.php
$env:DB_NAME = (Get-Content storage/test_database_name.txt -Raw).Trim()
php tests/mysql_completion.php
php tests/mysql_phase3.php
php tests/mysql_phase4.php
php tests/mysql_concurrency.php
php tests/setup_fresh.php
php tests/prepare_http_fixtures.php
php tests/mysql_reminders.php
```

Clone tạo database test tên mới, giữ nguyên database nguồn. Guard từ chối các test ghi nếu DB_NAME không phải paper_page_test_... . Test completion/HTTP giữ fixture trong DB test để đối chiếu; không dọn bằng lệnh reset database chính.

Browser: npm.cmd install --prefix storage/browser-qa playwright --no-audit --no-fund. Khởi chạy PHP server 127.0.0.1:8011 với DB_NAME test và SESSION_NAME=paper_page_qa; ở terminal khác chạy node tests/browser_completion.cjs. Script dùng Edge Windows tại C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe; điều chỉnh đường dẫn nếu máy khác. Khi quay lại app chính: Remove-Item Env:DB_NAME và dừng server QA trước khi mở server demo.

## Backup

Không đặt mật khẩu vào lệnh; -p yêu cầu nhập riêng. Tạo storage/backups (bị ignore), dùng mysqldump của Laragon:

```powershell
New-Item -ItemType Directory -Force storage/backups
mysqldump --single-transaction --routines --triggers --hex-blob --set-gtid-purged=OFF -h 127.0.0.1 -u root -p --result-file=storage/backups/library.sql paper_page_library
```

Dùng --result-file để tránh lỗi encoding do PowerShell redirect. Kiểm tra file và thử restore vào database khác trước khi dùng cho nộp/khôi phục; lượt này chưa chạy restore backup. Không import đè database đang dùng. Khi gửi bài, chuyển bản backup qua kênh riêng sau khi xử lý thông tin cá nhân/hash phù hợp.

## Bảo trì

php database/maintenance.php cập nhật expiry reservation, phí và reminder. Có thể chạy hàng ngày bằng Windows Task Scheduler: Program=php.exe, Arguments=database/maintenance.php, Start in=source root. Thông báo vẫn phát sinh khi reader mở account/notifications; Task Scheduler chưa tự đăng ký trong lượt bàn giao.
