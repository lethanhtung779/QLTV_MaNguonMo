# Kiểm tra dự án trước khi nâng cấp PHP/MySQL

Ngày kiểm tra: 08-10-2026

## Cấu trúc trước khi nâng cấp

```text
QLTV_MaNguonMo-main/
├── index.html
├── dashboard.html
├── README.md
├── css/style.css
└── js/main.js
```

Workspace còn có `.vscode/settings.json` đặt cổng Live Server 5501 và một file ZIP ở thư mục cha. Thư mục dự án cùng thư mục cha đều không phải Git repository; không có metadata để kiểm tra nhánh, commit hoặc thay đổi chưa commit. ZIP nằm ngoài phạm vi sửa đổi.

## Công nghệ và cách chạy hiện trạng

- Frontend thuần HTML, CSS và JavaScript, không có Composer, framework hoặc bước build.
- Chạy bằng cách mở `index.html` hoặc Live Server.
- Không tìm thấy PHP, SQL, cấu hình kết nối, migration, API, Apache/PHP server hoặc tài liệu database trong dự án.
- Dữ liệu sách, thể loại, độc giả và phiếu mượn là dữ liệu mẫu trong JavaScript; các phiên bản giao diện trước lưu bản chỉnh sửa vào `localStorage`.
- Đăng nhập mẫu được xác minh ở trình duyệt với một cặp thông tin cố định; không có tài khoản backend, quyền thực tế hay session phía máy chủ.

## Chức năng đã có

- Trang đăng nhập và bố cục dashboard.
- Danh mục sách, độc giả, thể loại và phiếu mượn/trả với thao tác frontend.
- Tìm kiếm, lọc, thống kê và lưu trạng thái ở trình duyệt.
- Bản giao diện trước có responsive, bìa dự phòng và lựa chọn sáng/tối.

## Chức năng chưa có / chỉ là mô phỏng

- Cơ sở dữ liệu dùng chung, PDO, giao dịch và ràng buộc khóa ngoại.
- Xác thực an toàn, phân quyền, session phía máy chủ, CSRF protection và quản lý tài khoản.
- Tính sẵn có sách được bảo đảm đồng thời bởi transaction; lịch sử mượn/trả, đặt trước và phí phạt lưu bền.
- Trang công khai, tài khoản độc giả và API/trang phía máy chủ.
- Sao lưu, migration, nhật ký thao tác và hướng dẫn triển khai PHP/MySQL.

## Lỗi và rủi ro quan sát được

- `localStorage` không đồng bộ giữa thiết bị/người dùng và không phải nguồn dữ liệu có thẩm quyền.
- Xác thực phía client có thể bị bỏ qua; không thể dùng cho vận hành thư viện thực tế.
- Các tệp HTML được phục vụ tĩnh nên không thể bảo vệ trang hoặc kiểm tra quyền ở server.
- CSS/JavaScript và dữ liệu nghiệp vụ bị ghép vào một stylesheet/script lớn, khó kiểm soát hợp đồng dữ liệu lâu dài.
- Tên/tác giả/ID sách từng được dùng thay ID khóa ngoại; sửa tên có thể làm lệch phiếu mượn.
- Không có Git repository nên không thể cung cấp kết quả Git diff/status có thể xác minh.

## Thành phần cần giữ và tái sử dụng

- Nội dung tiếng Việt, bộ chức năng nghiệp vụ, dữ liệu thư viện mẫu, quy tắc giữ lịch sử khi xóa và ý tưởng bìa sách.
- HTML/CSS/JS hiện tại làm tài liệu chuyển đổi; stylesheet được thay bằng design system PAPER & PAGE, còn trình duyệt không duy trì dữ liệu nghiệp vụ.
- Không giữ cơ chế đăng nhập demo, `localStorage` cho dữ liệu thư viện hoặc xử lý nghiệp vụ chỉ phía client.

## Kế hoạch nâng cấp

1. Chuyển sang điểm vào PHP riêng trong `public/`, nạp cấu hình ngoài web root và kết nối PDO MySQL.
2. Tạo schema UTF8MB4/InnoDB, migration/setup CLI, dữ liệu khởi tạo an toàn và khóa ngoại/index.
3. Tách router, middleware, controller, service, repository và view; xác thực bằng PHP session, CSRF, băm mật khẩu và phân quyền.
4. Thêm website công khai, catalog/tìm kiếm, dashboard thủ thư/quản trị, CRUD và thao tác mượn-trả giao dịch.
5. Thêm cổng độc giả, lịch sử, hạn trả, tiền phạt được tính phía server và đặt trước sách.
6. Áp dụng design system PAPER & PAGE, kiểm tra viewport 1440/1366/1024/768/390 px.
7. Chạy lint/test và kiểm tra MySQL/browser khi runtime tương ứng hiện diện; báo rõ giới hạn môi trường.

