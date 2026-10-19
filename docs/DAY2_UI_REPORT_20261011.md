# FINAL SPRINT NGÀY 2 — GIAO DIỆN / TRẢI NGHIỆM — 11/10/2026

Chốt lúc 2026-10-11T23:34:50+07:00. Thực thi trực tiếp trên nguồn chính thức D:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage. Branch minh; HEAD e78f09a77371ac1b62be238e8e835cb6491f2e92; working tree có thay đổi từ các sprint trước và phiên này. Không commit/push/merge, không dùng source/index.html cũ.

## Môi trường và phạm vi

Main http://127.0.0.1:8010 → paper_page_library, PID22412. QA http://127.0.0.1:8015 → paper_page_test_20261011_214305_37d8, PID24432, cùng source/public/index.php; cấu hình được đọc từ process đang chạy trong runtime-verification.json. Mọi thao tác ghi/chỉnh chính sách/mượn/trả/thu phí dùng QA. Server phụ8016 trỏ tới một tên database không tồn tại, APP_DEBUG=false, chỉ để kiểm tra 500 thật; không dừng hay phá database chính.

Google Chrome 155.0.8059.39, Playwright headless, locale vi-VN. Zoom100% xác minh devicePixelRatio=1 và visualViewport.scale=1 trên từng render. Đây là Chrome thật, không phải ảnh thu nhỏ của VS Code. Chiều cao900px; kích thước1440/1366/1024/768/390. Ảnh full-page lưu đúng kích thước; các ảnh workflow/modal giữ viewport để xem chữ, icon và khoảng cách. Ảnh lazy-load được cuộn tới trước khi kiểm tra naturalWidth; không tắt lazy-loading trong ứng dụng.

Bằng chứng nằm trong storage/backups/day2-20261011/, được Git ignore. Không đưa thông tin đăng nhập QA hoặc SQL backup vào báo cáo công khai. JSON/log có ngày, browser/source/test DB; SHA-256 trong evidence-manifest.json.

## Kết quả cuối, không dùng lại số PASS cũ

| Bộ kiểm tra thực thi trong phiên này | Kết quả | Bằng chứng |
|---|---|---|
| UI bốn nhóm người dùng,43 cặp vai trò/trang ×5 widths | 215 render;252 checks PASS;0 FAIL | final/result.json, ui-final.log và ảnh từng trang |
| Luồng native UI/CRUD/độc giả/mượn trả/đặt trước/cài đặt/lỗi | 86 PASS | workflows/result.json, workflows-final.log, ảnh workflows/ |
| Tồn0/giữ bản retired/fee cap/redirect giả mạo | 8 PASS | states-after.json/log |
| Tìm nhật ký tiếng Việt/mã cũ/phân trang/input SQL/mobile | 7 PASS | activity-search-after.json/log |
| Native phone validation2 form ×5 widths ×4 giá trị +console | 41 PASS | phone-pattern-after.json/log |
| Browser completion chạy lại bằng Chrome | 273 PASS;110 render | browser-completion.log |
| Browser sprint chạy lại bằng Chrome | 108 PASS | browser-sprint.log |
| HTTP replay/retire0/thẻ/validation | 31 PASS | http.log, final/http-regression.json |
| Password/session regression | 22 PASS | password.log, final/password-after.json |
| Dashboard tên dài, Admin/Thủ thư ×5 widths | 10 PASS | overflow.log, final/dashboard-overflow.json |
| Cover PNG/JPEG/WebP | 6 PASS | cover-formats.log |
| PDF tiếng Việt, glyph/phông/amount/IDOR | 20 PASS | pdf.log |
| Trang500:4 roles x5 widths / KPI MySQL:2 roles x5 widths x3 filters | 20 /60 PASS | error-roles-after.json, statistics-balance-after.json |
| PHP lint / JS syntax / diff | 122 PHP;0 lỗi | lint.log, syntax-final.json, diff-check.log |
| MySQL phase3/phase4/completion | 18/21/26 PASS | phase3.log / phase4.log / mysql-completion.log |
| MySQL permissions/report/return-all/reminders | 118/7/5 PASS | permissions.log / return-all.log / reminders.log |
| Core smoke/security | 10/15 PASS | smoke.log / security.log |
| Main preservation | 25/25 bảng khớp rows và timestamp;30 sách/105 bản sao | database-before/after.json, main-preservation.json |
| Khôi phục backup trước ngày2 | 25/25 bảng exact rows trên schema mới | restore.log, restore-paper_page_library-20261011-214305-5a4ff4.sql.json |

