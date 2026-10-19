# Cập nhật AI Chatbot — 12/10/2026

[Báo cáo thực thi chatbot](CHATBOT_TEST_REPORT_20261012.md): PHP140, MySQL38+12, Chrome65 và core273/110 renders. API live BLOCKED thiếu khóa/model; không dùng kết quả lịch sử thay kết quả mới.

# Đủ nhóm thể loại — 12/10/2026

[Báo cáo mới nhất](CATEGORY_COVERS_REPORT_20261012.md): thêm7 sách thật/bìa local,8 nhóm có ảnh;49tổng/28public/105copies,25/28public có ảnh. **Không thiếu bìa nào:BLOCKED3**. Main Chrome706+56/QA56,PHP128,MySQL18+7; bảo toàn25bảng. Các kết quả dưới là lịch sử.

---

# Bổ sung bìa còn lại — 12/10/2026

[Báo cáo mới nhất](REMAINING_COVERS_REPORT_20261012.md): thêm6 bìa tiếng Anh theo ISBN, không ẩn sách còn lại; 12local +6remote có ảnh/21 công khai,3 chưa xác minh. Chrome155 PASS, lint127, MySQL18+7, bảo toàn25 bảng. NXB/năm bản dịch còn cần xác minh. Nội dung dưới là lịch sử.

---

# DANH MỤC THẬT — 12/10/2026

[Báo cáo mới nhất](REAL_CATALOG_REPORT_20261012.md):12 bìa chính thức local,42 tổng/21 công khai,21 demo ẩn công khai,105 copies nguyên vẹn;9 sách công khai cũ thiếu bìa. Chrome824 PASS/MySQL18+7/lint126. Nội dung dưới là lịch sử.

---

# KIỂM THỬ BÌA SÁCH — 11/10/2026

[Báo cáo phiên mới](BOOK_COVER_AUDIT_20261011.md). Chrome100%,4 widths:1053 checks PASS;136 public +44 authenticated renders. MySQL18/7,upload6,lint124 PASS.25/25 bảng main nguyên vẹn. **Bìa thật0/30; mục tiêu đủ bìa BLOCKED vì dữ liệu minh họa/ấn bản chưa xác minh**. Số273/108 bên dưới là lịch sử sprint trước, không cộng vào phiên này.

---

# TEST REPORT — FINAL SPRINT NGÀY2 — 11/10/2026

[Ma trận43 trang/vai trò, screenshot và lỗi/file](DAY2_UI_REPORT_20261011.md), [checklist](FINAL_SPRINT_PLAN.md). Main8010/paper_page_library; QA8015/paper_page_test_20261011_214305_37d8; Google Chrome155.0.8059.39, zoom100%.

| Test thực thi sau sửa ngày2 | PASS | Bằng chứng storage/backups/day2-20261011/ |
|---|---|---|
| UI responsive | 252 checks/215 renders,0 FAIL | final/result.json,ui-final.log và215 ảnh |
| Workflow /state/redirect /audit-search /phone | 86 /8 /7 /41 | workflows/result.json,states-after.json,activity-search-after.json,phone-pattern-after.json |
| Browser completion/sprint mới | 273 /108 | browser-completion.log,browser-sprint.log |
| HTTP replay/zero-copy | 31 | http.log,final/http-regression.json |
| Password/long title/covers/PDF | 22 /10 /6 /20 | password/overflow/cover-formats/pdf.log |
| PHP lint / JS syntax / diff | 122 PHP,0 lỗi | lint.log,syntax-final.json,diff-check.log |
| MySQL phase3/phase4/completion/permissions | 18 /21 /26 /118 | regression-results.json +từng log |
| Return-all/reminders/core/security | 7 /5 /10 /15 | return-all/reminders/smoke/security.log |
| Main /backup restore | 25/25 bảng exact rows/timestamp | main-preservation.json,restore.log |

PASS có log/kết luận assertion, không chỉ process exit0. Không cộng lượt trung gian. 8 FAIL ảnh ở lượt trung gian là ảnh lazy-load chưa cuộn tới, đã chỉnh test và chạy lại215 render; lỗi tương tác3 FAIL ban đầu có bằng chứng trước/sau. Palette/font/dependency giữ nguyên. Trang local0 FAIL/0 BLOCKED; thiết bị thật/production/packet loss sau POST chưa kiểm chứng, xem phạm vi trong báo cáo ngày2.

---

Nội dung ngày1 và ngày10Oct bên dưới là lịch sử, không phải lần chạy UI ngày2.

