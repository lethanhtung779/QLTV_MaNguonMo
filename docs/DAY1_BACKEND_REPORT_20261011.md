# FINAL SPRINT — NGÀY 1: DATABASE/BACKEND — 11/10/2026

## Kết luận và nguồn thực tế

Đã xử lý 7 nhóm lỗi P0 có bằng chứng tái hiện, thêm regression và hoàn tất kiểm tra database/backend trong phạm vi local. Không còn P0 đã xác minh đang mở; không có P0 BLOCKED. Các giới hạn P1 và môi trường chưa kiểm chứng được liệt kê riêng, không coi là nghiệm thu production.

Source: D:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage. Branch minh, HEAD e78f09a77371ac1b62be238e8e835cb6491f2e92, remote https://github.com/lethanhtung779/QLTV_MaNguonMo.git. Một working tree có thay đổi từ các sprint trước; không reset/revert/commit/push/merge chúng. Bản ZIP kiểm chứng được dùng làm điểm khôi phục.

Xác minh trực tiếp working directory, command line và riêng biến DB_NAME của tiến trình PHP:
- Main 8010, PID 22412: public/index.php của source trên; không có DB_NAME override, .env chọn paper_page_library.
- QA ngày 1: 8014, PID 11668, cùng source và front controller; DB_NAME=paper_page_test_20261011_201609_2e00.
- 8013 và paper_page_test_20261011_194427_cd59 thuộc phiên nghiệm thu trước; không dùng để tính kết quả ngày 1.
- PHP 8.3.33, MySQL 8.4.3, Edge/Playwright. Bằng chứng runtime-verification.json.

Không dùng source cũ/index.html. CSS PAPER & PAGE và composer.json/composer.lock giữ nguyên SHA-256 so với đầu ngày 1. JS chỉ sửa nạp dữ liệu form, không thay đổi style.

## Database, migrations và dữ liệu chính

| Hạng mục | Trước ngày 1 | Sau ngày 1 | Bằng chứng |
|---|---:|---:|---|
| Đầu sách | 30 | 30 | database-before/after.json |
| Bản sao vật lý | 105 | 105 | database-before/after.json |
| Users / readers | 4 / 4 | 4 / 4 | main-preservation.json |
| Loans / loan_items / reservations | 5 / 6 / 1 | 5 / 6 / 1 | main-preservation.json |
| Bảng | 24 | 25 | schema-main-before/after.json |
| FK | 34 | 35 | Từng FK không có orphan; đều có index hỗ trợ |
| Migrations | 8 | 10 | Chỉ bổ sung 009 và 010 |
| CHECK được enforce | — | 16 | check_constraints trong schema-main-after.json |
| Sai lệch nghiệp vụ | 0 (10 invariant) | 0 (11 invariant) | Schema audit read-only |

Hiện có 101 bản available (100 good, 1 worn), 3 on_loan và 1 reserved. Không ép dữ liệu về con số tham chiếu; dữ liệu hiện tại tình cờ vẫn là 30/105.

Tất cả 25 bảng có PK; kiểm tra mọi unique index kể cả composite/nullable, mọi FK/orphan và index tiền tố hỗ trợ FK. Đối chiếu copy/open item, ready reservation/copy/book, reservation trùng, header trả, tình trạng hỏng/mất, cached inventory, tổng phân bổ khoản thu và khoản phạt không bị trả vượt. Tất cả 11 invariant bằng 0 ở main và test.

23 bảng dữ liệu/nghiệp vụ/quyền đang có giữ nguyên số dòng và SHA-256 từng tập dữ liệu trước/sau. schema_migrations chỉ thêm hai phiên bản; mutation_requests là bảng mới rỗng trên main. Không seed/reset/delete/maintenance trên main. Hai migration chỉ thêm bảng/index và thay CHECK; không sửa bản ghi nghiệp vụ.

Có 1 yêu cầu ready trên main đã quá hạn nhận. Trạng thái copy/reservation vẫn khớp, nhưng lịch chạy maintenance tự động chưa được xác minh. GET trang mượn/tài khoản hoặc CLI maintenance sẽ xử lý hết hạn theo quy tắc hiện có. Không chạy maintenance main để làm sạch số liệu nghiệm thu.

## Lỗi đã sửa và đường dẫn

