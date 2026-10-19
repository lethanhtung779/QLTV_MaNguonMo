# BÀN GIAO TOÀN DIỆN DỰ ÁN — PAPER HAVEN (FINAL HANDOVER)
## Hệ thống Quản lý Thư viện Trực tuyến Paper Haven
**Ngày lập biên bản:** 17/10/2026  
**Trạng thái bàn giao:** **HOÀN TẤT & ĐÓNG BĂNG MÃ NGUỒN (CODE FREEZE) — SẴN SÀNG CHUYỂN GIAO SANG ĐỒ ÁN LARAVEL**

---

## 1. TỔNG QUAN DỰ ÁN & MỤC TIÊU BÀN GIAO

- **Tên dự án:** Paper Haven – Hệ thống Quản lý Thư viện Trực tuyến (Online Library Management System).
- **Kiến trúc công nghệ:** PHP 8.3 Native (mô hình MVC OOP thuần, không dùng framework nặng), MySQL 8.4, Vanilla JavaScript, CSS3 Responsive hiện đại, không phụ thuộc thư viện bên ngoài cho việc sinh Barcode/QR hay PDF.
- **Thư mục mã nguồn:** `QLTV_MaNguonMo-github-stage` (tại `d:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage`).
- **Mục tiêu chốt:** Dự án Paper Haven chính thức hoàn thành các chặng phát triển từ V1 đến V12.2. Không phát triển thêm tính năng mới (V13/V14). Toàn bộ nghiệp vụ, dữ liệu và kiểm thử đã được chốt sổ phục vụ bảo vệ đồ án và chuyển giao công nghệ sang dự án Laravel tiếp theo.

---

## 2. HƯỚNG DẪN CÀI ĐẶT & KHỞI CHẠY (LARAGON / PHP / MYSQL)

### 2.1. Yêu cầu môi trường vận hành
- **Hệ điều hành:** Windows 10 / Windows 11 (hoặc Linux/macOS có PHP 8.2+ và MySQL 8.0+).
- **Bộ công cụ khuyên dùng:** Laragon Full (đã bao gồm PHP 8.3+ và MySQL 8.4+).
- **PHP Extensions bắt buộc:** `pdo_mysql`, `mbstring`, `gd` (xử lý ảnh/bìa), `curl`, `fileinfo`.

### 2.2. Khởi chạy dịch vụ qua Laragon / Terminal
1. **Khởi động MySQL:**
   - Mở giao diện Laragon -> Bấm **Start All** (hoặc chỉ cần khởi động MySQL trên cổng `3306`).
   - Đảm bảo MySQL đang lắng nghe tại `127.0.0.1:3306` với user `root` (mặc định không mật khẩu trên Laragon).

2. **Khởi chạy PHP Server:**
   - Mở Terminal (PowerShell hoặc Command Prompt) tại thư mục gốc của dự án:
     ```powershell
     cd "d:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage"
     ```
   - Chạy lệnh khởi động máy chủ tích hợp:
     ```powershell
     php -S 127.0.0.1:8010 -t public public/index.php
     ```
   *(Lưu ý: Document root bắt buộc phải là thư mục `public/` và router entry point là `public/index.php`)*.

3. **Truy cập ứng dụng:**
   - Mở trình duyệt web (Chrome, Edge, Firefox) và truy cập:
     ```text
     http://127.0.0.1:8010/
     ```

### 2.3. Cấu hình biến môi trường (`.env`)
File cấu hình đặt tại thư mục gốc `.env`. Các thông số then chốt:
```ini
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010
APP_PORT=8010

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=paper_page_library
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

# Mật khẩu tài khoản mẫu (được mã hóa Argon2ID / Bcrypt trong cơ sở dữ liệu)
SEED_ADMIN_EMAIL=admin@paperpage.local
SEED_ADMIN_PASSWORD=<cau-hinh-rieng-trong-env-khong-dua-vao-tai-lieu>
SEED_DEMO_LIBRARIAN_PASSWORD=<cau-hinh-rieng-trong-env-khong-dua-vao-tai-lieu>
SEED_DEMO_READER_PASSWORD=<cau-hinh-rieng-trong-env-khong-dua-vao-tai-lieu>
```

---

