# FINAL SPRINT PLAN — 11/10/2026

Nguồn duy nhất: D:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage. Git branch minh; HEAD e78f09a77371ac1b62be238e8e835cb6491f2e92; origin https://github.com/lethanhtung779/QLTV_MaNguonMo.git. Có thay đổi chưa commit từ sprint trước; một worktree. Không push/merge.

> Ngày2 UI/UX: [báo cáo/ma trận Chrome mới](DAY2_UI_REPORT_20261011.md). QA8015 dùng DB độc lập; main8010 giữ nguyên25 bảng.

> Ngày 1 database/backend: [báo cáo mới](DAY1_BACKEND_REPORT_20261011.md). QA hiện tại 8014 → paper_page_test_20261011_201609_2e00. Main hiện có 25 bảng/35 FK/10 migrations; số liệu kiểm toán trước bên dưới là lịch sử.

Web chính 8010 → paper_page_library; web QA 8013 → paper_page_test_20261011_194427_cd59. PHP 8.3.33/MySQL 8.4.3; document root public/. Clone đối chiếu timestamp sau sửa: paper_page_test_20261011_195015_b121.

## Checklist P0 — kiểm toán trước ngày 1

- [x] P0-01: Xác minh đúng source/Git/server/DB; giữ source cũ và toàn bộ thay đổi đã có.
- [x] P0-02: Backup nguồn 149 file và binary patch trước sửa; kiểm tra SHA-256/CRC. Backup SQL main phục hồi vào schema mới, so từng dòng đủ 24 bảng.
- [x] P0-03: Reset/đổi mật khẩu thu hồi phiên khác ở request kế tiếp; giữ phiên tự đổi sau commit; phiên trước nâng cấp cần đăng nhập lại.
- [x] P0-04: Giới hạn bcrypt 72 byte ở tạo/đổi/reset/login/seed, chặn NUL/array và giữ nguyên khoảng trắng. Kiểm tra đúng72, UTF-8, suffix, seed.
- [x] P0-05: Backup/clone giữ DEFAULT_GENERATED, chỉ bỏ STORED/VIRTUAL GENERATED; timestamp và cột sinh lại khớp dữ liệu nguồn.
- [x] P0-06: Cập nhật fixture session phase3/4; PASS phải có kết luận 18/21 assertions, không chỉ exit code 0 khi redirect sớm.
- [x] P0-07: Chốt bộ browser 273, sprint 108, PDF/upload/CSV và SQL permissions/atomic/concurrency sau sửa cuối, lưu manifest bằng chứng.
- [x] P0-08: Dữ liệu chính 24/24 bảng khớp hash trước/sau; không reset/xóa/seed/maintenance main; inventory mismatch=0.

Tiêu chí nghiệm thu P0: không còn lỗi P0 đã tái hiện; PASS có assertion/log/hash và database rõ ràng; source/DB khôi phục được; dữ liệu main giữ nguyên; kiến trúc và dependency giữ nguyên.

## Checklist P1 — kiểm toán trước ngày 1

- [x] P1-01: Cancel confirm không disable/đổi chữ nút hoặc gửi nghiệp vụ.
- [x] P1-02: Dashboard tên sách dài không tràn ở 1440/1366/1024/768/390 px cho Admin/Thủ thư; chỉ sửa co cột, giữ màu/font.
- [x] P1-03: Bảng ĐÃ CÓ/CÓ LỖI/CHƯA CÓ/CHƯA KIỂM CHỨNG và lỗi trước/sau trong AUDIT_REPORT_20261011.md.
- [x] P1-04: Sửa thông tin lỗi thời về PDF/upload/quyền/migrations, gắn nhãn tài liệu lịch sử và link trạng thái mới từ README.
- [x] P1-05: Chốt lint/JS syntax/diff check, manifest, ảnh/PDF và hướng dẫn khôi phục.

Tiêu chí P1: giữ PAPER & PAGE #193E30/#F8F3EB/#E8D8C5/#C58B54/#302B25 và Playfair/Inter; form hoạt động, 5 viewport không tràn; các số test gắn ngày/source/DB.

## Bằng chứng cũ/mới

Bộ 273 browser/108 sprint ngày 10/10 nằm trong previous-qa-evidence.zip trước khi ghi đè artifact. Log phiên 11/10 nằm riêng ở storage/backups/audit-20261011/. Không tính lượt clone GitHub ngày 10/10 là test mới.

Password trước sửa: 9 vi phạm/13 kỳ vọng; sau sửa: 22 PASS. Backup cũ restore lệch 16/24 bảng; backup đúng khớp24/24. Một lượt browser chạy lại phát hiện tràn dashboard1024, được giữ trong browser_completion-failed-overflow.log; phải chạy lại sau sửa mới chốt PASS.

