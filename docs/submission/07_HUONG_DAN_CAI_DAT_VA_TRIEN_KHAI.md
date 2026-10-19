# ĐỒ ÁN MÔN HỌC: LẬP TRÌNH MÃ NGUỒN MỞ
## PHẦN 7: HƯỚNG DẪN CÀI ĐẶT & TRIỂN KHAI HỆ THỐNG (DEPLOYMENT GUIDE)

---

## 1. YÊU CẦU HỆ THỐNG TỐI THIỂU

Để triển khai và vận hành Paper Haven, máy chủ hoặc máy tính phát triển cần đáp ứng các điều kiện sau:

* **Hệ điều hành:** Windows 10/11, Ubuntu 22.04 LTS hoặc macOS Sonoma.
* **Môi trường đề xuất:** **Laragon 6.0+** (Môi trường WAMP tối ưu cho Windows) hoặc Docker / XAMPP.
* **Phiên bản PHP:** PHP 8.2 hoặc PHP 8.3 trở lên (Hệ thống hiện tại đang kiểm thử tốt nhất trên PHP 8.3.33 x64).
* **Các Extension PHP bắt buộc:**
  - `pdo` & `pdo_mysql` (Kết nối CSDL MySQL)
  - `mbstring` (Xử lý chuỗi Unicode tiếng Việt)
  - `json` (Xử lý dữ liệu trao đổi API)
  - `gd` hoặc `imagick` (Hỗ trợ xử lý ảnh nếu cần)
* **Hệ quản trị CSDL:** MySQL 8.0 hoặc MySQL 8.4 LTS (Hiện tại đang chạy MySQL 8.4.3).
* **Công cụ bổ trợ:** Composer 2.8+ để nạp tự động PSR-4.

---

## 2. QUY TRÌNH CÀI ĐẶT TỪNG BƯỚC (STEP-BY-STEP)

### Bước 1: Chuẩn bị thư mục mã nguồn
Sao chép hoặc giải nén thư mục dự án `QLTV_MaNguonMo-github-stage` vào thư mục gốc của máy chủ web (ví dụ: `C:\laragon\www\paperhaven` hoặc thư mục làm việc của bạn).

### Bước 2: Thiết lập tệp cấu hình môi trường (.env)
Sao chép tệp `.env.example` thành `.env` tại thư mục gốc của dự án:
```bash
cp .env.example .env
```

Mở tệp `.env` và cấu hình các thông số kết nối CSDL phù hợp:
```ini
APP_NAME="Paper Haven"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010
APP_BASE_PATH=""

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paper_page_library
DB_USERNAME=root
DB_PASSWORD=""

# Phí phạt trễ hạn mặc định (VNĐ/ngày)
LIBRARY_DAILY_FINE=5000
LIBRARY_MAX_LOAN_DAYS=14
LIBRARY_MAX_RENEWALS=2
```

### Bước 3: Tạo Cơ sở dữ liệu MySQL
Mở MySQL Console hoặc phpMyAdmin / HeidiSQL trong Laragon và tạo CSDL `paper_page_library`:
```sql
CREATE DATABASE IF NOT EXISTS `paper_page_library` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### Bước 4: Thực thi di chuyển cấu trúc CSDL (Database Migrations)
Khởi chạy lệnh sau trong terminal để tự động áp dụng toàn bộ 16 tệp migration vào cơ sở dữ liệu:
```bash
php database/migrate.php
```

Kết quả mong đợi:
```text
Running migrations for paper_page_library...
[APPLIED] 001_initial_schema
...
[APPLIED] 016_quarantine_simulated_copies_and_clean_isbns
All migrations applied successfully!
```

### Bước 5: Kiểm tra nạp Composer Autoload
Nếu triển khai từ bản sao lưu sạch, chạy lệnh:
```bash
composer dump-autoload
```

### Bước 6: Khởi chạy máy chủ Web phát triển
Khởi động máy chủ PHP tích hợp lắng nghe trên cổng `8010` (trỏ Document Root vào thư mục `public/`):
```bash
php -S 127.0.0.1:8010 -t public public/index.php
```

Bây giờ bạn có thể truy cập website tại địa chỉ: **`http://127.0.0.1:8010`**

---

## 3. TÀI KHOẢN MẪU ĐỂ ĐĂNG NHẬP VÀ KIỂM THỬ

Hệ thống đã có sẵn 3 tài khoản chuẩn đại diện cho 3 vai trò trong thư viện:

| Vai trò | Email đăng nhập | Mật khẩu mặc định | Mục đích kiểm thử |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@paperpage.local` | `Admin@123456` | Quản trị toàn quyền, xem báo cáo, cấu hình phân quyền, xem nhật ký kiểm toán |
| **Thủ thư (Librarian)** | `demo-librarian@paperpage.local`| `Librarian@123` | Lập phiếu mượn trả, thu tiền phạt, kiểm kê kho, quản lý sách |
| **Độc giả (Reader)** | `demo-reader@paperpage.local` | `Reader@123` | Tra cứu sách, đặt trước sách (FIFO), đánh giá sao, xem lịch sử mượn |

---

## 4. BỘ LỆNH KIỂM THỬ VẬN HÀNH NHANH (SMOKE TESTS)

Sau khi cài đặt xong, bạn có thể chạy bộ kiểm thử tự động để xác nhận toàn bộ hệ thống hoạt động hoàn hảo:

```bash
# 1. Kiểm tra cú pháp toàn bộ file PHP
php tests/lint.php

# 2. Kiểm thử luồng nghiệp vụ cơ bản
php tests/run.php

# 3. Kiểm tra toàn vẹn CSDL sau cài đặt
php tests/postflight_check.php

# 4. Kiểm thử nghiệp vụ thực tế 3 vai trò
php tests/test_v12_2_postflight_business.php
```

Tất cả các lệnh trên phải xuất ra thông báo **PASS (100%)**.

---

## 5. XỬ LÝ SỰ CỐ THƯỜNG GẶP (TROUBLESHOOTING)

1. **Lỗi `Access denied for user 'root'@'localhost'`:**
   - Kiểm tra lại mật khẩu root của MySQL trong tệp `.env`. Đối với Laragon mặc định, mật khẩu thường là rỗng (`DB_PASSWORD=""`).
2. **Lỗi `PDOException: Table doesn't exist`:**
   - Hãy chắc chắn bạn đã chạy lệnh `php database/migrate.php` trên đúng tên CSDL được khai báo trong `.env`.
3. **Ảnh bìa sách không hiển thị:**
   - Đảm bảo Document Root được trỏ chính xác vào thư mục `public/`.
   - Kiểm tra đường dẫn tĩnh `public/assets/book-covers/` và quyền đọc tệp của web server.