# TEST REPORT — FINAL SPRINT NGÀY 1 — 11/10/2026

Source chính thức QLTV_MaNguonMo-github-stage, branch minh/HEAD e78f09a. Test DB paper_page_test_20261011_201609_2e00, server8014; main8010/paper_page_library. [Chi tiết lỗi/file/khôi phục](DAY1_BACKEND_REPORT_20261011.md), [checklist](FINAL_SPRINT_PLAN.md).

### Test thực thi mới sau bản sửa cuối

| Nhóm | Kết quả | Bằng chứng riêng ngày 1 |
|---|---|---|
| PHP lint | 121 file,0 lỗi | lint.log |
| MySQL phase3/phase4/completion | 18/21/26 PASS | integration-results.json và từng log |
| Permissions/report / return-all | 118/7 PASS | mysql_permissions.log / mysql_return_all.log |
| Core smoke/security | 10/15 PASS | run.log / security.log |
| Schema constraints / workflow/card/replay | 10/15 PASS | mysql_day1_constraints.log / mysql_day1_regression.log |
| SQL late failure/rollback/policy | 23 PASS;11 bảng được so hash rollback | mysql_day1_atomicity.log |
| Concurrent same key | 3 races;2 session/2 lock waiters/1 operation mỗi race | mysql_day1_concurrency.log và race JSON |
| Checkout cạnh tranh | 2 session/2waiters/1accepted+1rejected | mysql_concurrency.log |
| Money description / N+1 | 5 PASS;1 SELECT thực đo | mysql_day1_fine_limits.log |
| Copy-state / zero inventory | 8 PASS | mysql_day1_copy_states.log |
| HTTP mới | 31 PASS | http-regression.log/json |
| Browser completion | 273 PASS,110 renders | browser-completion.log |
| Browser sprint | 108 PASS | browser-sprint.log |
| Upload formats / Vietnamese PDF | 6/20 PASS | cover-formats.log / pdf-validation.log |
| Fresh/repeat setup / seed passwords / reminders | 2/7/5 PASS | setup_fresh.log / password_seed.log / mysql_reminders.log |
| Schema main/test | 0 violation;25 PK tables/35 FK/16 enforced CHECK/11 invariant | schema-main-after.json và schema-test.log |
| Restore SQL trước/sau | 24/24 và25/25 bảng exact rows | restore-before/after.log |
| Dữ liệu main | 23 bảng cũ giữ hash; registry chỉ thêm009/010, mutation_requests rỗng | main-preservation.json |
| JS syntax/diff | PASS | syntax/diff logs; CSS/dependency hash giữ nguyên |

Log và manifest: storage/backups/day1-20261011/. integration-results.json/browser-final-results.json có exit code và kết luận suite. Bộ browser cuối chạy 20:54–20:55 +0700 sau toàn bộ bản sửa. Không tính test ngày10/10 hoặc lần audit trước ngày1 vào kết quả này.

### Lỗi trước sửa được lưu

- regression-before.log:5 vi phạm (checkout replay,renew twice,payment twice,expired/inactive card renewal).
- fine-limits-before.log: SQL truncation mô tả overdue/condition charge và7 SELECT.
- copy-states-before.log: MySQL3819 khi retire bản cuối.
- http-zero-stock-failed.log/http-zero-debug.log: edit sách chưa phân loại/category rỗng bị validation từ chối.
- Những log này là REPRODUCED/FAIL trước sửa, không phải PASS. Các regression cuối bên trên đã đạt.

Không còn P0 đã xác minh đang mở; không có P0 BLOCKED. Chưa nghiệm thu scheduler/tải lớn/MariaDB/production; P1 phân trang feed và tìm wildcard được ghi trong kế hoạch.

---

## Báo cáo lịch sử trước ngày 1 (giữ nguyên để đối chiếu)

# Báo cáo nghiệm thu thực thi — 10/10/2026

> Trạng thái và test thực thi 11/10/2026: [AUDIT_REPORT_20261011](AUDIT_REPORT_20261011.md), [FINAL_SPRINT_PLAN](FINAL_SPRINT_PLAN.md). Nội dung có ngày cũ bên dưới là lịch sử, không phải test chạy lại hôm nay.


Đã chạy PHP 8.3.33, MySQL 8.4.3 và Microsoft Edge qua Playwright trên Windows/Laragon. Không lấy kết quả ngày 08/10 làm kết quả sprint mới.

## Nguồn và database