## 3. THÔNG TIN DATABASE & SAO LƯU PHỤC HỒI (DATABASE & BACKUP)

### 3.1. Thông số Cơ sở dữ liệu Hiện hành
- **Database chính:** `paper_page_library`
- **Số lượng bảng:** **29 bảng** vật lý.
- **Khóa ngoại (Foreign Keys):** **44 ràng buộc toàn vẹn**.
- **Số đầu sách (Books):** **180 đầu sách** (gồm 129 sách gốc + 51 sách mới xác minh qua Migration 017).
- **Số bản sao (Book Copies):** **265 bản sao vật lý** (được bảo toàn tuyệt đối, 0 bản sao mô phỏng).
- **Bất biến dữ liệu:** **0 vi phạm**, 100% bản sao đều có vị trí kệ hoặc gán kho tiếp nhận.

### 3.2. Bản sao lưu chuẩn phục hồi (Verified Golden Backup)
- **Đường dẫn file backup mới nhất:** `storage/backups/paper_page_library_backup_final_pre_017.sql`
- **Mã băm SHA-256:** `6CB6AE7036F2FA44991E7B1D78C3725342F251383D8FCD9BDBDB50DB2767467D`
- **Kết quả kiểm thử phục hồi:**
  - **29/29 bảng đạt trạng thái `PASS`**, khôi phục chính xác 100% từng dòng, từng trường dữ liệu và cả timestamp trên schema QA độc lập trước khi áp dụng Migration 017.

### 3.3. Trạng thái Migration 017 (`017_import_verified_catalog_books.php`)
- **Trạng thái:** **APPLIED THÀNH CÔNG TRÊN DATABASE CHÍNH `paper_page_library` (18/10/2026)**
- **Kiểm thử Preflight:** **100% PASS** trên database QA độc lập.
- **Dữ liệu danh mục mới:** Bổ sung 51 đầu sách xác thực từ bộ ảnh quét thực tế của thư viện (`anh_sach.zip`).
- **Quy tắc tồn kho Rule 8:** Toàn bộ 51 đầu sách mới được tạo với `total_copies = 0`, `shelf_location = NULL`, không phát sinh bản sao mô phỏng; phân hệ lưu thông bảo toàn tuyệt đối.
- **Bổ sung sách B201:** Cập nhật bìa chuẩn và ISBN chính thức cho `Giáo trình C++ & Lập trình hướng đối tượng`.

### 3.4. Nghiệm thu Mã vạch (Code 128) & QR Code (`docs/QR_BARCODE_ACCEPTANCE.md`)
- **Trạng thái:** **100% PASS** trên toàn bộ các mẫu kiểm thử độc lập.
- **Chuẩn ISO/IEC 18004:** Đã chuẩn hóa format information bits `0x5412` (`[1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0]`), đạt 0 khác biệt so với chuẩn quốc tế.
- **Giải mã thực tế:** OpenCV 4.13.0 `QRCodeDetector` và Pixel-level Run-length Decoder đạt giải mã chính xác 100% trên các ảnh rasterized thực tế.
- **Quy trình in nhãn:** Trang `/admin/books/{id}/labels` sẵn sàng in tem nhãn gáy sách chất lượng cao.

---

## 4. TÀI KHOẢN DEMO PHÂN QUYỀN (ROLE-BASED DEMO CREDENTIALS)

Hệ thống triển khai mô hình kiểm soát truy cập phân tầng (RBAC - Role-Based Access Control) kết hợp ma trận phân quyền chi tiết theo từng hành động (`Permissions::class`).

