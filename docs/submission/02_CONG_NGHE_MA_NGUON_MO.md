# ĐỒ ÁN MÔN HỌC: LẬP TRÌNH MÃ NGUỒN MỞ
## PHẦN 2: KIẾN TRÚC CÔNG NGHỆ MÃ NGUỒN MỞ & BẢN QUYỀN (FOSS STACK)

---

## 1. NỀN TẢNG CÔNG NGHỆ MÃ NGUỒN MỞ SỬ DỤNG

Dự án **Paper Haven** được xây dựng hoàn toàn dựa trên các công nghệ mã nguồn mở phổ biến, hiện đại và tuân thủ các giấy phép bản quyền phần mềm tự do (FOSS - Free and Open Source Software):

| Thành phần | Công nghệ / Thư viện | Phiên bản | Giấy phép (License) | Vai trò trong hệ thống |
| :--- | :--- | :--- | :--- | :--- |
| **Ngôn ngữ cốt lõi** | PHP | 8.3.33 x64 | PHP License 3.01 | Xử lý logic máy chủ, triển khai mô hình MVC OOP Strict Types |
| **Hệ quản trị CSDL** | MySQL Community Server | 8.4.3 LTS | GPL v2 / FOSS Exception | Lưu trữ cơ sở dữ liệu quan hệ, thực thi transaction ACID, Foreign Keys |
| **Máy chủ Web phát triển**| Laragon WAMP / PHP Built-in Server | 6.0+ | GPL / MIT | Môi trường máy chủ phát triển cục bộ hiệu năng cao |
| **Quản lý phụ thuộc**| Composer | 2.8+ | MIT License | Quản lý nạp tự động PSR-4 (Autoloading) và các gói thư viện mã nguồn mở |
| **Tạo tài liệu PDF** | Dompdf | 3.0+ | LGPL v2.1 | Kết xuất hóa đơn, biên nhận mượn/trả và thẻ thư viện sang file PDF |
| **Tạo mã vạch & QR** | Native Engine (Custom SVG/HTML) | 1.0 (Built-in) | MIT License | Tạo mã vạch Code 128 và mã phản hồi nhanh QR Code định dạng SVG độc lập không phụ thuộc service ngoài |
| **Biểu tượng giao diện** | Bootstrap Icons | 1.11+ | MIT License | Hệ thống biểu tượng trực quan, nhất quán trên toàn bộ giao diện |
| **Phông chữ tự do** | Inter & Merriweather (Google Fonts) | OFL 1.1 | SIL Open Font License | Hệ thống typography hiển thị tiếng Việt chuẩn mực, thẩm mỹ |
| **Giao diện & CSS** | Custom CSS3 Flexbox/Grid Variables | Native | MIT License | Hệ thống giao diện độc quyền tùy biến cao, không phụ thuộc framework cồng kềnh |

---

## 2. NGUYÊN TẮC TUÂN THỦ MÃ NGUỒN MỞ

1. **Minh bạch mã nguồn (Source Code Transparency):**
   - 100% mã nguồn ứng dụng nằm trong thư mục dự án, không bị mã hóa (obfuscated) hay đóng gói dạng nhị phân đóng kín.
   - Các cấu hình môi trường được tách biệt qua file `.env`, không hardcode thông tin nhạy cảm vào code.

2. **Khả năng tự chủ và chạy ngoại tuyến (Offline Independence):**
   - Hệ sinh thái Paper Haven có khả năng khởi chạy và vận hành hoàn chỉnh 100% trong mạng nội bộ (Local Area Network hoặc Localhost) mà không bắt buộc phải có kết nối Internet.
   - Bộ máy Chatbot thông minh tích hợp sẵn bộ phân tích ngữ nghĩa Regex Engine nội bộ (offline rule-based system), đảm bảo luôn sẵn sàng phục vụ thủ thư và bạn đọc kể cả khi mất kết nối mạng.

3. **Tương thích tiêu chuẩn mở (Open Standards Compliance):**
   - **PSR-4:** Tiêu chuẩn nạp mã nguồn tự động của nhóm PHP-FIG (PHP Framework Interop Group).
   - **PSR-7 & PSR-15 Tư tưởng:** Thiết kế lớp Middleware bảo vệ phiên đăng nhập, kiểm tra CSRF token và phân quyền.
   - **W3C Standards:** HTML5 Semantic, CSS3 Custom Properties, SVG Vector Graphics.
   - **ISO/IEC 15417:** Tiêu chuẩn kỹ thuật mã vạch Code 128 được ứng dụng tạo mã vạch bản sao và biên nhận.

---

## 3. CƠ CHẾ NẠP MÃ NGUỒN TỰ ĐỘNG (PSR-4 AUTOLOADING)

Hệ thống sử dụng tệp `composer.json` cấu hình không gian tên (namespace) `Library\\`:

```json
{
    "name": "paperhaven/library-system",
    "description": "Paper Haven Online Library Management System",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=8.2.0",
        "ext-pdo": "*",
        "ext-pdo_mysql": "*",
        "ext-json": "*",
        "ext-mbstring": "*",
        "dompdf/dompdf": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Library\\": "app/"
        },
        "files": [
            "app/Support/helpers.php"
        ]
    }
}
```

Nhờ quy chuẩn PSR-4, các lớp trong thư mục `app/` được nạp tự động tức thời theo cấu trúc thư mục, loại bỏ hoàn toàn các lệnh `require_once` rải rác, tăng tính module hóa và dễ bảo trì mã nguồn.

---

## 4. TÍNH ĐỘC LẬP VÀ AN TOÀN BẢN QUYỀN DỮ LIỆU

Trong khuôn khổ môn học Lập trình Mã nguồn mở, dự án quán triệt nghiêm ngặt đạo đức dữ liệu khoa học:
- **Không sử dụng hình ảnh bìa sách do trí tuệ nhân tạo (AI Image Generation) tự vẽ lại:** Mọi hình ảnh bìa hiển thị trong thư viện đều được rà soát nguồn gốc (ấn bản NXB Trẻ, NXB Kim Đồng,...), nếu chưa được cấp phép hoặc chưa xác minh 100% ấn bản thì hệ thống sử dụng **Trình giữ chỗ trung tính (Neutral SVG Book Placeholder)** hiển thị rõ tên sách, tác giả và thể loại.
- **Không tự sinh chỉ số ISBN ảo:** Hệ thống phân định rõ ràng giữa ISBN hợp lệ đã đối chiếu (19 đầu sách) và các đầu sách chưa xác minh (110 đầu sách được gán giá trị `NULL` trong CSDL, không tạo số giả mạo).
- **Mã nguồn hoàn toàn có thể phân phối lại theo giấy phép MIT.**