| ID / mức | Lỗi tái hiện | Bản sửa | Regression/bằng chứng |
|---|---|---|---|
| D1-P0-01 | Lặp POST khoản thu một phần tạo thêm payment khi còn dư nợ | [FineService.php](../app/Services/FineService.php) + [MutationRequest.php](../app/Services/MutationRequest.php): actor/operation/key/payload, claim/result cùng transaction | mysql_day1_regression, concurrency, atomicity; HTTP31 |
| D1-P0-02 | Lặp gia hạn kéo dài hai lần | [LoanService.php](../app/Services/LoanService.php): replay trả hạn cũ trước kiểm tra max, ledger chỉ một lần | regression-after.log; idempotency-concurrency.log; HTTP cả Admin/Độc giả |
| D1-P0-03 | Lặp checkout không lấy lại phiếu đã thành công | [LoanService.php](../app/Services/LoanService.php) trả loan_code đã commit; đổi payload cùng key bị từ chối | regression-after.log; concurrency; HTTP |
| D1-P0-04 | Thẻ hết hạn/ngừng hoạt động vẫn gia hạn được | [LoanService.php](../app/Services/LoanService.php) khóa reader và kiểm tra active/expiry | Hai case trong mysql_day1_regression |
| D1-P0-05 | Tiêu đề 240 ký tự + mã hợp lệ gây SQL truncation khi lập khoản phạt | [FineRepository.php](../app/Repositories/FineRepository.php) giới hạn description 300 ký tự Unicode, giữ nguyên số tiền | fine-limits-before/after.log |
| D1-P0-06 | Ngừng sử dụng bản cuối bị CHECK total_copies>=1 chặn | migration010, schema.sql; cho cached inventory=0; controller/form cho chỉnh sách cũ có 0 bản | copy-states-before/after.log; HTTP retire/edit/history |
| D1-P0-07 | Form chỉnh sách chưa phân loại gửi category rỗng, backend từ chối số | [ManagementController.php](../app/Controllers/ManagementController.php) chuẩn hóa category tùy chọn; [app.js](../public/assets/js/app.js) nạp null thành option0 | http-zero-debug.log trước sửa; HTTP31 sau sửa |
| D1-P1-01 | synchronizeAll có SELECT theo từng reader (quan sát 7 SELECT ở fixture) | FineRepository.overdueItems(null) + FineService đọc tất cả overdue một lượt | 1 SELECT đo bằng Com_select, cùng số tiền chính xác |
| D1-P1-02 | Thứ tự khóa reader/book/item/reservation không thống nhất, có khả năng khóa chéo | LoanService: reader trước, book tăng dần rồi item/copy; cancel/expire khóa book trước reservation và recheck sau chờ | Race checkout khác reader, ba race same-key, rollback; chưa coi là chứng minh mọi lịch xen kẽ |
| D1-P1-03 | Đọc policy values/version qua nhiều query có thể lệch dưới READ COMMITTED | SystemSettingRepository.currentPolicy() lấy một immutable row | mysql_day1_atomicity: old/new policy/due/fine/version |
| D1-P1-04 | Thống kê ngày đọc index copy_history chưa phù hợp | migration009 thêm idx_loan_items_borrowed_at | EXPLAIN main sau sửa dùng index mới; không tuyên bố benchmark tải lớn |

Đường dẫn đầy đủ của các file ứng dụng:
- app/Services/MutationRequest.php, app/Services/LoanService.php, app/Services/FineService.php.
- app/Repositories/FineRepository.php, app/Repositories/SystemSettingRepository.php.
- app/Controllers/LoanController.php, FineController.php, ReaderController.php, ManagementController.php.
- app/Helpers/functions.php; app/Views/admin/loans.php, admin/fines.php, reader/dashboard.php.
- public/assets/js/app.js.
- database/schema.sql, database/setup.php, database/migrate.php, database/migrations/009_mutation_requests.php, 010_zero_operational_inventory.php.

Native form sinh _request_key bằng random_bytes(16). Các endpoint HTTP checkout/renew/payment bắt buộc key; thiếu, array, sai format hoặc key đổi payload đều bị chặn. Service giữ optional key cho caller CLI nội bộ hiện có. Các khoản thu hợp lệ khác nhau phải dùng key khác nhau; reference_code không được dùng làm khóa vì một người có thể thu nhiều đợt với cùng tham chiếu.

Claim có PK(actor_id,operation,request_key), FK actor→users. Unique insert chờ transaction cùng key; result được lưu trước commit. Giao dịch lỗi không để lại claim đã commit. Quyền/CSRF vẫn được kiểm tra trước nghiệp vụ. Không dùng localStorage.

## Transaction, trạng thái và chính sách