## Môi trường tại thời điểm kiểm tra

Máy có Node.js v24.21.0 và Git. Các lệnh `php`, `composer`, `mysql`, `mariadb`, `mysqld`, `httpd`, `apache2` không có trên PATH; các thư mục cài đặt phổ biến của XAMPP/Laragon/WAMP cũng không tồn tại. Vì vậy không thể khởi động hoặc kiểm thử tích hợp PHP/MySQL tại thời điểm này. Project sẽ được chuẩn bị để chạy với PHP 8.2+ và MySQL/MariaDB theo cấu hình `.env`.

## Kết quả triển khai trong workspace

> Phần trên ghi lại hiện trạng **trước khi nâng cấp**. Trạng thái dưới đây mô tả code sau lần chuyển đổi PHP/MySQL.

- Đã thêm front controller `public/index.php`, router, session, CSRF, PDO, config `.env`, header bảo mật và trang lỗi; mã ứng dụng/database nằm ngoài document root được khuyến nghị (`public/`).
- Đã thêm schema InnoDB/UTF8MB4, seed danh mục/sách, CLI setup database và tạo tài khoản quản trị.
- Đã triển khai repository/service/controller cho sách, độc giả, thể loại, xác thực, mượn/trả, đặt trước, thống kê, dashboard và nhật ký hoạt động.
- Đã tạo trang public/catalog/chi tiết sách, auth, quản trị, cổng độc giả và cài đặt; biểu mẫu có CSRF/validation server-side, tìm kiếm/lọc và empty states.
- Đã thay thiết kế ứng dụng PHP bằng PAPER & PAGE; frontend bản cũ vẫn được giữ làm tài liệu nhưng bị chặn qua root `.htaccess` khi dùng Apache. Chạy ứng dụng PHP phải dùng document root `public/`.
- README đã được thay bằng hướng dẫn chạy PHP/MySQL, cấu hình `.env`, setup, seed, serve và kiểm tra.
- `database/setup.php` ghi nhận migration nền tảng `001_initial_schema` trong `schema_migrations`; setup và seed danh mục/sách dùng thao tác idempotent.

## Kiểm chứng sau nâng cấp

- Đã rà soát các view/route, luồng POST, quyền truy cập, truy vấn và biểu mẫu trong source; đã chỉnh đường dẫn view, xử lý fallback ảnh, rollback khi lưu sách lỗi và truy vấn grouped reader tương thích strict SQL mode.
- Đã xác minh bằng Laragon PHP 8.3.33, PDO MySQL, mbstring, Apache 2.4.68 và MySQL 8.4.3; migration, database riêng và seed chạy thành công.
- PHP lint kiểm tra 52 file không lỗi; 10 smoke tests và JavaScript syntax check đạt.
- Đã kiểm thử qua HTTP với MySQL thật: đăng nhập/phân quyền, đăng ký độc giả, CRUD/lưu trữ sách và độc giả, lập/trả phiếu, cập nhật tồn kho, đặt trước, giữ bản sách sẵn sàng, nhận sách và dashboard.
- Đã kiểm tra trang chủ ở 1440, 1366, 1024, 768 và 390 px; không phát hiện horizontal overflow.
- Secrets và môi trường vẫn chỉ cấu hình trong `.env`; chính sách nghiệp vụ thư viện được quản trị an toàn qua trang Cài đặt và lưu snapshot version trong MySQL.
- Lint/smoke tests và kiểm thử MySQL phase 3 được tự động hóa; script tích hợp tạo dữ liệu QA tạm, xác minh renewal/reservation/notifications và dọn lại.

## Cập nhật hiện trạng sau giai đoạn 3

- Đã áp dụng migration `003_reader_policies_reservations_notifications` và `004_versioned_library_policies` trên database riêng `paper_page_library`; giữ nguyên 63 bản sao và catalog cũ.
- Gia hạn từng bản có giới hạn và số ngày cấu hình, chặn quá hạn/đang có reservation; ledger ghi ngày cũ/mới, số lần, actor, policy version và số ngày đã áp dụng.
- Reservation FIFO gắn một bản sao thực; trả/thêm tồn cấp bản theo hàng chờ, checkout đúng người mới fulfill, hủy/hết hạn trả lại copy và chuyển cho người sau.
- Notifications dùng database, có khóa chống tạo trùng, unread/read và thao tác đánh dấu đã đọc; layout và trang riêng đã được nối.
- Chính sách `max_books_per_reader`, hạn mượn, số lần/ngày gia hạn, mức phạt tham chiếu và thời hạn giữ sách có thể chỉnh trong Cài đặt; mỗi lần lưu tạo snapshot lịch sử.
- Kết quả lần xác minh cuối: 60 file PHP lint sạch, 10 smoke tests, 18 assertion MySQL phase 3, JavaScript syntax check và setup idempotent. Xem [TEST_REPORT.md](./TEST_REPORT.md).
- Giai đoạn tiếp theo còn lại: chuẩn hóa metadata/RBAC chi tiết, báo cáo/export và kiểm thử concurrency; xem [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md).