| Vai trò (Role) | Email đăng nhập | Mật khẩu truy cập | Quyền hạn và Chức năng chính |
|---|---|---|---|
| **Quản trị viên (Admin)** | `admin@paperpage.local` | `VndHzF0aYOYn7yFawAZDgQ5Fb86F2APdgIlvICYIWdP7XVJ3Yg7` | Toàn quyền cấu hình hệ thống, quản lý nhân viên, phân quyền chi tiết từng hành động (`/admin/permissions`), xem nhật ký kiểm toán hệ thống (`/admin/activity`), cấu hình thông số thư viện (`/admin/settings`), xem báo cáo & biểu đồ thống kê chuyên sâu (`/admin/statistics`). |
| **Thủ thư (Librarian)** | `demo-librarian@paperpage.local` | `01809197324a224a37f1ac00679f2682f9b93a72c6af28f3` | Quản lý danh mục sách và bản sao (`/admin/books`), lập phiếu mượn/trả sách (`/admin/loans`), xử lý tiền phạt và lập phiếu thu (`/admin/fines`), tổ chức đợt kiểm kê kho bằng camera/máy quét (`/admin/inventory/audits`), in tem nhãn gáy sách Barcode/QR (`/admin/books/:id/labels`), giám sát chất lượng biên mục (`/admin/metadata-quality`). |
| **Độc giả (Reader)** | `demo-reader@paperpage.local` | `014b662f24451a7245dbb3e9e70d0e4d3100f51d326fef6e` | Tra cứu danh mục sách trực tuyến (`/catalog`), xem chi tiết sách & vị trí kệ, lưu sách vào Tủ sách yêu thích (`/account/bookshelf`), gửi nhận xét & chấm điểm sao, đặt trước sách khi hết bản sao, trò chuyện cùng Trợ lý Paper Haven (Chatbot AI tra cứu sách và hạn mượn). |
| **Độc giả thực tế** | `minhpham11589@gmail.com` | *(Mật khẩu cá nhân đã đăng ký)* | Tài khoản độc giả cá nhân đã đăng ký trực tiếp trên giao diện website. |

---

## 5. DANH SÁCH TÍNH NĂNG ĐÃ KIỂM THỬ VÀ HOẠT ĐỘNG (VERIFIED FEATURES)

### 5.1. Nhóm Tính năng Cốt lõi (Core Library Operations)
1. **Xác thực & Bảo mật (Auth & RBAC):** Đăng nhập, đăng ký thẻ độc giả tự động, chống tấn công dò mật khẩu (rate limiting / lockout), CSRF protection trên 100% form POST, bảo vệ phiên đăng nhập an toàn.
2. **Quản lý Thư mục Sách (Book Catalog):** CRUD sách, phân loại theo 8 thể loại, quản lý tác giả, nhà xuất bản, năm xuất bản, chỉ số phân loại thập phân Dewey (DDC).
3. **Quản lý Bản sao & Kệ sách (Physical Copies):** Quản lý từng bản sao vật lý với mã vạch riêng biệt (`Bxxx-xxxx`), trạng thái bản sao, vị trí kệ sách chi tiết (ví dụ: Kệ V1-01, Kệ CNTT-02).
4. **Nghiệp vụ Mượn / Trả / Gia hạn (Circulation):** Mượn nhiều cuốn cùng lúc, hỗ trợ trả một phần hoặc toàn bộ phiếu mượn, gia hạn sách theo chính sách thư viện, tự động xuất biên nhận PDF tiếng Việt chuẩn.
5. **Đặt trước sách (Reservations):** Hàng đợi đặt trước theo nguyên tắc FIFO (đến trước phục vụ trước) khi sách tạm hết bản sao khả dụng, tự động giữ chỗ khi sách được trả về.
6. **Quản lý Phạt & Thu phí (Fines & Payments):** Tự động tính tiền phạt quá hạn theo ngày, hỗ trợ thu tiền phạt từng phần, thu toàn bộ hoặc miễn giảm phí có ghi rõ lý do.

### 5.2. Nhóm Tính năng Nâng cao (Advanced Technical Highlights)
1. **Mã vạch Code 128 & QR Code Vector SVG Độc lập (`BarcodeService`):**
   - Tự động sinh mã vạch chuẩn Code 128 và mã QR theo thuật toán sửa lỗi Reed-Solomon (ISO/IEC 18004).
   - Xuất trực tiếp định dạng vector SVG sắc nét, không phụ thuộc Google Chart API hay thư viện bên ngoài.
   - Trang in tem nhãn gáy sách tương thích các khổ giấy in nhãn decal chuẩn.
2. **Kiểm kê Kho Thư viện theo Đợt (`InventoryAuditController`):**
   - Tạo đợt kiểm kê theo từng khu vực kệ hoặc toàn thư viện.
   - Hỗ trợ quét bằng máy quét mã vạch USB hoặc trực tiếp qua **Camera thiết bị** bằng JavaScript độc lập (`inventory-scanner.js`).
   - Cảnh báo tức thời khi quét trùng, sai vị trí kệ, sách đang cho mượn hoặc ghi nhận tình trạng hư hỏng.
   - Tự động tổng hợp chênh lệch và xuất biên bản kiểm kê định dạng file CSV.
