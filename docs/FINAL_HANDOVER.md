# Bàn giao cuối — 08/10/2026

Đã hoàn thiện P0/P1 trên ứng dụng PHP/MySQL hiện hữu, giữ giao diện PAPER & PAGE. Không reset hoặc xóa dữ liệu `paper_page_library`. Database chính giữ 30 đầu sách/105 bản sao, migrations 001–006, 21 bảng và 31 khóa ngoại. Chi tiết trong [FINAL_GAP_ANALYSIS.md](./FINAL_GAP_ANALYSIS.md) và [TEST_REPORT.md](./TEST_REPORT.md).

## Kết quả

- Auth/role/CSRF, strict session/timeout và throttle email/IP không phụ thuộc cookie; hồ sơ/đổi mật khẩu cả ba vai trò.
- Sách, từng bản sao/barcode/kệ/tình trạng; CRUD tác giả/NXB/thể loại; tìm kiếm không dấu qua collation, lọc/sort/phân trang catalog và độc giả.
- Thẻ độc giả có trạng thái/khôi phục và trang lịch sử/phí cho staff; ngừng thẻ có kiểm tra sách/đặt trước/dư nợ.
- Mượn nhiều bản/trả phần/gia hạn/FIFO/expiry/ledger và thanh toán; transaction/rollback/concurrency đã kiểm chứng.
- KPI phân biệt phiếu/bản sao; CSV mượn, kho, phí; reminders và maintenance CLI; responsive toàn bộ màn trong bộ browser test.
- Sửa form nhận bản giữ chỗ, modal ID rỗng, setup lần đầu thiếu inventory, archival dùng legacy, lỗi PDO lộ SQL, fallback ảnh innerHTML, tràn tablet/settings và cỡ chữ mobile.

Kiểm thử: PHP lint 92 file; smoke 10; security 15; MySQL phase3 18, phase4 21, completion 26, reminders 5; concurrency PASS; cài mới/idempotency PASS; JS syntax PASS; browser 273 checks/110 renders PASS. Git diff **BLOCKED** vì không có repository.

## Chạy và tài khoản demo

Mở terminal trong source thật (thư mục chứa README.md, app/, public/, database/), dùng Laragon PHP/MySQL rồi:

```powershell
php -S 127.0.0.1:8000 -t public public/index.php
```

Database hiện tại đã có migration và demo; không cần seed lại. Mở http://127.0.0.1:8000/login. Admin dùng SEED_ADMIN_EMAIL; thủ thư/độc giả dùng SEED_DEMO_LIBRARIAN_EMAIL và SEED_DEMO_READER_EMAIL trong .env. Mật khẩu xem riêng tại các trường PASSWORD tương ứng; không chép vào báo cáo/screenshot/nộp công khai. Seed không phải công cụ reset mật khẩu tài khoản đã có. Cài máy mới theo INSTALLATION_GUIDE.md.

## Tệp đã sửa/thêm

- Core/helpers: Session.php, Pagination.php (mới), Router.php, functions.php; controller base.
- Controllers: Auth, Catalog, Management, Reader, Fine, Dashboard, Notification; InventoryController/ProfileController mới.
- Repositories: Book, Reader, Loan, Dashboard; CopyRepository/MetadataRepository/ReportRepository mới.
- Services: AuthService, LoanReportCsvExporter; LoginThrottle/ReminderService mới.
- Views: layouts/app/public, admin books/readers/categories/loans/fines/settings/dashboard/statistics; copies/metadata/profile/reader_history và partial pagination mới; public/catalog.
- Assets: public/assets/css/app.css, public/assets/js/app.js. Không thay frontend legacy làm entry point.
- Database: setup.php sửa seed inventory lần đầu; maintenance.php mới. Không đổi schema hoặc thêm migration.
- Tests: guard, clone DB test, fixtures HTTP, completion/reminders/security/setup_fresh/audit và browser_completion.cjs mới; phase3/phase4/concurrency thêm guard.
- Config/docs: .env.example, .gitignore, README và tài liệu liên quan. .env riêng không chỉnh/sao chép vào docs.

## Khi nộp và bảo vệ

Chuẩn bị source, bản sao DB đã kiểm tra, tài liệu ERD/dictionary/use cases/architecture, báo cáo test và kịch bản demo mượn hai bản/trả phần/gia hạn/quá hạn/thu phí. Không đóng gói .env, cache/session/log/QA credentials/node_modules hoặc DB test. Cung cấp .env.example và credential demo qua kênh riêng. Backup dùng hướng dẫn INSTALLATION_GUIDE.md.

Chưa triển khai: PDF, upload ảnh, REST API, permission matrix granular hoặc UI quản trị tài khoản nhân viên. Không cần các phần này cho nghiệp vụ P0/P1 đã thống nhất. Trước production cần security review độc lập, kiểm thử tải, TLS và xác minh backup/restore; chưa chứng nhận các hạng mục đó.

Gói source sạch: storage/PaperPage_Final_Source.zip, không chứa .env, log/cache/QA credentials/node_modules hoặc dữ liệu database chính. Gói chứa schema/migrations để cài mới; backup dữ liệu dùng hướng dẫn riêng.