## Ngoài phạm vi local

CHƯA CÓ: REST API, CI, vai trò tùy ý. CHƯA KIỂM CHỨNG: HTTPS public/Linux/Nginx/MariaDB/tải lớn/pentest độc lập/job scheduler thực tế. Có CLI maintenance; không tự cấu hình Task Scheduler.

Bàn giao local hoàn tất khi mọi P0/P1 trên có bằng chứng. Không gán phần trăm hoặc ngày hoàn thiện production khi chưa kiểm chứng môi trường đó.

Chốt nghiệm thu local 11/10/2026: mọi P0/P1 của phiên có evidence; không còn lỗi đã tái hiện đang mở. Kết quả cuối273/108 được chạy sau toàn bộ bản sửa, cùng32 checks mới password/dashboard và7 seed checks. Bằng chứng: evidence-manifest.json trong thư mục audit.

## Ngày 1 — DATABASE/BACKEND — kết quả chốt 11/10/2026

Báo cáo chi tiết/lỗi/file/bằng chứng: [DAY1_BACKEND_REPORT_20261011](DAY1_BACKEND_REPORT_20261011.md).

### P0 ngày 1

- [x] D1-P0-01: Durable MySQL request key ngăn thu phí một phần hai lần; bắt buộc key ở HTTP, từ chối thiếu/array/đổi payload.
- [x] D1-P0-02: Gia hạn gửi lại trả kết quả cũ, không thêm ledger/due date, kể cả đã đạt max.
- [x] D1-P0-03: Checkout gửi lại trả phiếu đã commit; key lỗi rollback có thể retry.
- [x] D1-P0-04: Gia hạn kiểm tra/khóa thẻ active và expires_on.
- [x] D1-P0-05: Tiêu đề/mã tối đa không gây SQL truncation khoản phạt; giữ số tiền và giới hạn mô tả Unicode.
- [x] D1-P0-06: Ngừng sử dụng bản cuối cho tồn phục vụ bằng0; giữ copy và lịch sử; chỉnh metadata với0 không tự sinh lại bản.
- [x] D1-P0-07: Sách chưa phân loại nạp option0; backend chấp nhận category tùy chọn hợp lệ.
- [x] D1-P0-08: Audit toàn bộ PK/FK/unique/index/CHECK, 11 invariant main/test không sai lệch.
- [x] D1-P0-09: Backup nguồn155 file và SQL24/24 trước sửa; backup SQL cuối25/25;23 bảng dữ liệu cũ giữ hash, chỉ registry migrations đổi.
- [x] D1-P0-10: Lint121; MySQL/core/regression/HTTP31; browser273/sprint108 chạy lại sau sửa cuối, có log/hash/test DB rõ ràng.

Tiêu chí: 7 nhóm P0 tái hiện được xử lý; SQL late-failure rollback khớp11 bảng; 3 race same-key dùng2session/2waiters nhưng chỉ1 operation; main không reset/xóa/seed, 30/105 dữ liệu hiện tại được giữ. Không có P0 BLOCKED.

### P1 ngày 1

- [x] D1-P1-01: Đồng bộ phí bỏ SELECT theo từng reader, đo1 SELECT thay7 ở fixture.
- [x] D1-P1-02: Thống nhất khóa reader/book/item/reservation, book tăng dần; expiry recheck sau lấy lock.
- [x] D1-P1-03: Policy values/version đọc từ một immutable row; test policy mới/cũ.
- [x] D1-P1-04: Index borrowed_at và EXPLAIN thống kê; migrate CLI không seed/reset.
- [ ] D1-P1-05: Kiểm chứng tìm substring/quy mô lớn; chỉ đổi chiến lược tìm kiếm khi thống nhất semantics.
- [ ] D1-P1-06: Phân trang đầy đủ feed khoản thu/thông báo/lịch sử và danh sách chọn khi chốt phạm vi UX.
- [ ] D1-P1-07: Xác minh scheduler maintenance thực tế;1 ready reservation hết hạn main được ghi nhận, không tự chạy main.
- [ ] D1-P1-08: Mixed-operation stress/load và môi trường triển khai ngoài local.

P1 đang mở là giới hạn cải thiện/kiểm chứng, không được ghi PASS hoặc gọi là P0 BLOCKED. CSS/màu/font/dependency giữ nguyên; JS chỉ sửa form. Không tự push/merge.


## Ngày2 — UI/UX — 11/10/2026

Bằng chứng và ma trận từng trang: [DAY2_UI_REPORT_20261011](DAY2_UI_REPORT_20261011.md). Các số bên dưới là test mới, không lấy lại273/108 từ ngày1.

### P0 nghiệm thu local