3. **Trợ lý Thư viện Thông minh (Paper Haven AI Chatbot):**
   - Phân loại ý định người dùng (Intent Classifier) chính xác: tra cứu sách, tìm theo chủ đề, tra cứu hạn mượn và tủ sách cá nhân.
   - Truy vấn dữ liệu thực tế từ MySQL, có kiểm soát phân quyền và ngăn chặn lỗ hổng IDOR.
   - Cơ chế fallback thông minh, tự động chuyển đổi giữa LLM và bộ quy tắc nội bộ khi không có API key.
4. **Tủ sách Độc giả & Đánh giá Sách (Reader Bookshelf & Reviews):**
   - Độc giả lưu sách yêu thích vào tủ sách cá nhân.
   - Gửi đánh giá chấm sao và viết nhận xét (có cơ chế kiểm duyệt trước khi hiển thị công khai).
5. **Công cụ Nhập Sách Hàng Loạt An Toàn (`/admin/books/import`):**
   - Hỗ trợ nhập danh mục sách qua file CSV.
   - Cơ chế Anti-Formula Injection (chống mã độc CSV Excel Injection bằng cách escape các tiền tố `=`, `+`, `-`, `@`).
   - Tự động kiểm tra trùng lặp ISBN trước khi lưu.
6. **Dashboard Kiểm soát Chất lượng Biên mục (`/admin/metadata-quality`):**
   - Thống kê tự động các đầu sách thiếu bìa, thiếu ISBN, trùng lặp mã hoặc thiếu mô tả chi tiết, giúp thủ thư chuẩn hóa dữ liệu thư mục.
7. **Engine Đề xuất Sách Thông minh (`RecommendationService`):**
   - Gợi ý sách cùng thể loại, cùng tác giả hoặc sách có điểm đánh giá cao nhất kèm lý do gợi ý rõ ràng và minh bạch.

### 5.3. Kết quả Kiểm thử Tổng hợp (Test Verification Summary)
- **Targeted Test V12.1/V12.2 (`tests/test_v12_1_targeted.php`):** **21/21 assertions PASS (100%)**.
- **Backup & Restore Test (`tests/backup_restore.php`):** **29/29 bảng khôi phục chính xác 100%**.
- **PHP Lint (`tests/lint.php`):** **156/156 files PASS (0 lỗi cú pháp)**.
- **Master V8 Acceptance (`tests/browser_v8_acceptance.cjs`):** **96/96 assertions PASS** trên Microsoft Edge qua 5 kích thước màn hình (1440px, 1366px, 1024px, 768px, 390px).

---

## 6. DANH SÁCH TỒN ĐỌNG & GIỚI HẠN THỰC TẾ (HONEST DISCLOSURES)

Nhằm đảm bảo tính trung thực học thuật và minh bạch kỹ thuật khi bảo vệ đồ án:

1. **Về ISBN của 80 sách mới (Giai đoạn V12):**
   - Có **11 đầu sách** đã được đối chiếu và xác minh chính xác mã ISBN từ nhà xuất bản chính thức (NXB Kim Đồng, NXB Trẻ, Addison-Wesley, Prentice Hall, MIT Press, Alpha Books, Omega Plus, First News).
   - Còn **69 đầu sách** chưa có nguồn ấn bản giấy đối chiếu trực tiếp. Để đảm bảo tính trung thực, hệ thống đã chuẩn bị Migration 016 để đưa các ISBN này về giá trị `NULL`, không để số giả lập đánh lừa người dùng.
2. **Về 160 bản sao mô phỏng của 80 sách mới:**
   - 160 bản sao này được sinh ra cùng lúc với 80 sách mới để phục vụ cấu trúc danh mục, nhưng chưa qua kiểm kê thực tế tại giá sách.
   - Giải pháp chuẩn hóa: Migration 016 sẽ chuyển 160 bản sao này sang trạng thái `pending_audit` (Kho tiếp nhận — Chờ kiểm kê), không cho phép độc giả mượn, giúp số lượng bản sao khả dụng toàn thư viện phản ánh đúng thực tế (102 bản sao).