- Checkout nhiều bản: khóa reader, các book theo ID tăng dần, rồi copy. Kiểm tra giới hạn thẻ/số bản, available/reserved đúng người, FIFO và unique active_copy_id.
- Trả phần/toàn bộ: reader→book→item/copy; duplicate return bị từ chối, không thêm charge/audit/payment. Return-all rollback toàn bộ nếu một bước lỗi; chỉ đóng header khi mọi item đã trả.
- Gia hạn: thẻ active/chưa hết hạn, ownership, item còn giữ/chưa quá hạn, max và hàng đặt trước; snapshot renewal_days/policy_version/due date.
- Thu phí: cùng reader lock với trả/gia hạn; DECIMAL/cents, không thu vượt dư nợ; phân bổ FIFO, tất cả payment/allocation/claim/log/notification cùng transaction.
- Test trigger gây SQL lỗi ở activity_logs sau khi đã ghi header/items/copies, renewal, payment/allocation hoặc return/fines: so hash đủ 11 bảng, rollback khớp toàn bộ.
- State available chỉ good/worn; damaged/lost tự đồng bộ condition; on_loan/reserved phải qua nghiệp vụ, không chỉnh tay. Retired giữ copy/history nhưng loại khỏi tồn phục vụ; có thể về 0 và phục hồi bản đã sửa.
- Khi đổi policy, daily_fine của phiếu/item cũ và ledger gia hạn cũ giữ nguyên. Phiếu mới dùng mức phạt mới; gia hạn mới dùng policy đang có tại giao dịch. Phạt hỏng/mất lấy cài đặt tại lúc ghi nhận trả; charge đã lập không bị sửa ngược khi đổi cài đặt.

## Bảng chức năng trong phạm vi ngày 1

| Chức năng | Trạng thái cuối | Phạm vi đã kiểm chứng |
|---|---|---|
| MySQL schema/PK/FK/unique/CHECK/index | ĐÃ CÓ | 25 bảng, 35 FK, 16 CHECK; audit + negative SQL |
| Bản sao/cached inventory/trạng thái | ĐÃ CÓ | 11 invariant, 8 copy-state checks, HTTP retire cuối |
| Checkout nhiều bản/trả phần/trả tất cả | ĐÃ CÓ | Integration, duplicate, late SQL rollback, hai session độc lập |
| Gia hạn/ownership/thẻ/policy | ĐÃ CÓ | Admin/Độc giả HTTP; version/fine/duration |
| Thu/miễn giảm/phân bổ phạt | ĐÃ CÓ | Phase4, cents/overpay, payment replay/rollback |
| Idempotency checkout/renew/payment | ĐÃ CÓ | Durable MySQL; 3 races same-key + HTTP |
| Catalog/báo cáo/phân trang/quyền | ĐÃ CÓ | 118 permission/report assertions + E2E |
| Upload cover/MIME/size/path | ĐÃ CÓ | GD re-encode; JPG/PNG/WebP; 5MB/dimensions/traversal được E2E kiểm tra |
| PDF tiếng Việt/CSV | ĐÃ CÓ | 108 sprint + 20 PDF checks mới |
| Route đã kiểm thử | ĐÃ CÓ | 273 browser/110 renders +108 sprint; không thấy route chết/SQL lỗi trong coverage |
| Các lỗi P0 ở bảng trên | CÓ LỖI trước sửa → ĐÃ CÓ sau sửa | Lưu cả log tái hiện và log PASS |
| CI/REST API | CHƯA CÓ | Không thêm ngoài phạm vi |
| Scheduler tự động/tải lớn/MariaDB/production | CHƯA KIỂM CHỨNG | Không gán PASS local cho môi trường khác |

## Kết quả chạy lại sau toàn bộ bản sửa

Tất cả kết quả bên dưới thuộc ngày 1, test DB paper_page_test_20261011_201609_2e00 và server8014. Các kết quả 10/10 hoặc lần kiểm toán trước ngày 1 chỉ là lịch sử. Test cài mới và restore chạy vào các schema paper_page_test_install_/restore_ riêng; không tạo lại main.

| Suite | Kết quả thực thi | Log trong storage/backups/day1-20261011/ |
|---|---|---|
| PHP lint | 121 file, 0 lỗi | lint.log |
| Smoke / security | 10 / 15 PASS | run.log / security.log |
| MySQL phase3 / phase4 / completion | 18 / 21 / 26 PASS | mysql_phase3/4/completion.log |
| Permissions/report | 118 PASS | mysql_permissions.log |
| Return-all atomic | 7 PASS | mysql_return_all.log |
| Checkout race | 2 connection, 2 lock waiters, 1 accepted / 1 rejected | mysql_concurrency.log |
| Day1 constraints | 10 PASS, fixtures rollback | mysql_day1_constraints.log |
| Day1 replay/card/workflow | 15 PASS | mysql_day1_regression.log |
| Day1 late-failure/policy | 23 PASS | mysql_day1_atomicity.log |
| Day1 same-key concurrency | 3 races; mỗi race 2 session/2 waiters/1 operation | mysql_day1_concurrency.log |
| Day1 money limits/N+1 | 5 PASS, 1 SELECT | mysql_day1_fine_limits.log |
| Day1 copy states | 8 PASS | mysql_day1_copy_states.log |
| New HTTP backend | 31 PASS | http-regression.log/json |
| Browser completion | 273 PASS, 110 renders | browser-completion.log |
| Browser sprint | 108 PASS | browser-sprint.log |
| Cover formats / PDF | 6 / 20 PASS | cover-formats.log / pdf-validation.log |
| Fresh setup/repeat | 2 PASS, riêng test schema | setup_fresh.log |
| Seed password / reminders | 7 / 5 PASS | password_seed.log / mysql_reminders.log |
| Schema audit main/test | 0 vi phạm; PK/FK/index/CHECK/invariant | schema-main-after.log / schema-test.log |
| Backup restore trước/sau | 24/24 và25/25 bảng exact rows, gồm timestamps | restore-before.log / restore-after.log |
| Syntax JS / diff | PASS | syntax/diff logs |

