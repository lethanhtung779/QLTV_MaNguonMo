# ĐỒ ÁN MÔN HỌC: LẬP TRÌNH MÃ NGUỒN MỞ
## PHẦN 8: HƯỚNG DẪN SỬ DỤNG CHO 3 ĐỐI TƯỢNG (ADMIN, LIBRARIAN, READER)

---

## 1. HƯỚNG DẪN DÀNH CHO ĐỘC GIẢ (READER USER MANUAL)

### 1.1. Tra cứu và Khám phá mục lục sách (OPAC)
1. **Tìm kiếm sách:** Truy cập trang chủ hoặc menu **"Mục lục sách"** (`/catalog`). Nhập từ khóa (tên sách, tên tác giả hoặc năm xuất bản) vào ô tìm kiếm. Hệ thống sẽ trả về kết quả ngay lập tức.
2. **Bộ lọc đa tiêu chí:** Sử dụng thanh lọc bên trái để lọc sách theo:
   - Thể loại (Công nghệ thông tin, Khoa học, Kinh tế, Văn học,...).
   - Tình trạng sách (Chỉ hiện sách còn bản sao có thể mượn ngay).
   - Sắp xếp theo: Mới nhất, Phổ biến nhất hoặc Đánh giá cao nhất.
3. **Xem chi tiết đầu sách:** Bấm vào thẻ sách để xem trang chi tiết:
   - Thông tin tác giả, nhà xuất bản, năm phát hành, vị trí kệ khuyến nghị.
   - Bảng tình trạng từng bản sao vật lý (Mã bản sao, tình trạng, trạng thái).
   - Khu vực đánh giá của các bạn đọc khác và danh sách sách liên quan được hệ thống gợi ý.

### 1.2. Đặt trước sách (Hàng đợi giữ chỗ FIFO)
* Khi một cuốn sách đang hết bản sao trên giá (`0 bản sao khả dụng`), nút **"Đặt trước sách"** sẽ hiển thị.
* Bấm vào nút này để ghi danh vào hàng đợi. Hệ thống sẽ cấp số thứ tự ưu tiên (Queue Position).
* Khi có độc giả khác trả sách về quầy, hệ thống sẽ tự động gửi thông báo đến tài khoản của bạn. Bạn có 48 giờ để đến thư viện nhận sách.

### 1.3. Quản lý tài khoản cá nhân & Lịch sử mượn trả
* Đăng nhập tài khoản bạn đọc tại `/login`.
* Truy cập trang cá nhân:
   - **Đang mượn:** Xem các cuốn sách đang giữ, ngày mượn, hạn trả dự kiến và cảnh báo sắp đến hạn.
   - **Lịch sử đã trả:** Xem lại toàn bộ các cuốn sách đã từng đọc tại thư viện.
   - **Tài chính & Phí phạt:** Kiểm tra các khoản phí trễ hạn (nếu có), số tiền đã nộp và số dư nợ còn lại.
   - **Tủ sách yêu thích:** Danh sách các cuốn sách bạn đã đánh dấu yêu thích để đọc sau.

### 1.4. Trò chuyện cùng Trợ lý thư viện (AI & Rule-based Chatbot)
* Bấm vào biểu tượng Chatbot hình tròn ở góc dưới bên phải màn hình.
* Đặt các câu hỏi thường gặp:
   - *"Thư viện mở cửa lúc mấy giờ?"*
   - *"Tôi muốn mượn sách Lược sử thời gian thì tìm ở kệ nào?"*
   - *"Quy định mượn sách được tối đa bao nhiêu ngày?"*
* Trợ lý ảo sẽ phản hồi ngay lập tức với thông tin chính xác và gợi ý liên kết đến cuốn sách bạn đang tìm kiếm.

---

## 2. HƯỚNG DẪN DÀNH CHO THỦ THƯ (LIBRARIAN MANUAL)

### 2.1. Nghiệp vụ Lập phiếu mượn sách
1. Truy cập menu **"Lưu thông" -> "Mượn sách"** (`/admin/loans`).
2. Nhập Mã thẻ độc giả (`card_number`) hoặc tìm theo tên bạn đọc. Hệ thống sẽ hiển thị thông tin thẻ, số sách đang mượn và cảnh báo nếu thẻ hết hạn hoặc đang nợ phạt.
3. Nhập Mã bản sao (`copy_code`) hoặc dùng máy quét mã vạch quét trực tiếp trên gáy sách.
4. Bấm **"Thêm vào phiếu mượn"** -> Bấm **"Xác nhận cho mượn"**.
5. Bấm **"In biên nhận mượn"** để xuất biên nhận điện tử trao cho bạn đọc.