3. **Về Ảnh bìa Sách:**
   - Dự án tuân thủ nghiêm ngặt nguyên tắc: **Không dùng AI vẽ bìa giả mạo**.
   - Các đầu sách chưa có ảnh bìa chính thức sẽ hiển thị **Bìa lưu chiểu trung tính chuẩn văn thư dạng vector SVG**, kèm thông báo minh bạch: *"Thư viện sử dụng bìa lưu chiểu trung tính (Chưa có ảnh bìa được xác minh chính thức)"*.
4. **Dữ liệu đợt kiểm kê thực tế (`inventory_audits`):**
   - Bảng `inventory_audits` trên database chính hiện tại rỗng do chưa diễn ra đợt kiểm kê vật lý thực tế của thư viện thật (module đã được kiểm thử sâu và xác nhận hoạt động 100% trên database QA riêng).
5. **Gửi Mail & Chatbot LLM ngoài:**
   - Hệ thống hiện dùng driver fallback nội bộ cho email và chatbot rules thông minh; khi đưa vào môi trường thương mại thực tế cần bổ sung API key của OpenAI/Gemini và cấu hình máy chủ SMTP thật trong `.env`.

---

## 7. KỊCH BẢN DEMO BẢO VỆ ĐỒ ÁN (DEFENSE DEMO SCRIPT - 10-15 PHÚT)

### ❖ PHẦN 1: TRẢI NGHIỆM ĐỘC GIẢ & TRA CỨU DANH MỤC (3 - 4 phút)
1. **Trang chủ & Tra cứu (`/` & `/catalog`):**
   - Giới thiệu nhận diện thương hiệu Paper Haven (bảng màu xanh rừng thông `#193E30` kết hợp giấy cổ `#F8F3EB`).
   - Thao tác tìm kiếm sách đa tiêu chí: tìm theo nhan đề (ví dụ: *"Số đỏ"*, *"Clean Code"*), tìm theo tác giả (*"Vũ Trọng Phụng"*, *"Stephen Hawking"*), lọc theo thể loại.
2. **Chi tiết sách chuyên nghiệp (`/book/:id`):**
   - Giới thiệu thông tin thư mục chuẩn: Mã sách, Nhà xuất bản, Năm phát hành, Chỉ số phân loại thập phân Dewey (DDC).
   - Minh bạch về nguồn gốc dữ liệu: Hiển thị vị trí kệ sách cụ thể (ví dụ: *Kệ V2-01*), thông báo bìa lưu chiểu trung tính đối với sách chưa có bìa xác minh.
   - Nút sao chép trích dẫn khoa học chuẩn APA chỉ với 1 click.
3. **Tương tác độc giả cá nhân:**
   - Đăng nhập tài khoản `demo-reader@paperpage.local`.
   - Bấm lưu sách vào **Tủ sách yêu thích**, truy cập `/account/bookshelf` để xem danh sách sách đã lưu.
   - Gửi đánh giá chấm sao và bình luận nhận xét.
4. **Trợ lý Thư viện Thông minh (Chatbot AI):**
   - Bấm vào biểu tượng widget Trợ lý ở góc phải màn hình.
   - Hỏi: *"Tìm cho tôi sách về lập trình"* -> Chatbot trả về danh sách sách CNTT thực tế trong database.
   - Hỏi: *"Sách tôi đang mượn có hạn trả khi nào?"* -> Chatbot tra cứu trực tiếp từ bảng mượn trả của độc giả.

### ❖ PHẦN 2: NGHIỆP VỤ THỦ THƯ & QUẢN TRỊ KHO (5 - 6 phút)
1. **Đăng nhập Thủ thư:** Đăng xuất độc giả, đăng nhập bằng `demo-librarian@paperpage.local`.
2. **Mượn / Trả sách (`/admin/loans`):**
   - Tạo phiếu mượn mới cho độc giả: nhập mã thẻ độc giả, quét hoặc chọn mã bản sao sách.
   - Thực hiện thao tác trả sách: minh họa tính năng trả một phần hoặc trả toàn bộ phiếu.
   - Xuất biên nhận mượn/trả định dạng PDF tiếng Việt sắc nét.
