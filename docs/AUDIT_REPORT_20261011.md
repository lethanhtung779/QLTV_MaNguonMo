# Kiểm toán và sửa lỗi cuối — 11/10/2026

## Source và môi trường

Nguồn chính thức: D:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage. Branch minh, HEAD e78f09a77371ac1b62be238e8e835cb6491f2e92; origin https://github.com/lethanhtung779/QLTV_MaNguonMo.git. Một worktree; có thay đổi chưa commit từ sprint trước, được giữ trong backup. Không fetch để tuyên bố remote mới nhất; không commit/push/merge.

Đã đọc/kiểm kê tệp văn bản PHP/JS/CSS/SQL/migrations/tests/tài liệu. source-read-inventory.json ghi hash/line trước sửa; source-structure-review.json ghi hàm/route/headings. Kiểm kê tự động không chứng minh mọi nhánh hoạt động; bảng trạng thái dựa thêm đối chiếu code và test thực thi. Vendor và artifact QA không được coi là ứng dụng cần viết lại.

Đầu phiên không có PHP server ứng dụng chạy; đã khởi động source chính tại http://127.0.0.1:8010 và QA tại8013 với document root public/. CSS/JS HTTP đối chiếu SHA-256 với source chính; trang sách khớp đầu sách MySQL hiện có. Không dùng index.html/source lồng cũ.

Main: PHP8.3.33/MySQL8.4.3, paper_page_library; 24 bảng,34FK,migrations001–008; 30đầu sách/105copies/4users/4readers/5loans/6items/1reservation; inventory_mismatch=0. SHA-256 dữ liệu từng bảng trước/sau khớp24/24. Không reset/xóa/setup/seed/maintenance main.

QA ghi dữ liệu: paper_page_test_20261011_194427_cd59, server8013. Clone kiểm tra timestamp sau sửa: paper_page_test_20261011_195015_b121. Restore luôn tạo schema mới paper_page_test_restore_..., không phục hồi đè main.

## Bảng chức năng sau sửa

ĐÃ CÓ = có triển khai và test phiên này kiểm tra các luồng nêu dưới đây. CÓ LỖI = lỗi còn mở đã tái hiện. CHƯA CÓ khác với CHƯA KIỂM CHỨNG.

| Chức năng | Trạng thái | Căn cứ phiên11/10 |
|---|---|---|
| Public/catalog/chi tiết/tìm kiếm/lọc/sort/pagination | ĐÃ CÓ | browser_completion; main-http |
| Login/register/logout/hồ sơ ba vai trò | ĐÃ CÓ | browser lifecycle/sprint; security |
| Nhân viên/chặn tự khóa/22 quyền theo action | ĐÃ CÓ | sprint; SQL permission118 |
| Sách/thể loại/tác giả/NXB/giữ lịch sử | ĐÃ CÓ | SQL completion; native CRUD |
| Copies/barcode/kệ/tình trạng/kho | ĐÃ CÓ | SQL completion; browser |
| Upload PNG/JPEG/WebP/an toàn MIME,size,dimension | ĐÃ CÓ | sprint; formats6 |
| Thẻ/tài khoản liên kết/khóa,kích hoạt/lịch sử | ĐÃ CÓ | lifecycle/sprint |
| Mượn nhiều bản/trả phần,toàn bộ/condition snapshot | ĐÃ CÓ | lifecycle; atomic return7 |
| Chống mượn đồng thời cùng copy | ĐÃ CÓ | 2sessions/2lockwaiters/1accepted |
| Đặt trước FIFO/expiry/cancel/fulfillment | ĐÃ CÓ | phase3/lifecycle/reminders |
| Gia hạn/giới hạn/ownership/policy version | ĐÃ CÓ | phase3/lifecycle |
| Phạt quá hạn,hỏng,mất/thu phần,toàn bộ/miễn giảm | ĐÃ CÓ | phase4/lifecycle/sprint |
| Thông báo MySQL/đọc/chống trùng/nhắc hạn | ĐÃ CÓ | reminders5/lifecycle |
| PDF tiếng Việt mượn,trả,thu phí/ownership | ĐÃ CÓ | sprint; PDF20 trên6PDF |
| CSV sách,độc giả,mượn,kho,phí,tháng/BOM/formula | ĐÃ CÓ | phase4/HTTP sprint |
| Dashboard/report/lọc ngày/phân trang | ĐÃ CÓ | SQL permissions/sprint/viewport |
| Cài đặt phiên bản chính sách/audit | ĐÃ CÓ | phase3,4/sprint |
| Mật khẩu/thu hồi phiên/byte limit | ĐÃ CÓ | password HTTP22/seed7 |
| Backup/clone/restore giữ timestamp | ĐÃ CÓ | restore24/24; clone24/24 |
| Responsive PAPER & PAGE | ĐÃ CÓ | lifecycle110renders; dashboard10 |
| Lỗi P0/P1 đã tái hiện còn mở | CÓ LỖI: không còn trong tập đã sửa | Không suy ra không còn mọi lỗi |
| REST API/CI/vai trò tùy ý | CHƯA CÓ | Ngoài phạm vi hiện tại |
| HTTPS public/Linux/Nginx/MariaDB/tải lớn/pentest | CHƯA KIỂM CHỨNG | Chưa chạy môi trường đó |
| Task Scheduler/cron thực tế | CHƯA KIỂM CHỨNG | Có CLI maintenance, chưa kiểm tra lịch |
| Ba tài khoản thật main sau sửa | CHƯA KIỂM CHỨNG | Chỉ GET public để giữ main; QA ba vai trò trên DB riêng |

