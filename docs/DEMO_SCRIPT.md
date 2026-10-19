# Kịch bản demo bảo vệ Paper Haven (10–15 phút)

1. **0:00–1:30 — Tổng quan Paper Haven:** mở `http://127.0.0.1:8010`, giới thiệu nhận diện thương hiệu Paper Haven (bảng màu xanh rừng `#193E30`, kem `#F8F3EB`, chữ Cormorant Garamond / Inter), danh mục 28 sách công khai, tìm kiếm không dấu, bộ lọc thể loại và phân trang mượt mà.
2. **1:30–3:00 — Xác thực & Độc giả:** minh họa đăng ký tài khoản Độc giả tự động cấp số thẻ thư viện. Đăng nhập phân quyền chính xác: Độc giả vào `/account`, Thủ thư/Admin vào `/admin`.
3. **3:00–5:00 — Tủ sách cá nhân & Đánh giá sách (Phase 3):**
   - Vào chi tiết sách (`/book/1`), bấm nút "Lưu vào yêu thích", chấm sao (1–5 sao) và viết nhận xét.
   - Mở "Tủ sách của tôi" (`/account/bookshelf`) xem danh mục sách đã lưu yêu thích, sách đang mượn, sách đặt trước và lịch sử đã đọc.
   - Phía quản trị: mở `/admin/reviews` để duyệt hoặc ẩn nhận xét của bạn đọc.
4. **5:00–7:30 — In tem nhãn & Kiểm kê thư viện theo đợt (Phase 4):**
   - Vào trang bản sao sách, bấm "In tem nhãn gáy sách" (`/admin/books/1/labels`): hiển thị toàn bộ tem nhãn gồm mã vạch chuẩn Code 128 vector SVG và mã QR 2D SVG sắc nét sẵn sàng in ấn nhiệt.
   - Vào "Kiểm kê thư viện" (`/admin/inventory/audits`): tạo đợt kiểm kê mới theo khu vực hoặc toàn bộ. Quét barcode/mã bản sao thực tế, hệ thống tự động kiểm tra đối chiếu vị trí thực tế với vị trí kệ trên hệ thống (khớp/lệch vị trí). Chốt hoàn tất đợt và xuất biên bản đối soát CSV.
5. **7:30–10:00 — Mượn trả, Gia hạn & Phí phạt:**
   - Lập phiếu mượn nhiều bản sao, kiểm tra điều kiện độc giả. Trả một phần, gia hạn bản còn lại, rồi trả toàn bộ.
   - Thu phí quá hạn hoặc đền bù, xuất biên lai thu tiền PDF tiếng Việt chuẩn nhận diện Paper Haven (`ReceiptController`).
6. **10:00–12:00 — Trợ lý Paper Haven (Chatbot):**
   - Mở widget "Trợ lý Paper Haven", đặt câu hỏi tra cứu sách thông minh, hỏi tình trạng còn sách, kiểm tra sách đang mượn dựa trên dữ liệu MySQL thực tế.
7. **12:00–13:30 — Báo cáo & An toàn dữ liệu:**
   - Xem thống kê KPI thời gian thực, xuất 5 loại báo cáo CSV.
   - Giới thiệu giải pháp sao lưu nhất quán `database/backup.php` sinh mã băm SHA-256 xác thực và khả năng phục hồi nguyên vẹn cấu trúc và dữ liệu.
8. **13:30–15:00 — Kết luận & Bằng chứng kiểm thử:**
   - Mở `FINAL_TEST_REPORT.md`: 153/153 PHP files lint PASS (0 lỗi), 17 assertions Feature test Phase 3 & 4 PASS, 29 bảng MySQL 44 khóa ngoại với 0 lỗi bất biến toàn vẹn dữ liệu.

> [!NOTE]
> Không dùng database chính cho các thao tác phá hoại dữ liệu trong buổi demo. Mọi quy trình mượn/trả/kiểm kê tuân thủ chuẩn an toàn và ghi vết nhật ký kiểm toán minh bạch.