- Nguồn chính thức: QLTV_MaNguonMo-github-stage; Git branch minh, HEAD e78f09a77371ac1b62be238e8e835cb6491f2e92. Working tree sạch trước sprint; hai source PHP giống nhau trước khi sửa. Không có server PHP đang chạy lúc bắt đầu.
- Database ghi dữ liệu QA: paper_page_test_20261010_111217_86af.
- Clone GitHub thực tế: storage/fresh-install/github, nhánh minh ở cùng HEAD. Áp dụng source local chưa commit lên clone sạch, cài Composer từ lock, không copy vendor/storage cũ. DB cài mới paper_page_test_clone_36299053f3; server riêng 8012.
- Database chính paper_page_library được backup trước migration 007–008. Counts trước/sau migration không đổi: 30 sách, 105 bản sao, 4 độc giả, 4 users, 5 phiếu, 6 items, 1 đặt trước. Không reset.
- Sau migration: 24 bảng, 34 FK, inventory_mismatch=0. Login/maintenance thông thường có thể bổ sung audit/thông báo/phí quá hạn theo ngày hiện tại.

## Kết quả

| Kiểm tra thực thi | Kết quả |
|---|---|
| PHP lint source ứng dụng | 107 files, không lỗi; không quét vendor/QA clone |
| Smoke/helpers | 10 assertions |
| Security/session/CSRF/XSS/helpers | 15 assertions |
| MySQL phase 3 | 18 assertions |
| MySQL phase 4: ledger/payments/waivers | 21 assertions |
| MySQL completion: copies/metadata/rollback/inventory | 26 assertions |
| Reminders/ownership/reserved checkout | 5 assertions |
| Concurrency | Hai process PHP, quan sát 2 row-lock waiters, 1 mượn thành công/1 bị từ chối, 1 active item |
| Ma trận quyền và phân trang SQL | 118 assertions; override tạm rollback |
| Trả toàn bộ atomic | 7 assertions; lỗi bản thứ hai hoàn tác bản thứ nhất và tồn kho |
| Browser lifecycle source chính | 273 checks, 110 viewport/page renders |
| Browser lifecycle clone cài mới | 273 checks, 110 viewport/page renders |
| Browser sprint source chính | 108 checks |
| Browser sprint clone cài mới | 108 checks |
| Upload JPEG/WebP ngoài PNG trong sprint | 6 checks qua form thật và HTTP MIME |
| PDF tiếng Việt | 20 checks trên 6 PDF; trích xuất text và render raster thành công |
| Website chính/ba vai trò | 16 checks đăng nhập/routes thành công |
| Cài mới/idempotency | Setup đầu 9 đầu sách/63 copies/9 liên kết tác giả; lần hai giữ nguyên |
| Composer install clone | 5 dependency cài từ lock; không copy vendor |
| JS syntax | Không lỗi |
| git diff --check | Không lỗi whitespace |

composer validate xác nhận cấu trúc hợp lệ. Lần chạy --strict cảnh báo metadata chưa khai báo license; không tự gán giấy phép cho source của người dùng. Đây không phải lỗi chạy ứng dụng.

## Luồng demo 20 bước

| Bước | Bằng chứng đã chạy |
|---|---|
| 1 Admin đăng nhập | Browser lifecycle/sprint và live main |
| 2 Tạo Thủ thư | Native dialog; đăng nhập bằng tài khoản vừa tạo |
| 3 Tạo độc giả | Thẻ/email/tài khoản cùng transaction; login Độc giả |
| 4 Thêm sách | Native form, tiếng Việt và upload PNG thật |
| 5 Ba bản sao | Kiểm đếm bảng quản lý bản sao; kệ/barcode có kiểm thử |
| 6 Mượn hai bản | Checkbox có tìm kiếm, Thủ thư mới lập phiếu |
| 7 Tồn kho giảm | 1/3 khả dụng trong UI sau reload |
| 8 Trả một bản | Còn 1 item đang mở |
| 9 Gia hạn | UI hiển thị lượt 1 và hạn mới |
| 10 Quá hạn DB test | Form ngày mượn -5, hạn -2 |
| 11 Trả/phí | Phát sinh ledger thật, tình trạng trả được lưu |
| 12 Thu một phần | Native payment, phân bổ theo phí |
| 13 Thu phần còn lại | Dư nợ đã xử lý; thử thu vượt nợ bị từ chối |
| 14 Lịch sử/thông báo | Độc giả thấy mã thanh toán và thông báo nhận trả |
| 15 Dashboard | KPI đối chiếu repository SQL, tồn kho mismatch=0 |
| 16 PDF/CSV | 3 loại PDF staff, PDF reader-owned, các CSV UTF-8 |
| 17 Độc giả riêng tư | Tài khoản vừa tạo thấy lịch sử của mình |
| 18 Trái quyền | 403 trên GET/POST, thu hồi quyền trong session, IDOR PDF bị từ chối |
| 19 Desktop/mobile | 1440/1366/1024/768/390; màn mới 1440/390 |
| 20 Reload | Book/copies/upload/loans/renewals/payment vẫn tồn tại |