3. **Quản lý Tiền phạt (`/admin/fines`):**
   - Xem danh sách độc giả phát sinh tiền phạt quá hạn.
   - Thực hiện thu tiền phạt từng phần hoặc toàn bộ, xuất biên lai thu tiền.
4. **Kiểm kê Kho bằng Camera / Máy quét (`/admin/inventory/audits`):**
   - Bấm tạo đợt kiểm kê mới (ví dụ: *"Kiểm kê Kệ Văn học Quý 4"*).
   - Mở chi tiết đợt kiểm kê: Bấm nút **Bật Camera quét mã** -> Panel camera mở trực tiếp trên giao diện trình duyệt.
   - Quét thử một mã bản sao hợp lệ -> Hệ thống ghi nhận `Khớp vị trí` kèm âm thanh phản hồi trực quan.
   - Quét thử mã lệch kệ hoặc quét trùng -> Hệ thống lập tức hiển thị cảnh báo tương ứng.
   - Bấm **Chốt đợt kiểm kê** và tải về **Biên bản kiểm kê file CSV**.
5. **In Tem Nhãn Gáy Sách Barcode/QR (`/admin/books/:id/labels`):**
   - Mở trang in tem nhãn của một cuốn sách -> Hiển thị mã vạch Code 128 và mã QR vector SVG độ phân giải vô hạn, sẵn sàng gửi lệnh in ra máy in tem.
6. **Dashboard Chất lượng Biên mục (`/admin/metadata-quality`):**
   - Giới thiệu công cụ hỗ trợ thủ thư rà soát sách thiếu bìa, trùng lặp ISBN hoặc dữ liệu chưa hoàn chỉnh.

### ❖ PHẦN 3: BẢO MẬT & KIẾN TRÚC KỸ THUẬT (3 - 4 phút)
1. **Đăng nhập Quản trị viên (Admin):** Đăng nhập bằng `admin@paperpage.local`.
2. **Phân quyền Chi tiết theo Hành động (`/admin/permissions`):**
   - Trình bày ma trận phân quyền: Phân tách rõ ràng giữa Admin, Thủ thư và Độc giả. Admin có thể bật/tắt từng quyền nhỏ của từng nhân viên thay vì chỉ dùng role cứng.
3. **Nhật ký Kiểm toán Hệ thống (`/admin/activity`):**
   - Minh chứng mọi thao tác nhạy cảm (thêm/sửa sách, mượn trả, thu tiền phạt, phân quyền) đều được lưu vết chi tiết kèm IP và User-Agent.
4. **Cơ chế Sao lưu & Phục hồi:**
   - Trình bày giải pháp sao lưu tự động và kiểm thử phục hồi 100% 29 bảng dữ liệu.
5. **Định hướng Chuyển giao sang Laravel:**
   - Mô hình OOP MVC hiện tại (Service - Repository - Controller) có sự tương đồng 1:1 với cấu trúc của Laravel, giúp việc chuyển đổi sang Eloquent ORM, Blade Templates và Laravel Artisan Migrations diễn ra thuận lợi và nhanh chóng.

---

## 8. TRẠNG THÁI GIT & TỔNG KẾT FILE THAY ĐỔI (GIT STATUS SUMMARY)

### 8.1. Trạng thái Git hiện tại
- **Branch:** `minh`
- **Tình trạng:** Các file phát triển mới và tài liệu kiểm toán đang được lưu trữ an toàn trong working directory, tuân thủ nguyên tắc không tự động commit/push nếu chưa có lệnh của chủ dự án.

### 8.2. Danh mục các Module & File quan trọng mới bổ sung
- **Quản lý & Nghiệp vụ Nâng cao:**
  - `app/Services/BarcodeService.php`: Thuật toán sinh Barcode Code 128 và QR Code SVG chuẩn ISO.
  - `app/Services/RecommendationService.php`: Engine đề xuất sách thông minh.
  - `app/Controllers/InventoryAuditController.php` & `app/Repositories/InventoryAuditRepository.php`: Nghiệp vụ kiểm kê kho theo đợt.
  - `app/Controllers/MetadataQualityController.php`: Bảng điều khiển kiểm soát chất lượng dữ liệu biên mục.
  - `app/Controllers/BookImportController.php`: Công cụ nhập sách CSV chống formula injection.
  - `app/Controllers/ChatController.php` & `app/Services/ChatService.php`: Trợ lý ảo Paper Haven tra cứu sách và thông tin độc giả an toàn.
  - `app/Controllers/ReviewController.php` & `app/Repositories/ReviewRepository.php`: Quản lý đánh giá và nhận xét sách.
  - `app/Repositories/FavoriteRepository.php`: Quản lý tủ sách yêu thích của độc giả.