- [x] D2-P0-01: Chrome thật zoom100%,4 nhóm người dùng,5 widths;43 cặp trang/vai trò,215 renders/252 checks PASS,0 FAIL/BLOCKED.
- [x] D2-P0-02: Checkout nhiều bản, partial/full return, renew/reserve/payments, CRUD/profile/password/permissions/report được kiểm thử trên MySQL test độc lập; không giả lập dữ liệu bằng localStorage.
- [x] D2-P0-03: Regression Chrome273/108; UI workflows86/states8/audit-search7/phone41; HTTP31; PDF20/covers6/password22/overflow10; lint122,0 lỗi và MySQL/core logs.
- [x] D2-P0-04: Source backup CRC/hash +SQL restore25/25; main25/25 rows/timestamps khớp; palette/font và dependencies giữ nguyên.
- [x] D2-P0-06: Trang500 an toàn:20 PASS; KPI dư nợ khớp MySQL:60 PASS.
- [x] D2-P0-05: Form invalid không gửi, cancel không khóa nút;500/419/403/404 thật, loading/offline alert+retry; redirect whitelist không đi ra ngoài.

Tiêu chí: không còn P0 UI local đã tái hiện; PASS có JSON/log/screenshot/version/time; không reset main/push/merge. Lỗi đã xác minh được sửa trực tiếp, không chỉ lập kế hoạch.

### P1 trải nghiệm

- [x] D2-P1-01: Dialog padding giữ draft; backdrop/Escape/close và bounds mobile.
- [x] D2-P1-02: Picker reset filter/checkbox/count; chọn1..max theo policy; choices hàng ngang và giữ hidden khi tìm kiếm.
- [x] D2-P1-03: Offline giữ form và cho retry; loading tiếng Việt, double-click bị ngăn.
- [x] D2-P1-04: Menu Thể loại trỏ section thật; cover fallback và tồn0 không gán sai là đang mượn.
- [x] D2-P1-05: Log/report/permissions/staff/settings dùng nhãn Việt; tìm nhật ký theo nhãn Việt/mã gốc, log empty có hướng dẫn.
- [x] D2-P1-06: Form max phí khớp backend; cài đặt save revision; regex phone hợp lệ trên Chrome155,41 cases.
- [x] D2-P1-07: Trả/gia hạn giữ q/status/page, không thêm thao tác tìm lại phiếu.
- [ ] D2-P1-08: Thiết bị thật/screen reader/khác Chrome/production load; chưa kiểm chứng.
- [ ] D2-P1-09: Packet loss sau POST và mọi tổ hợp dữ liệu/quyền quy mô lớn; chưa kiểm chứng.

Bảng rộng cuộn trong vùng table-scroll, không tràn toàn trang. Không đổi design system; chỉ3 quy tắc bố trí checkbox được nối vào CSS. P1 chưa kiểm chứng không được gán PASS hoặc gọi P0 BLOCKED.


## Bìa sách — ưu tiên cao — 11/10/2026

[Báo cáo và nguồn xác minh](BOOK_COVER_AUDIT_20261011.md), [30 cuốn cần bổ sung](BOOK_COVERS_MISSING.csv).

- [x] Đọc30 books +authors/book_authors/publishers/categories và quan hệ; backup/snapshot trước sửa.
- [x] Tra cứu6 ISBN +30 tên/tác giả; ghi evidence và từng quyết định không gắn sai ấn bản.
- [x] Bỏ bìa chữ cái, fallback tên/tác giả; component dùng chung toàn website, card3 dòng/object-fit:contain.
- [x] Chrome4 widths,1053 checks; upload6,MySQL18/7,lint124, main25/25 nguyên vẹn.
- [ ] BLOCKED: bìa thật đã xác minh0/30.21 cuốn minh họa,6ISBN không khớp/ấn bản chưa xác minh,3 khôngISBN/không kết quả. Cần dữ liệu ấn bản/ảnh được phép, không đổi tên/ISBN hoặc gắn ảnh khác để báo PASS.


## Danh mục thật — được người dùng cho phép — 12/10/2026

[Báo cáo](REAL_CATALOG_REPORT_20261012.md).

- [x]12 metadata/cover chính thức đúng ấn bản,local/CC0/hash kiểm chứng.
- [x]Ẩn21 demo chỉ công khai,giữ hoạt động lưu hành/lịch sử.
- [x]QA import12/replay0; Chrome824/MySQL18+7/lint126; backup restore25/25.
- [x]Main42 tổng/21public/105copies; mọi row cũ giữ nguyên metadata/time.
- [ ]9 sách công khai cũ thiếu bìa đúngấn bản; xem CSV.
- [ ]Kiểm kê bản vật lý12 sách mới; hiện0 bản,không tạo tồn giả.