## Cập nhật sau giai đoạn 4 — Ledger phí và thanh toán

- Migration `005_fines_and_payments` bổ sung các bảng `fine_charges`, `fine_payments`, `fine_payment_allocations`, `fine_waivers`; các bản ghi mượn/catalog có sẵn được giữ nguyên.
- Phí quá hạn lũy kế theo từng loan item được đồng bộ idempotent; phí sách hỏng/mất được cấu hình trong settings và ghi nhận tại lúc nhận trả.
- Admin/thủ thư ghi thu tiền trong transaction, phân bổ theo khoản phí cũ nhất, không cho thu vượt dư nợ; admin có thể miễn/giảm có lý do, actor được lưu audit log.
- Độc giả xem dư nợ, chi tiết phát sinh/đã thu/miễn giảm và lịch sử thanh toán riêng; dashboard và route quản lý được nối với MySQL.
- Kiểm thử mới `tests/mysql_phase4.php` tạo/dọn dữ liệu QA thật, xác minh return charge, dedupe, phân bổ thanh toán, chặn overpayment, waiver và render trang hai vai trò.

## Cập nhật sau giai đoạn 5–6 — Metadata, báo cáo và bàn giao

- Migration `006_normalized_catalog_metadata` tạo `authors`, `publishers`, `book_authors`, thêm `books.publisher_id` và backfill quan hệ từ văn bản legacy; không xóa cột/giá trị cũ.
- CRUD sách đồng bộ relations trong transaction; hỗ trợ nhiều tác giả bằng dấu `;`. Quyền hiện được áp dụng theo role hiện hữu: admin/thủ thư vận hành, chỉ admin đổi cấu hình/miễn giảm, độc giả chỉ xem tài khoản của mình.
- Dashboard hiển thị dư nợ ledger bên cạnh KPI kho/mượn; thống kê có lọc ngày/trạng thái, xem trước theo bản mượn và CSV UTF-8 BOM với chống formula injection.
- Test MySQL phase 4 kiểm tra phí, thanh toán, metadata, bộ lọc báo cáo, định dạng CSV, an toàn spreadsheet và giao diện; xem [TEST_REPORT.md](./TEST_REPORT.md).
- Còn lại ngoài phạm vi bàn giao: RBAC granular qua bảng permission riêng, PDF export, kiểm thử cạnh tranh hai session và kiểm tra responsive bằng trình duyệt trên từng trang quản trị mới.

## Bổ sung bàn giao sau đặc tả mở rộng

- Thêm `database/seed_demo.php` theo chế độ opt-in local. Trên database riêng đã tạo 21 đầu sách/42 bản sao demo, 3 hồ sơ độc giả, tài khoản thủ thư và độc giả, 5 phiếu minh họa (đang mượn, quá hạn, trả một phần, đã trả), reservation ready và ledger phí. Mã sách/thẻ/ghi chú được gắn `DEMO`; chạy lại không nhân bản fixture. Mật khẩu tự sinh được lưu trong `.env` bị ignore, không xuất ra terminal.
- Bổ sung tài liệu yêu cầu, kiến trúc, use-case, quy tắc nghiệp vụ, cài đặt, sử dụng, routes, checklist bảo mật, triển khai, sơ đồ và bàn giao. Tài liệu nêu rõ API REST, RBAC granular, PDF, pagination và responsive admin toàn diện chưa được xác minh/triển khai.
- Sau khi có fixture demo, kiểm thử hồi quy MySQL phase 3 đạt 18 assertion và phase 4 đạt 21 assertion; setup chạy lại không làm mất fixture. Lượt lint mới nhất: 69 file PHP, 0 lỗi; smoke 10 assertion.

## Kiểm toán cuối

Các phần trên là lịch sử chuyển đổi. Hiện trạng cuối 08/10/2026: FINAL_GAP_ANALYSIS.md, TEST_REPORT.md và FINAL_HANDOVER.md thay thế các phát biểu thiếu/concurrency/responsive trước đây. Source không có .git; database chính không reset; tất cả test ghi chuyển sang DB test riêng.