## Lỗi đã tái hiện và sửa trực tiếp

| ID/mức | Trước sửa | Thay đổi/tiêu chí đạt |
|---|---|---|
| A/P0 | Reset/đổi password nhưng phiên khác còn truy cập | Session credential fingerprint đối chiếu DB mỗi request; current self-change stamp sau commit; phiên khác về login |
| B/P0 | >72byte bị bcrypt bỏ suffix, một số form trim password | Giới hạn byte/NUL, giữ whitespace, chặn cả login/seed; exact72/UTF8/suffix/arrays được test |
| C/P0 | Backup/clone bỏ DEFAULT_GENERATED và lịch sử timestamp/default | Chỉ bỏ STORED/VIRTUAL GENERATED; restore/clone từng dòng đủ24bảng |
| D/P1 | Cancel confirm nhưng nút vẫn bị disable | Submit listener tôn trọng defaultPrevented; native cancel giữ nút |
| E/P1 | Tên sách dài khiến dashboard1024 tràn thành1093px | minmax(0,1fr)/min-width:0;10role/viewport checks và ảnh trước/sau |
| F/QA | Session fixture cũ bị redirect sớm, exit0 nhưng chưa xong test | Cập nhật credential_version; phase3/4 in đủ18/21assertions |

Không đổi schema/dependency/kiến trúc. Giữ màu/font PAPER & PAGE; CSS chỉ sửa co cột, JS sửa submit bị hủy. Phiên cũ phải login lại một lần. Nếu tài khoản cũ từng dùng password>72byte, Admin cần đặt lại trong giới hạn.

## Bằng chứng kiểm thử cũ/mới

TEST_REPORT.md ngày10/10 và JSON cũ273/108 được giữ riêng trong previous-qa-evidence.zip. Lượt clone GitHub ngày10/10 không phải test mới. Evidence phiên11/10 nằm trong storage/backups/audit-20261011/.

PASS cần assertion/kết luận đầy đủ, không chỉ mã0. Password trước sửa:9vi phạm/13kỳ vọng; restore-old-bug.log:16FAIL/24bảng; browser_completion-failed-overflow.log ghi lần tràn. Những kết quả này không tính PASS.