Upload giả MIME, quá dung lượng, quá dimensions bị từ chối và không tạo sách dở dang. Ảnh được đổi tên, re-encode, raw storage không truy cập HTTP. Backend chặn giả cấp quyền quản trị cho Thủ thư; tự khóa/hạ Admin đang dùng bị từ chối.

Trả toàn bộ được thao tác qua nút xác nhận thật, đóng tất cả item, trả tồn kho về 3/3, lần trả lặp bị từ chối. Test MySQL chủ động tạo xung đột ở bản thứ hai rồi xác nhận toàn bộ rollback.

## Lỗi đã phát hiện và xử lý trong sprint

- Dialog nhân viên ban đầu chưa đi qua opener chung: sửa JS hỗ trợ dialog có form cố định và giữ ID sửa.
- Test chọn Ctrl cũ không phù hợp checkbox mới: cập nhật test để thao tác checkbox thật.
- Test bỏ chọn quyền dùng locator động :checked bị đổi chỉ số: sửa theo permission code ổn định.
- Test trang 2 chưa có đủ dữ liệu: tạo 25 luồng checkout/return thật trên DB test và phân biệt paginator loans/inventory/fines.
- Cấu hình demo email có thể không có khóa EMAIL: dùng fallback đúng của seed khi nghiệm thu main.
- Các lỗi trên đã được chạy lại; không tính lượt chạy thất bại là PASS.

Bằng chứng local trong storage: browser_qa_result.json, sprint_qa_result.json, main_browser_result.json, cover_format_result.json, logs, screenshot ba vai trò và receipts-qa/*.pdf/*.png/*.csv. Clone có result/log riêng. Tài khoản và artifact chứa dữ liệu QA không đưa vào Git/ZIP.

## Giới hạn đã biết

Chưa nghiệm thu Linux/Nginx, MariaDB, HTTPS public hay tải lớn. Maintenance cần lịch bên ngoài. Quyền tùy chỉnh giới hạn ở nhân viên Thủ thư, vai trò vẫn Admin/Thủ thư/Độc giả. PDF trả lịch sử trước migration 008 không có snapshot tình trạng; hiển thị rõ thiếu ghi nhận. CSV giới hạn 10.000 dòng mỗi lần. Remote minh chưa chứa sprint vì chưa được yêu cầu push.


Cập nhật trang chủ 12/10/2026: danh mục đủ 28 sách công khai phân trang 12/12/4, số trang cho catalog; PHP3/3, Chrome14 PASS, main25/25 không đổi. [Báo cáo](HOME_PAGINATION_REPORT_20261012.md).


Chatbot đối chiếu attachment gửi lại: sửa intent phí còn nợ, bổ sung 10 assertions; lần chạy mới MySQL48+12, Chrome65 PASS, main25/25 không đổi. API live vẫn BLOCKED thiếu khóa/model. [Báo cáo bổ sung](CHATBOT_FOLLOWUP_REPORT_20261012.md).


## Sửa câu hỏi khả năng trợ lý — 12/10/2026

Screenshot cho thấy “bạn giúp được gì” trả unknown. Đã thêm intent help, hỗ trợ các cách hỏi Bạn có thể giúp tôi gì/Bạn có thể làm những gì/Trợ lý hỗ trợ gì/help/giúp tôi. ChatService trả khả năng thực tế, gợi ý tiếp theo và link danh mục; mô tả báo cáo chỉ khi có quyền. Không cần LLM và không bịa dữ liệu.

Thực thi mới: MySQL chatbot56 assertions PASS, Chrome regression65 PASS và kiểm tra chính câu hỏi trong screenshot trên Chrome390px PASS; lint2 file service PASS; git diff --check sạch. Bằng chứng và backup: storage/backups/chat-help-20261012, help-mobile.png, help-browser.json, mysql-results.json, browser-results.json.


Đăng nhập/đăng ký: hướng dẫn vai trò, giữ email và thông tin không nhạy cảm khi lỗi, điền email sau đăng ký; Chrome10 PASS và lint3 PASS. [Báo cáo](AUTH_UX_REPORT_20261012.md).