Các số273/108 trong tài liệu ngày1/10Oct là lịch sử. Các dòng ở bảng này là lần chạy Chrome mới; log và thời điểm trong regression-results.json. Không cộng kết quả trung gian thành PASS cuối. Lượt matrix trung gian có8 FAIL do test đọc ảnh chưa được lazy-load; bằng chứng ui-lazy-diagnostic.json/log được giữ lại, sửa cách kiểm tra và chạy lại toàn bộ, không sửa dữ liệu kết quả. Ba lỗi tương tác ban đầu nằm trong before/result.json và ui-before.log.

## Các lỗi có bằng chứng đã sửa

| Mã | Lỗi trước sửa / thay đổi | File ứng dụng | Bằng chứng trước → sau |
|---|---|---|---|
| D2-01 | Click khoảng đệm trong dialog đóng form, mất bản nháp; chỉ đóng khi tọa độ ở ngoài dialog | public/assets/js/app.js | probes-before.json, before/padding-desktop.png → workflow giữ draft/backdrop tại5 widths |
| D2-02 | Mở lại checkout giữ tìm kiếm/ẩn các bản; select native bị ẩn mất required; reset filter và validation chọn1..max theo policy | public/assets/js/app.js; app/Views/admin/loans.php | 2 FAIL trong before/result.json → 252/252 và picker-max-validation.png |
| D2-03 | Offline gửi form rời trang sang chrome-error; giữ form/nút khả dụng và thông báo tiếng Việt, gửi lại khi online | public/assets/js/app.js | offline-before.json/ảnh → workflows/offline-after.png và reconnect assertions |
| D2-04 | CSS label của form ghi đè choice-row: checkbox trên tên, min-height43; chỉ thêm layout flex/checkbox20px, giữ hidden khi lọc | public/assets/css/app.css | after/dialog-loanDialog.png (trước layout) → workflows/picker-layout-after.png + geometry assertion |
| D2-05 | Menu Thể loại tới /catalog#genres không có target; chuyển về /#genres hiện hữu | app/Views/layouts/public.php | probes-before.json targetExists0 → genre-navigation.png/targetExists1 |
| D2-06 | Log/report/permissions/staff/settings hiện mã tiếng Anh; thêm nhãn tiếng Việt ở presentation và tìm được bằng nhãn Việt, không đổi code trong MySQL/CSV | app/Helpers/functions.php; app/Views/admin/activity.php, statistics.php, permissions.php, users.php, settings.php; app/Controllers/StaffController.php | before/labels-*.png/probes-before.json và activity-search-before.json (mã30,nhãn Việt0) → vietnamese-audit.png, activity-search-after.json7 PASS |
| D2-07 | Form phí hỏng/mất max100000000 dù backend chỉ99999999; đồng bộ HTML cap | app/Views/admin/settings.php | settings-limit-before.json (HTML nhận nhưng backend từ chối) → settings/state regression |
| D2-08 | Tồn0 vì bản retired vẫn ghi Tất cả bản đang mượn; dùng trạng thái Chưa có sẵn/Chưa có bản có thể mượn đúng với tồn phục vụ | app/Views/public/book.php, catalog.php; app/Views/partials/book_card.php; app/Views/admin/books.php, dashboard.php | states-before.json/zero-availability.png → states-after.json/zero-availability-after.png |
| D2-09 | Tìm nhật ký không có kết quả chỉ hiện bảng trống; bổ sung hướng dẫn đổi từ khóa/xóa lọc | app/Views/admin/activity.php | states-before.json rows0/message0 → audit-empty-after.png |
| D2-10 | Trả/gia hạn POST đưa ra danh sách chưa lọc, phải tìm lại phiếu; giữ q/status/page trong form, whitelist query và cố định route đích | app/Controllers/LoanController.php; app/Views/admin/loans.php | failure.json/lượt workflow cũ bị mất filter → 86 PASS, Partial return retains search context; 4 assertions chống redirect giả mạo |
| D2-11 | Regex phone không hợp lệ trên Chrome155, invalid phone vẫn được client nhận; sửa escaping theo parser hiện tại, không đổi backend | app/Views/auth/register.php; app/Views/reader/profile.php | phone-pattern-before.json (invalidAccepted=true và console syntax error) → phone-pattern-after.json41 PASS/ảnh validation |
| D2-12 | Trang500 Thủ thư lộ fatal PHP/SQL khi DB unavailable; render layout public không truy vấn DB | public/index.php | error-roles-before.json -> error-roles-after.json:20 PASS |
| D2-13 | KPI dư nợ hiện0 thay vì207.000đ do biến tổng bị ghi đè; tách rowOutstanding | app/Views/admin/statistics.php | statistics-balance-before.json -> statistics-balance-after.json:60 PASS |


