# PAPER HAVEN — HỆ THỐNG QUẢN LÝ THƯ VIỆN TRỰC TUYẾN
### ĐỒ ÁN MÔN HỌC: LẬP TRÌNH MÃ NGUỒN MỞ (OPEN SOURCE PROGRAMMING)

[![PHP 8.3](https://img.shields.io/badge/PHP-8.3.33-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL 8.4](https://img.shields.io/badge/MySQL-8.4.3%20LTS-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/badge/Automated%20Tests-100%25%20PASS%20(93%2F93)-brightgreen?style=flat-square)]()
[![Code Standard](https://img.shields.io/badge/Code%20Style-Strict%20Types%20%26%20PSR--4-blue?style=flat-square)]()

---

## 1. GIỚI THIỆU TỔNG QUAN

**Paper Haven** là hệ thống quản lý thư viện trực tuyến toàn diện được thiết kế và xây dựng theo mô hình **Phân tầng (Layered Architecture)** kết hợp mẫu kiến trúc **Model-View-Controller (MVC)** hướng đối tượng trong **PHP 8.3 Native** và **MySQL 8.4**.

Dự án ra đời nhằm cung cấp một giải pháp quản lý thư viện hiện đại, minh bạch, bảo mật cao và vận hành độc lập hoàn toàn bằng các công nghệ mã nguồn mở, phục vụ trọn vẹn 3 nhóm người dùng: **Quản trị viên (Admin)**, **Thủ thư (Librarian)** và **Độc giả (Reader)**.

### Điểm nhấn kỹ thuật & Nghiệp vụ nổi bật:
* **Mô hình Quản lý Bản sao Vật lý (Copy-level Tracking):** Mỗi cuốn sách được quản lý theo từng bản sao độc lập với mã vạch (`copy_code`), vị trí kệ (`shelf_location`), tình trạng vật lý và trạng thái (`available`, `on_loan`, `damaged`, `pending_audit`).
* **Đạo đức Dữ liệu Khoa học (Real Books Only):** 100% danh mục sách thực sự tồn tại trên thị trường xuất bản. Cam kết **không sử dụng ảnh bìa do AI tạo ra** và **không tự sinh ISBN ảo**.
* **Công cụ Sinh Mã vạch & QR Code Độc lập:** Tạo mã vạch chuẩn **Code 128** và mã phản hồi nhanh **QR Code** dạng vector SVG thuần túy qua `BarcodeService` và `QrCodeService`, không phụ thuộc vào bất kỳ API hay thư viện đóng bên ngoài.
* **Hệ thống Lưu thông & Quyết toán Phí phạt Chuyên sâu:** Quy trình mượn - trả - gia hạn khép kín, tự động tính phí phạt trễ hạn lũy tiến hàng ngày, phân bổ thanh toán FIFO và hỗ trợ miễn giảm phạt có thẩm quyền.
* **Biên nhận Điện tử (E-Receipts):** Kết xuất biên nhận mượn sách, trả sách và phiếu thu tiền phạt dạng in nhiệt chuyên nghiệp kèm mã vạch tra cứu tức thì.
* **Hàng đợi Giữ chỗ Tự động (FIFO Reservation Queue):** Tự động giữ sách và gửi thông báo khi có bản sao hoàn trả về thư viện.
* **Kiểm kê Kho sách Thực địa (Inventory Audit):** Hỗ trợ thủ thư quét mã vạch đối soát tồn kho thời gian thực, phát hiện sách sai kệ, sách mất hoặc hư hại.
* **Trợ lý Thư viện Thông minh (Chatbot):** Cơ chế lai tích hợp sẵn bộ phân tích ngữ nghĩa Regex Engine nội bộ (hoạt động ngoại tuyến không cần Internet), kết hợp tùy chọn kết nối Google Gemini API khi có API Key.

---

## 2. KIẾN TRÚC HỆ THỐNG & CÔNG NGHỆ

```text
QLTV_MaNguonMo-github-stage/
├── app/                        # Ứng dụng lõi (Namespace: Library\)
│   ├── Controllers/            # 12 Bộ điều khiển HTTP
│   ├── Core/                   # Framework nền tảng (Database, Router, View, Permissions)
│   ├── Repositories/           # Tầng truy xuất dữ liệu MySQL (Prepared Statements)
│   ├── Services/               # Tầng logic nghiệp vụ chuyên sâu (Circulation, Fines, Audits, Barcode)
│   ├── Views/                  # Tầng giao diện người dùng (Bố cục Public & Staff)
│   └── Support/                # Hàm tiện ích toàn cục (helpers.php)
├── bootstrap/                  # Khởi tạo ứng dụng & nạp cấu hình
├── database/                   # Quản lý 16 tệp Migration & Schema
├── docs/                       # Toàn bộ tài liệu kỹ thuật & Báo cáo đối soát
│   └── submission/             # 10 tệp Hồ sơ Bàn giao chính thức nộp môn
├── public/                     # Thư mục gốc web công khai (Single Entry Point: index.php)
│   ├── assets/                 # CSS, JavaScript, Web Fonts, Book Covers
│   └── uploads/                # Tệp người dùng tải lên được kiểm duyệt
├── storage/                    # Log hệ thống, ảnh đối soát, bộ đệm
├── tests/                      # Bộ kiểm thử tự động toàn diện (Lint, Smoke, Business, Postflight)
├── composer.json               # Cấu hình nạp mã nguồn tự động PSR-4
└── .env.example                # Mẫu thiết lập môi trường chuẩn
```

---

## 3. HƯỚNG DẪN CÀI ĐẶT & CHẠY NHANH (QUICK START)

### Yêu cầu môi trường:
- PHP >= 8.2 (Khuyến nghị **PHP 8.3.33** với PDO MySQL, mbstring, json, gd).
- MySQL >= 8.0 (Khuyến nghị **MySQL 8.4.3 LTS**).
- Khuyên dùng môi trường **Laragon 6.0+** trên Windows.

### Các bước cài đặt:

1. **Khởi tạo tệp môi trường `.env`:**
   ```bash
   cp .env.example .env
   ```
   *Cấu hình thông số kết nối CSDL trong tệp `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).*

2. **Tạo Cơ sở dữ liệu MySQL:**
   ```sql
   CREATE DATABASE IF NOT EXISTS `paper_page_library` 
   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Chạy di chuyển cấu trúc CSDL (Migrations):**
   ```bash
   php database/migrate.php
   ```

4. **Nạp autoloader của Composer:**
   ```bash
   composer dump-autoload
   ```

5. **Khởi chạy máy chủ phát triển:**
   ```bash
   php -S 127.0.0.1:8010 -t public public/index.php
   ```
   Truy cập hệ thống tại: **`http://127.0.0.1:8010`**

---

## 4. TÀI KHOẢN MẪU ĐỂ TRẢI NGHIỆM

| Vai trò | Email đăng nhập | Mật khẩu mẫu | Quyền hạn chính |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@paperpage.local` | `Admin@123456` | Toàn quyền hệ thống, xem nhật ký kiểm toán, cấu hình phân quyền |
| **Thủ thư (Librarian)** | `demo-librarian@paperpage.local` | `Librarian@123` | Lập phiếu mượn/trả, thu tiền phạt, kiểm kê kho, quản lý sách |
| **Độc giả (Reader)** | `demo-reader@paperpage.local` | `Reader@123` | Tra cứu sách, đặt trước FIFO, đánh giá, xem lịch sử mượn |

---

## 5. BỘ LỆNH KIỂM THỬ TỰ ĐỘNG (AUTOMATED TEST SUITE)

Hệ thống tích hợp sẵn các kịch bản kiểm thử tự động, có thể thực thi ngay từ dòng lệnh:

```bash
# 1. Phân tích cú pháp tĩnh 170 tệp mã nguồn
php tests/lint.php

# 2. Kiểm thử luồng khởi tạo và các dịch vụ cơ bản
php tests/run.php

# 3. Kiểm thử phân hệ mượn trả & tính phí phạt
php tests/test_v8_features.php

# 4. Kiểm thử đợt kiểm kê tồn kho và quét mã vạch
php tests/test_v8_inventory_audit_deep.php

# 5. Kiểm thử độ bền & bảo mật trợ lý Chatbot
php tests/test_v8_chatbot_hardening.php

# 6. Kiểm tra các bất biến toàn vẹn dữ liệu CSDL
php tests/postflight_check.php

# 7. Kiểm thử tích hợp nghiệp vụ thực tế 3 vai trò
php tests/test_v12_2_postflight_business.php

# 8. Kiểm toán chỉ số CSDL độc lập (Read-only Metrics)
php storage/verify_readonly_metrics.php
```

> **Kết quả nghiệm thu:** **100% PASS trên toàn bộ 93 tiêu chí kiểm định kỹ thuật**.

---

## 6. HỒ SƠ BÀN GIAO MÔN HỌC (SUBMISSION DOSSIER)

Bộ tài liệu hoàn chỉnh phục vụ nộp môn và báo cáo đồ án được lưu trữ chi tiết trong thư mục `docs/submission/`:

1. [01_GIOI_THIEU_DE_TAI.md](docs/submission/01_GIOI_THIEU_DE_TAI.md) — Tổng quan đề tài, mục tiêu và phạm vi dự án.
2. [02_CONG_NGHE_MA_NGUON_MO.md](docs/submission/02_CONG_NGHE_MA_NGUON_MO.md) — Kiến trúc công nghệ mã nguồn mở & Bản quyền phần mềm tự do (FOSS Stack).
3. [03_KIEN_TRUC_HE_THONG.md](docs/submission/03_KIEN_TRUC_HE_THONG.md) — Kiến trúc phần mềm MVC OOP, sơ đồ luồng & tổ chức mã nguồn.
4. [04_SO_DO_USE_CASE_VA_PHAN_QUYEN.md](docs/submission/04_SO_DO_USE_CASE_VA_PHAN_QUYEN.md) — Sơ đồ Use Case, vai trò & Ma trận phân quyền hành động (RBAC).
5. [05_SO_DO_ERD_VA_DATABASE.md](docs/submission/05_SO_DO_ERD_VA_DATABASE.md) — Bản thiết kế cơ sở dữ liệu (ERD, 29 bảng, ràng buộc toàn vẹn).
6. [06_QUY_TRINH_NGHIEP_VU.md](docs/submission/06_QUY_TRINH_NGHIEP_VU.md) — Quy trình nghiệp vụ thư viện chuyên sâu (Circulation, Fines, Audit, FIFO Reservation).
7. [07_HUONG_DAN_CAI_DAT_VA_TRIEN_KHAI.md](docs/submission/07_HUONG_DAN_CAI_DAT_VA_TRIEN_KHAI.md) — Hướng dẫn cài đặt, cấu hình môi trường & triển khai hệ thống.
8. [08_HUONG_DAN_SU_DUNG.md](docs/submission/08_HUONG_DAN_SU_DUNG.md) — Sổ tay hướng dẫn sử dụng chi tiết cho Admin, Thủ thư và Độc giả.
9. [09_BAO_CAO_KIEM_THU.md](docs/submission/09_BAO_CAO_KIEM_THU.md) — Báo cáo kiểm thử tự động, kết quả đối soát & ma trận QA.
10. [10_HAN_CHE_VA_HUONG_PHAT_TRIEN.md](docs/submission/10_HAN_CHE_VA_HUONG_PHAT_TRIEN.md) — Đánh giá đồ án, các hạn chế thực tế & định hướng phát triển (Laravel 11+, RFID, Z39.50).

---

## 7. BẢN QUYỀN & GIẤY PHÉP (LICENSE)

Dự án được phân phối dưới giấy phép **[MIT License](LICENSE)**. Toàn bộ mã nguồn mở tự do phục vụ mục đích nghiên cứu, học tập và phát triển ứng dụng quản lý thư viện phi thương mại.
