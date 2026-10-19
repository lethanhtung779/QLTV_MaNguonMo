# FINAL SUBMISSION CHECK — GO/NO-GO

Ngày kiểm tra: 14/10/2026. Phạm vi: source hiện tại `QLTV_MaNguonMo-github-stage`; không reset database, không commit/push và không thêm tính năng lớn.

## Đối chiếu bằng chứng

Sáu tài liệu FINAL RELEASE đã được đọc và đối chiếu với artifact thực tế. Số liệu hợp lệ: lint 140 file/0 lỗi; MySQL 12 suite/319 assertions; reminders 5/5; browser core 273/273 với 110 page-viewport renders; browser sprint 108/108; restore 25/25 bảng exact rows/timestamps. Database chính được audit ở trạng thái 25 bảng, 35 FK, 11 migration, 49 sách, 105 bản sao và 0/11 invariant vi phạm.

Đăng nhập tập trung được chạy lại trên QA: Admin → `/admin`, Thủ thư → `/admin`, Độc giả → `/account`. Luồng mượn hai bản, trả phần, gia hạn, trả toàn bộ, phí và tồn kho được thực thi trong browser core/sprint vừa chạy; không chạy lại thao tác ghi trên database chính.

Ba bản ghi B006/B007/B009 đều không có ISBN và `cover_url` đang NULL. Báo cáo nghiên cứu trước không xác minh được đúng tác phẩm/tác giả/NXB/năm; hệ thống giữ placeholder có tên sách+tác giả. Không dùng ảnh gần giống hoặc ảnh AI.

ERD có trong `DATABASE_DESIGN.md` và `ERD.md`. Use Case và Sequence Diagram mượn/trả đã bổ sung vào `ARCHITECTURE.md`. Checklist nộp bài nằm ở `FINAL_SUBMISSION_CHECKLIST.md`; demo 10–15 phút nằm ở `DEMO_SCRIPT.md`.

## Git và dữ liệu nhạy cảm

Working tree có 170 đường dẫn chưa commit: 73 modified và 97 untracked. Đây là toàn bộ chuỗi sprint, không phải lỗi chạy chương trình nhưng là rủi ro đóng gói lớn. Không dùng `git add .`; phải review/stage theo nhóm trước khi người dùng quyết định commit.

`.env`, storage JSON/log/backup/upload/cache và credentials QA đang bị ignore; không có file nhạy cảm nào trong kết quả `git ls-files` kiểm tra. Quét pattern ngoài storage chỉ gặp placeholder `OPENAI_API_KEY=<khóa của bạn>` trong tài liệu. `.env.example` có mật khẩu/API key trống. Đã ignore thêm `storage/*.html` để artifact nghiên cứu `standardebooks-probe.html` không vào GitHub.

Người dùng đã từng gửi một mật khẩu trong hội thoại và mật khẩu tài khoản local đã được đặt lại theo yêu cầu trước đó. Nếu máy/repository/video được chia sẻ, nên đổi mật khẩu này trước khi bàn giao; không ghi nó vào tài liệu hoặc commit.

## Hoàn thành và tương lai

Hoàn thành có bằng chứng: xác thực/ba vai trò, quyền backend, catalog/metadata/copies/barcode, độc giả/nhân viên, mượn-trả-gia hạn-đặt trước, phí/biên lai, dashboard/report/PDF/CSV, upload/fallback bìa, thông báo in-app, backup/restore và chatbot rule/MySQL.

Phát triển tương lai: QR camera/in nhãn, kiểm kê theo đợt, favorites/reviews, SMTP live, LLM live, HTTPS/Linux production, tải lớn và pentest độc lập. Ba bìa chưa xác minh là hạn chế dữ liệu, không che giấu bằng ảnh sai.

## Quyết định

**GO CÓ ĐIỀU KIỆN** cho nộp và bảo vệ bản localhost P0/P1. Ứng dụng và dữ liệu cốt lõi có bằng chứng kiểm thử đầy đủ. Trước khi upload GitHub/gói nộp, điều kiện bắt buộc là review 170 thay đổi và stage có chọn lọc, kiểm tra lại gói không chứa `.env` hoặc storage riêng tư, và đổi mật khẩu đã chia sẻ nếu môi trường được giao cho người khác.

**NO-GO cho tuyên bố đạt 100% MASTER scope** vì các P2/phụ thuộc ngoài nêu trên chưa hoàn thiện. Không có lỗi P0 còn tái hiện trong lần kiểm tra này.