## Bìa còn lại — 12/10/2026

- [x] Tra cứu sáu ISBN, đối chiếu tác phẩm/tác giả, ghi rõ ảnh tiếng Anh và metadata chưa khớp.
- [x] Sửa CSP redirect Internet Archive; Chrome155/PHP127/MySQL18+7 có bằng chứng.
- [x] Giữ nguyên visibility của chín sách còn lại và toàn bộ nghiệp vụ; backup restore25/25.
- [ ] BLOCKED: B006/B007/B009 thiếu ISBN, chưa tìm được bìa đúng bản ghi. Giữ hiển thị, không dùng ảnh khác.
- [ ] Xác minh ấn bản tiếng Việt của sáu sách ISBN tiếng Anh; chưa tuyên bố hoàn thành đúng bản dịch.

[Báo cáo và nguồn](REMAINING_COVERS_REPORT_20261012.md). Tiêu chí hoàn tất: từng bìa có đúng tác phẩm/tác giả/ấn bản và quyền sử dụng, chạy Chrome có ảnh thực tế; dữ liệu nghiệp vụ không đổi.


## Đủ thể loại — 12/10/2026

- [x] Kinh tế/Khoa học/Lịch sử/Tâm lý/Thiếu nhi/Tin học/Văn phòng/Văn học đều có sách thật và ảnh.
- [x] Bổ sung7 sách/bìa local đúng nguồn, license/attribution, không tạo tồn kho giả; giữ tất cả sách cũ.
- [x] Chrome main706+56/QA56,PHP128,MySQL18+7; bảo toàn25bảng và SQLrestore25/25.
- [ ] Yêu cầu0 bìa thiếu:BLOCKED B006/B007/B009 chưa xác minh đúng dữ liệu thư mục, vẫn công khai.

[Báo cáo/ma trận mới](CATEGORY_COVERS_REPORT_20261012.md). Không đổi palette/font/dependency; không push.

## AI Chatbot Sprint — 12/10/2026

- [x] Widget chung PAPER & PAGE, responsive và trạng thái loading/error/empty.
- [x] Search/recommend/context từ MySQL; owner và quyền server; policy phiên bản; chỉ hướng dẫn thao tác ghi.
- [x] CSRF/XSS/SQL/input/rate và fallback; không lưu lịch sử MySQL/localStorage.
- [x] MySQL 38+12, Chrome chatbot 65, core 273/110 renders, PHP lint 140; main bảo toàn 25/25 bảng.
- [x] Tài liệu kiến trúc, API setup, demo và bằng chứng riêng tư.
- [ ] BLOCKED: API AI live/timeout mạng thực tế cần khóa và model; fallback/mock đã PASS.

Tiêu chí nghiệm thu và giới hạn: [CHATBOT_TEST_REPORT_20261012.md](CHATBOT_TEST_REPORT_20261012.md).


Cập nhật trang chủ 12/10/2026: danh mục đủ 28 sách công khai phân trang 12/12/4, số trang cho catalog; PHP3/3, Chrome14 PASS, main25/25 không đổi. [Báo cáo](HOME_PAGINATION_REPORT_20261012.md).


Chatbot đối chiếu attachment gửi lại: sửa intent phí còn nợ, bổ sung 10 assertions; lần chạy mới MySQL48+12, Chrome65 PASS, main25/25 không đổi. API live vẫn BLOCKED thiếu khóa/model. [Báo cáo bổ sung](CHATBOT_FOLLOWUP_REPORT_20261012.md).


## Sửa câu hỏi khả năng trợ lý — 12/10/2026

Screenshot cho thấy “bạn giúp được gì” trả unknown. Đã thêm intent help, hỗ trợ các cách hỏi Bạn có thể giúp tôi gì/Bạn có thể làm những gì/Trợ lý hỗ trợ gì/help/giúp tôi. ChatService trả khả năng thực tế, gợi ý tiếp theo và link danh mục; mô tả báo cáo chỉ khi có quyền. Không cần LLM và không bịa dữ liệu.

Thực thi mới: MySQL chatbot56 assertions PASS, Chrome regression65 PASS và kiểm tra chính câu hỏi trong screenshot trên Chrome390px PASS; lint2 file service PASS; git diff --check sạch. Bằng chứng và backup: storage/backups/chat-help-20261012, help-mobile.png, help-browser.json, mysql-results.json, browser-results.json.


Đăng nhập/đăng ký: hướng dẫn vai trò, giữ email và thông tin không nhạy cảm khi lỗi, điền email sau đăng ký; Chrome10 PASS và lint3 PASS. [Báo cáo](AUTH_UX_REPORT_20261012.md).