Không thay kiến trúc, service transaction/idempotency, schema/migration hay dependency trong ngày2. CSS gốc được giữ nguyên; chỉ nối3 quy tắc layout, không thêm/đổi color/font. style-preservation.json đối chiếu bản nguồn trước sửa; composer.json/lock khớp byte.

## Ma trận từng trang PASS / FAIL / BLOCKED

PASS dưới đây nghĩa là HTTP đúng vai trò, không tràn ngang toàn trang, ảnh đã tải hoặc fallback hợp lệ, zoom100%, không lỗi JS; không thay cho kiểm thử mọi mutation trên mọi bản ghi. Bảng rộng có vùng cuộn nội bộ. Trang404 là PASS với HTTP404 mong đợi.

| Vai trò | Trang / bộ lọc | 1440 | 1366 | 1024 | 768 | 390 |
|---|---|---|---|---|---|---|
| Khách | / | PASS | PASS | PASS | PASS | PASS |
| Khách | /catalog | PASS | PASS | PASS | PASS | PASS |
| Khách | /book/1 | PASS | PASS | PASS | PASS | PASS |
| Khách | /login | PASS | PASS | PASS | PASS | PASS |
| Khách | /register | PASS | PASS | PASS | PASS | PASS |
| Khách | /catalog?q=NO_RESULT_DAY2_987654 | PASS | PASS | PASS | PASS | PASS |
| Khách | /missing-day2 | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/books | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/books/1/copies | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/readers | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/readers/104 | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/authors | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/publishers | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/categories | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/loans | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/loans?status=overdue | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/fines | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/statistics | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/users | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/permissions | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/activity | PASS | PASS | PASS | PASS | PASS |
| Admin | /admin/settings | PASS | PASS | PASS | PASS | PASS |
| Admin | /notifications | PASS | PASS | PASS | PASS | PASS |
| Admin | /profile | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/books | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/books/1/copies | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/readers | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/loans | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/loans?status=overdue | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/fines | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/authors | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/publishers | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/categories | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /admin/statistics | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /notifications | PASS | PASS | PASS | PASS | PASS |
| Thủ thư | /profile | PASS | PASS | PASS | PASS | PASS |
| Độc giả | /account | PASS | PASS | PASS | PASS | PASS |
| Độc giả | /account/profile | PASS | PASS | PASS | PASS | PASS |
| Độc giả | /notifications | PASS | PASS | PASS | PASS | PASS |
| Độc giả | /catalog | PASS | PASS | PASS | PASS | PASS |
| Độc giả | /book/1 | PASS | PASS | PASS | PASS | PASS |

Tổng:43 cặp trang/vai trò,215 renders PASS;0 trang FAIL,0 trang BLOCKED trong phạm vi local. Các route riêng Admin không được gán PASS cho Thủ thư/Độc giả:403 và kiểm thử permissions có bằng chứng riêng.

## Luồng và trải nghiệm đã kiểm tra

- Công khai: hero/sách mới/phổ biến/danh mục/footer, điều hướng anchor, tìm kiếm/thể loại/tình trạng/sắp xếp/pagination; sách có cover thật và lỗi ảnh fallback; đăng ký/login/hiện-ẩn password/validation mật khẩu.
- Admin:9 KPI và biểu đồ có dữ liệu, thêm sách/cover/copies/kệ, quản lý độc giả/nhân viên/quyền; tác giả/NXB CRUD, thể loại CRUD/cancel confirm; lưu policy tạo revision mới; log lọc/empty, báo cáo phân trang và native CSV download; PDF/upload/security theo regression273/108.
- Thủ thư: mobile chọn2 bản, checkout, return partial/full, renew; ready reservation/overdue/payment trong regression; trạng thái damaged/lost/retired/available-worn/available-good cập nhật bằng form thật; menu/phân quyền/profile/thông báo.
- Độc giả: thẻ mới empty, profile/phone/address reload, sai current password backend error rồi đổi đúng, sách đang mượn và ngày trả/history, renew đủ điều kiện, reserve cuốn khác không còn bản/cancel, mark-all notifications rồi reload; fines/payment/receipt theo sprint regression.
- Dialog: mở/close/Escape/padding/backdrop/giữ draft/bounds; invalid selection không gửi; cancel destructive confirmation không khóa nút. Native form có loading và ngăn double-click. Network offline giữ giá trị và có retry; backend validation và trang403/419/404/500 thật.