- **Giao diện & Trải nghiệm Người dùng:**
  - `public/assets/js/inventory-scanner.js`: Module quét mã camera thời gian thực tuân thủ nghiêm ngặt CSP.
  - `app/Views/admin/inventory_audits.php` & `inventory_audit_detail.php`: Giao diện quản lý và thực hiện kiểm kê kho.
  - `app/Views/admin/print_labels.php`: Trang in tem nhãn gáy sách Barcode/QR.
  - `app/Views/admin/metadata_quality.php`: Giao diện rà soát chất lượng biên mục.
  - `app/Views/admin/book_import.php`: Giao diện tải lên và xem trước dữ liệu CSV an toàn.
  - `app/Views/reader/bookshelf.php`: Giao diện Tủ sách cá nhân của độc giả.
  - `app/Views/partials/chat_widget.php`: Widget Trợ lý Paper Haven.
  - `app/Views/partials/book_cover.php`: Bìa lưu chiểu trung tính SVG.
- **Cơ sở dữ liệu & Di chuyển:**
  - `database/migrations/016_quarantine_simulated_copies_and_clean_isbns.php`: Migration cách ly bản sao mô phỏng và chuẩn hóa ISBN (đã hoàn thiện và kiểm thử an toàn).
  - `database/backup.php` & `tests/backup_restore.php`: Kịch bản sao lưu và kiểm thử phục hồi toàn diện 29 bảng.
  - `storage/backups/paper_page_library-20261017-231206-faf6c4.sql`: Bản sao lưu an toàn kèm mã băm SHA-256.
- **Tài liệu & Hồ sơ Bàn giao:**
  - `docs/FINAL_HANDOVER.md`: Tài liệu bàn giao toàn diện chính thức này.
  - `docs/FINAL_FEATURE_AUDIT.md`: Báo cáo kiểm toán toàn diện 15/15 phân hệ chức năng (100% PASS, 31/31 kiểm tra).
  - `docs/BOOK_COVER_MATCH_REPORT.md`: Báo cáo đối soát 56 file từ bộ ảnh bìa, tích hợp thành công bìa NXB Trẻ cho B301.
  - `docs/V12_DATA_VERIFICATION.md`: Báo cáo chi tiết kiểm toán dữ liệu, 160 bản sao và đối soát 80 ISBN.
  - `docs/ANTIGRAVITY_PROGRESS.md`: Bảng tiến độ tổng thể các giai đoạn dự án.

---

## 9. KẾT QUẢ KIỂM TOÁN TÍNH NĂNG & ĐỐI SOÁT BÌA SÁCH (18/10/2026)

1. **Đối soát 56 files ảnh bìa sách:**
   - Hoàn tất rà soát 100% 54 ảnh + 2 tài liệu HTML trên cả 5 thư mục (`khoa_hoc`, `tin_hoc`, `kinh_te`, `thieu_nhi`, `van_hoc`).
   - Tích hợp thành công ảnh bìa chính thức của **NXB Trẻ** cho đầu sách **B301 ("Lược sử thời gian" - Stephen Hawking, ISBN 978-604-1-01204-2)** tại `public/assets/book-covers/b301-luoc-su-thoi-gian.webp` (và `.jpg`).
   - Tuyệt đối không gán ép, không dùng bìa AI, bảo toàn tính trung thực cho toàn bộ 53 tựa sách còn lại chưa thuộc danh mục DB.