| Bộ kiểm thử phiên11/10 | Kết quả | Log/artifact trong thư mục audit |
|---|---|---|
| PHP lint | 109 file sạch | lint.log |
| Smoke/security | 10/15PASS | run.log/security.log |
| MySQL phase3/4 | 18/21PASS sau sửa fixture | mysql_phase3.log/mysql_phase4.log |
| Completion/permissions/atomic return | 26/118/7PASS | mysql_completion/mysql_permissions/mysql_return_all.log |
| Concurrency | PASS:2sessions/2waiters/1accepted | mysql_concurrency.log |
| Reminders | 5PASS | mysql_reminders.log |
| Fresh setup/idempotency | 2PASS; DB mới9sách/63copies | setup_fresh.log |
| Browser lifecycle | 273 PASS / 110 renders sau bản sửa cuối | browser_completion.log |
| Browser sprint | 108 PASS sau bản sửa cuối | browser_sprint.log |
| JPEG/WebP | 6 PASS sau bản sửa cuối | browser_cover_formats.log |
| PDF text/raster | 20 PASS trên 6 PDF sau bản sửa cuối | pdf_validation.log |
| Password/session HTTP | 22PASS | password-after.json/log |
| Password seed | 7PASS | password_seed.log |
| Dashboard tên dài | 10PASS | dashboard-overflow.json/log;overflow-after.png |
| Backup restore/clone | 24/24PASS mỗi bộ | restore-after.log/restore-*.json/clone-integrity.json |
| Main HTTP/source/private paths | 9PASS,chỉGET | main-http.json/log |
| Main data unchanged | 24/24table hashes khớp | database-before.json/database-after.json |
| Diff/JS syntax | PASS | git-diff-check.log/js-syntax.log |

Không cộng110renders hoặc24bảng vào sốbrowserchecks. Manifest cuối ghi thời gian/hash/source và artifact mới; credentials không xuất ra tài liệu/Git.

## Điểm khôi phục và cách dùng

source-before-audit.zip trước sửa có149file, mỗi entry đối chiếu hash và CRC. SHA-256 archive:
8930c93ba683ff1eed22aff613c7e6cafb0004adb2b8d1160d1fb9fbfb1993b7.
source-manifest.json ghi HEAD/branch/hash từngfile; working-tree-before.patch lưu binary diff cũ; env-private.before lưu .env riêng.

Backup main đúng trước sửa ứng dụng:
storage/backups/paper_page_library-20261011-194700-f52903.sql.
restore-before-changes.log khớp24/24bảng.
Backup từ script đã sửa:
storage/backups/paper_page_library-20261011-195012-f36f72.sql,
restore-after.log khớp24/24.
Bản194619-e5c2c8 có lỗi timestamp chỉ dùng làm evidence, không dùng phục hồi.

Khôi phục thử:
1. Kiểm tra ZIP SHA theo manifest; giải nén vào thư mục riêng để giữ source hiện tại.
2. Dùng .env riêng và DB_NAME test; composer install từ lock, không update.
3. Từ source hiện tại chạy php tests/backup_restore.php <backup đúng>; đích luôn schema paper_page_test_restore_... mới, so với nguồn đọc.
4. Không tự replace/reset source hoặc main. Backup SQL/.env có dữ liệu riêng, nằm ngoài public và Git.

Có hướng dẫn sao lưu upload trong INSTALLATION_GUIDE; backup SQL và dữ liệu upload phải giữ riêng khi có sử dụng. Không đưa backup/QA credentials vào bản phát hành công khai.

## Chạy lại và giới hạn

Theo README, tạo DB test/fixtures và server test; không sửa .env main. Test mới: browser_password_audit.cjs, browser_dashboard_overflow.cjs, password_seed.php, backup_restore.php. PHP test ghi có DB_NAME test; browser dùng QA_BASE_URL8013.

Nếu chạy lifecycle nhiều lần, chuẩn bị lại mysql_reminders.php vì ready reservation đã fulfillment ở lượt trước. Không dọn/reset main. Server 8013 và credentials HTTP dùng DB cd59; storage/test_database_name.txt đã đặt lại cd59 để khớp server/fixtures đang chạy. Clone b121 chỉ dùng đối chiếu timestamp, tên được lưu trong clone-integrity.json.

Chưa chứng nhận HTTPS/public/Linux/MariaDB/load/pentest/scheduler. Xem [FINAL_SPRINT_PLAN](FINAL_SPRINT_PLAN.md) để theo dõi điều kiện bàn giao local.

Kết luận: hoàn tất kiểm toán và sửa các P0/P1 đã xác minh cho bàn giao local. Checklist được chốt; chưa kiểm chứng production. Source và .env chính được giữ, dữ liệu24/24bảng không thay đổi; không push/merge. Manifest kiểm chứng gồm hash source, log và artifact mới, hash backup, kiểm tra palette/font và composer lock giữ nguyên.