### 2.2. Nghiệp vụ Nhận trả sách & Gia hạn
1. Truy cập **"Lưu thông" -> "Trả sách"**.
2. Quét mã bản sao cuốn sách được mang đến quầy.
3. Hệ thống tự động hiển thị thông tin người mượn, số ngày đã mượn.
   - Nếu trả đúng hạn: Bấm **"Xác nhận trả sách"**. Bản sao tự động chuyển về trạng thái `available`.
   - Nếu trễ hạn: Hệ thống hiển thị số ngày trễ và tự động tính số tiền phạt. Sau khi bấm xác nhận, hệ thống tự động sinh khoản nợ phạt vào tài khoản độc giả.
4. **Gia hạn sách:** Nếu độc giả muốn mượn thêm, thủ thư bấm nút **"Gia hạn"** tại dòng mượn tương ứng (tối đa được gia hạn 2 lần nếu sách chưa có ai đặt trước).

### 2.3. Thu tiền phạt & Quyết toán
1. Truy cập **"Tài chính" -> "Quản lý phí phạt"** (`/admin/fines`).
2. Chọn độc giả nộp tiền. Hệ thống liệt kê toàn bộ các khoản phạt quá hạn.
3. Nhập số tiền thu được, chọn phương thức (Tiền mặt hoặc Chuyển khoản) và ghi chú.
4. Bấm **"Lập phiếu thu"**. Hệ thống tự động phân bổ tiền theo nguyên tắc FIFO và xuất biên nhận nộp tiền có mã tra cứu.

### 2.4. Thực hiện Đợt kiểm kê kho sách (Inventory Audit)
1. Truy cập **"Quản lý kho" -> "Kiểm kê kho"** (`/admin/inventory-audits`).
2. Bấm **"Tạo đợt kiểm kê mới"**, nhập tên đợt (ví dụ: *Kiểm kê Quý 4 - Khu vực Kệ A*).
3. Sử dụng đầu đọc mã vạch quét liên tục các cuốn sách trên kệ.
4. Quan sát màn hình đối soát trực tiếp:
   - Sách đúng vị trí hiện màu xanh lá.
   - Sách để sai kệ hiện cảnh báo màu vàng kèm gợi ý vị trí đúng.
5. Sau khi quét hết kệ, bấm **"Kết thúc kiểm kê & Lập báo cáo"**. Báo cáo sẽ thống kê chi tiết các cuốn sách nghi ngờ thất thoát hoặc hư hỏng.

---

## 3. HƯỚNG DẪN DÀNH CHO QUẢN TRỊ VIÊN (ADMIN MANUAL)

### 3.1. Bảng điều khiển tổng hợp (Executive Dashboard)
* Truy cập trang chủ quản trị (`/admin`):
  - Xem các chỉ số KPI: Tổng số sách, tổng bản sao, số sách đang cho mượn, số độc giả tích cực, tổng số tiền phạt đã thu.
  - Biểu đồ biến động mượn trả theo thời gian và danh sách các cuốn sách được mượn nhiều nhất.

### 3.2. Quản lý nhân viên và Ma trận phân quyền (Staff & RBAC)
1. Truy cập **"Quản trị" -> "Tài khoản nhân viên"** (`/admin/users`):
   - Thêm mới thủ thư, khóa/mở khóa tài khoản, cấp lại mật khẩu.
2. Truy cập **"Quản trị" -> "Ma trận phân quyền"** (`/admin/permissions`):
   - Bật/tắt các quyền hành động cụ thể cho từng nhóm vai trò (`Librarian`, `Admin`).
   - Cấp quyền đặc thù cho từng cá nhân (User Overrides) khi được giao nhiệm vụ riêng biệt.

### 3.3. Bảng kiểm soát chất lượng dữ liệu (Metadata Quality Dashboard)
* Truy cập tab **"Chất lượng dữ liệu"** trong bảng điều khiển:
  - Kiểm tra tỷ lệ bao phủ ISBN chuẩn.
  - Rà soát các đầu sách còn thiếu ảnh bìa hoặc thiếu mô tả tóm tắt.
  - Quản lý danh sách các bản sao đang ở trạng thái cách ly (`pending_audit`) trong *"Kho tiếp nhận — Chờ kiểm kê"* trước khi chính thức đưa vào lưu thông.

### 3.4. Nhật ký kiểm toán hệ thống (Activity Logs)
* Truy cập **"Hệ thống" -> "Nhật ký kiểm toán"** (`/admin/activity`):
  - Tra cứu toàn bộ các thao tác nhạy cảm: Ai đã sửa thông tin sách, ai đã duyệt miễn giảm phạt, ai đã thêm tài khoản cán bộ, kèm dấu thời gian và địa chỉ IP thực hiện.