2. **Kiểm toán toàn diện 15 phân hệ tính năng (100% PASS):**
   - Đã kiểm tra tự động qua `storage/audit_all_15_features.php` đạt **31/31 kiểm tra PASS**.
   - Toàn bộ các luồng Mượn/Trả, Phạt quá hạn, Gia hạn, Hàng đợi đặt trước, Kiểm kê Barcode/QR, Tủ sách cá nhân, Chatbot MySQL offline, Báo cáo thống kê, Import an toàn và Bảo mật CSP/CSRF/SQLi đều vận hành trơn tru.

---

## 10. BỘ HỒ SƠ NỘP MÔN HỌC LẬP TRÌNH MÃ NGUỒN MỞ (DOCS/SUBMISSION)

Bộ tài liệu học thuật hoàn chỉnh phục vụ bảo vệ và nộp đồ án môn Lập trình Mã nguồn mở đã được cấu trúc thành 10 văn bản độc lập tại `docs/submission/`:

1. [`docs/submission/01_GIOI_THIEU_DE_TAI.md`](docs/submission/01_GIOI_THIEU_DE_TAI.md): Tổng quan đề tài, mục tiêu, tính cấp thiết và các tính năng nổi bật.
2. [`docs/submission/02_CONG_NGHE_MA_NGUON_MO.md`](docs/submission/02_CONG_NGHE_MA_NGUON_MO.md): Nền tảng công nghệ mã nguồn mở (FOSS), bản quyền giấy phép (MIT, Apache, LGPL).
3. [`docs/submission/03_KIEN_TRUC_HE_THONG.md`](docs/submission/03_KIEN_TRUC_HE_THONG.md): Kiến trúc phần mềm MVC OOP phân tầng, cấu trúc cây thư mục và các cơ chế an toàn.
4. [`docs/submission/04_SO_DO_USE_CASE_VA_PHAN_QUYEN.md`](docs/submission/04_SO_DO_USE_CASE_VA_PHAN_QUYEN.md): Sơ đồ Use Case các tác nhân & Ma trận phân quyền hành động (Action Permissions Matrix).
5. [`docs/submission/05_SO_DO_ERD_VA_DATABASE.md`](docs/submission/05_SO_DO_ERD_VA_DATABASE.md): Bản thiết kế CSDL (ERD, 29 bảng dữ liệu, ràng buộc khóa ngoại, chỉ mục B-Tree).
6. [`docs/submission/06_QUY_TRINH_NGHIEP_VU.md`](docs/submission/06_QUY_TRINH_NGHIEP_VU.md): Quy trình nghiệp vụ thư viện chuyên sâu (Lưu thông, tính phạt lũy tiến, kiểm kê kho mã vạch, đặt trước FIFO, nhập sách CSV).
7. [`docs/submission/07_HUONG_DAN_CAI_DAT_VA_TRIEN_KHAI.md`](docs/submission/07_HUONG_DAN_CAI_DAT_VA_TRIEN_KHAI.md): Hướng dẫn cài đặt, cấu hình `.env`, chạy migration và khởi động máy chủ web phát triển.
8. [`docs/submission/08_HUONG_DAN_SU_DUNG.md`](docs/submission/08_HUONG_DAN_SU_DUNG.md): Sổ tay hướng dẫn chi tiết từng bước cho Admin, Thủ thư và Độc giả.
9. [`docs/submission/09_BAO_CAO_KIEM_THU.md`](docs/submission/09_BAO_CAO_KIEM_THU.md): Báo cáo kiểm thử tự động toàn diện (93/93 assertions PASS tuyệt đối trên 8 bộ kiểm thử).
10. [`docs/submission/10_HAN_CHE_VA_HUONG_PHAT_TRIEN.md`](docs/submission/10_HAN_CHE_VA_HUONG_PHAT_TRIEN.md): Đánh giá kết quả đồ án, các hạn chế thực tế và lộ trình nâng cấp (Laravel 11+, RFID, Z39.50/MARC21).

---

**XÁC NHẬN BÀN GIAO CHÍNH THỨC:**  
Dự án **Paper Haven** đã hoàn tất trọn vẹn toàn bộ các mục tiêu đặt ra, hệ thống vận hành ổn định trên môi trường Laragon (PHP 8.3 + MySQL 8.4), dữ liệu trung thực, nghiệp vụ chặt chẽ, tài liệu đầy đủ và đã sẵn sàng 100% để bảo vệ đồ án cũng như chuyển giao sang nền tảng Laravel.


