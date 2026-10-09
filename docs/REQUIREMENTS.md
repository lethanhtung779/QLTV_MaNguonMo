# Yêu cầu và trạng thái cuối

Bản PHP server-rendered/MySQL, không làm lại frontend hay dùng localStorage cho nghiệp vụ. Giữ Forest Green #193E30, Paper Cream #F8F3EB, Beige #E8D8C5, Gold #C58B54, Ink #302B25, serif headings/Inter body.

Đã có và kiểm chứng: public/auth/ba vai trò; CRUD sách/độc giả/thể loại/tác giả/NXB; copy riêng/mã/barcode/kệ/tình trạng; catalog sort/filter/pagination và reader pagination; mượn nhiều copy/trả phần/gia hạn/FIFO/expiry; ledger phí/thu một phần/miễn giảm; report CSV mượn/kho/phí; thông báo sắp/quá hạn/reservation, policy version và hồ sơ/mật khẩu. Runtime PHP8.3.33/MySQL8.4.3, source migrations 001–006; kiểm thử DB ghi trên clone riêng.

P0/P1 đã xử lý theo FINAL_GAP_ANALYSIS.md. Responsive kiểm tra 22 màn/route ở 5 viewport và native modal/sidebar/form. Báo cáo nghiệm thu TEST_REPORT.md ghi PASS/FAIL/BLOCKED thực tế; Git diff BLOCKED do không có .git.

Ngoài phạm vi hiện tại/chưa có: PDF, upload ảnh, REST API, permission matrix cấu hình/UI nhân viên, pipeline CI. Không chứng nhận security audit/production/TLS/load/backup restore hoặc mọi khác biệt MariaDB.