integration-results.json và browser-final-results.json ghi exit code và kết luận suite; browser cuối hoàn tất 20:55:01 +0700. PASS không dựa vào exit0 đơn lẻ: suite assertion/schema violations/restore hashes phải khớp.

## Điểm khôi phục và chạy lại

Bằng chứng nằm trong storage/backups/day1-20261011/ (ignored, có dữ liệu backup riêng tư; không đưa lên Git).

- source-before-audit.zip: 155 file, CRC/per-entry SHA kiểm chứng. SHA-256: 91c0ecd3b45d9778f0fb59fc0d4ac5e0e5f5bc78d2f0439ad0027bc1b79738fd.
- Source manifest và working-tree-before.patch giữ đầy đủ thay đổi trước ngày 1.
- Backup main trước sửa: storage/backups/paper_page_library-20261011-201608-92f994.sql; phục hồi riêng khớp24/24 bảng.
- Backup main cuối: storage/backups/paper_page_library-20261011-205050-4f774e.sql; phục hồi riêng khớp25/25 bảng.
- source-change-summary.json, main-preservation.json, runtime-verification.json, schema-main-before/after.json.
- evidence-manifest.json chứa hash source/artifact cuối; file backup/source ZIP được kiểm chứng lại trước bàn giao.

Khôi phục trước tiên vào database test riêng bằng tests/backup_restore.php hoặc công cụ MySQL; kiểm tra hash rồi mới cân nhắc chuyển cấu hình. Không ghi đè DB chính, không import backup vào main tự động. Khôi phục source ZIP vào thư mục kiểm tra mới; giữ .env/vendor/storage hiện có ở nơi riêng. Không chạy Git reset --hard để hoàn nguyên các thay đổi của người dùng.

Nâng cấp database đang có: php database/backup.php, rồi php database/migrate.php. migrate dùng advisory lock, chỉ chạy migration chưa áp dụng; không seed/reset. setup.php chỉ dành cho cài mới khi muốn danh mục mẫu.

Chạy lại trên test DB:
1. php tests/prepare_test_database.php; đặt DB_NAME theo storage/test_database_name.txt.
2. php database/migrate.php; php tests/prepare_http_fixtures.php; php tests/prepare_report_fixtures.php.
3. Chạy tests/mysql_phase3.php, mysql_phase4.php, mysql_completion.php, mysql_permissions.php, mysql_return_all.php, mysql_concurrency.php và các mysql_day1_*.php.
4. Khởi động PHP server test với DB_NAME test, public/index.php, cổng khác main; QA_BASE_URL trỏ đúng server.
5. php tests/mysql_reminders.php trước browser_completion.cjs; browser_sprint.cjs rồi browser_cover_formats.cjs/pdf_validation.py.
6. php tests/prepare_day1_http.php rồi browser_day1_backend.cjs; php tests/lint.php; tests/audit_backend_database.php read-only.
7. Không chạy --reproduce trên bản đã sửa; đó là chế độ ghi nhận lỗi trước sửa, không phải PASS regression.

## P1 còn lại / giới hạn nghiệm thu

- Tìm substring %term% quét bảng; B-tree title/author không phục vụ wildcard đầu. Không tự đổi semantics sang FULLTEXT. Chưa benchmark dữ liệu lớn.
- Register sách/mượn/độc giả/metadata/report có phân trang server-side; payment feed/notification feed/hồ sơ lịch sử dài và danh sách chọn có giới hạn hoặc chưa có phân trang riêng. Cần phân trang đầy đủ khi chốt phạm vi UX/ngày tiếp theo.
- Xác minh/cấu hình lịch maintenance tự động khi triển khai; main có 1 hold quá hạn chưa xử lý, không sửa dữ liệu để làm đẹp bằng chứng.
- Chưa chứng minh mọi lịch xen kẽ của mixed-operation concurrency, tải lớn, Linux/Nginx/MariaDB, HTTPS public hoặc pentest độc lập. Không có blocker local cho các P0 đã tái hiện.