Các form native được đo trong workflows/result.json:34 lần submit, median 191ms; p95 529ms, trong môi trường local/test. Không dùng các số này làm cam kết production. Từ trang mượn, tạo phiếu2 bản có4 kích hoạt nút/checkbox (mở dialog,2 tích,submit), ngoài thao tác chọn độc giả/nhập tìm kiếm; selectOption/fill của automation không tính là click người thật. Trả/gia hạn là submit + confirm; sau sửa không cần tìm lại phiếu. Không có benchmark UX người thật/production hay đủ mọi tổ hợp dữ liệu.

## Điểm khôi phục và bảo toàn dữ liệu

Trước sửa: source-before-audit.zip169 files, CRC và SHA từng file được kiểm chứng; SHA be8d2332982d3173e2d2cf3fe9c33221e96bbed57ce4ebfb938d1e256a7c2bc4; source-manifest.json và working-tree-before.patch giữ các sửa từ trước.

SQL backup storage/backups/paper_page_library-20261011-214305-5a4ff4.sql đọc main nhất quán, restore chỉ vào schema paper_page_test_restore_* mới,25/25 exact rows/timestamp. Không reset/xóa/seed/maintenance main. Main25/25 tables giữ hash, kể cả schema_migrations và mutation_requests, khác ngày1 chỉ có thay metadata migration. Main vẫn có1 ready hold hết hạn đã ghi từ ngày1; không tự xử lý dữ liệu chính để báo đẹp.

Bản sau sửa: source-after-day2.zip và evidence-manifest.json; QA screenshot/log được đóng gói qa-evidence.zip, không chứa credential JSON. Chỉ khôi phục source/SQL có đối chiếu khi thực sự cần; không tự restore đè main.

## Phạm vi còn chưa kiểm chứng

Không có P0 UI local đã xác minh còn mở. Các mục sau không được gọi PASS và không chặn kết quả local:

- CHƯA KIỂM CHỨNG: tương tác bằng thiết bị vật lý/touch thật, screen reader, khác Chrome, printer/PDF viewer của hệ điều hành, production/deployment/load, browser zoom khác100%.
- CHƯA KIỂM CHỨNG: packet loss khi POST đã gửi/đã commit. Offline guard chỉ xử lý khi trình duyệt báo offline trước gửi; idempotency HTTP/core được regression riêng, không chuyển các form đang hoạt động thành AJAX.
- CHƯA KIỂM CHỨNG: mọi tổ hợp hàng nghìn bản ghi/chính sách/quyền; đã kiểm tra nhóm control và luồng ở trên, không tuyên bố click từng nút của từng row.
- Phân trang feed/lịch sử lớn và scheduler thực tế vẫn là P1 ngày1. Các kiểm tra MySQL ở đây không thay thế toàn bộ test rollback/concurrency ngày1; xem báo cáo ngày1 cho các lần thực thi đó.

## Chạy lại

Tạo database test bằng tests/prepare_test_database.php, dùng DB_NAME từ storage/test_database_name.txt; tạo fixtures bằng prepare_http_fixtures.php, prepare_report_fixtures.php, prepare_cover_fixtures.php; start PHP server8015 trên source này và override DB_NAME test. Trước mỗi browser_completion cần mysql_reminders.php cho fixture ready hold. QA_BROWSER trỏ Chrome; QA_BASE_URL=http://127.0.0.1:8015. QA_OUTPUT chọn thư mục bằng chứng riêng, không ghi đè ngày1.

~~~powershell
node tests/browser_day2_ui.cjs
node tests/browser_day2_workflows.cjs
node tests/browser_day2_states.cjs
node tests/browser_day2_activity.cjs
node tests/browser_day2_phone.cjs
node tests/browser_day2_errors.cjs
node tests/browser_day2_balances.cjs
node tests/browser_completion.cjs
node tests/browser_sprint.cjs
php tests/lint.php
~~~

Workflow500 cần server phụ8016/DB_NAME test không tồn tại/APP_DEBUG=false. States cần prepare_day1_http.php và browser_day1_backend.cjs để tạo/test retired fixture; cả hai chỉ trên test DB. Credentials ngẫu nhiên giữ private trong storage, không in vào terminal hoặc commit. Các test mới có guard cổng8015 để tránh chạy mutation vào main8010.
