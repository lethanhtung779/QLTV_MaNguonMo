# ĐỒ ÁN MÔN HỌC: LẬP TRÌNH MÃ NGUỒN MỞ
## PHẦN 10: ĐÁNH GIÁ ĐỒ ÁN, HẠN CHẾ & HƯỚNG PHÁT TRIỂN TIẾP THEO

---

## 1. ĐÁNH GIÁ KẾT QUẢ ĐẠT ĐƯỢC CỦA ĐỒ ÁN

Trải qua quá trình nghiên cứu, thiết kế và phát triển nghiêm túc, đồ án **Paper Haven – Hệ thống Quản lý Thư viện Trực tuyến** đã hoàn thành xuất sắc toàn bộ các mục tiêu đặt ra cho môn học Lập trình Mã nguồn mở:

### 1.1. Về mặt kiến trúc và kỹ thuật lập trình
* **Nắm vững bản chất kiến trúc MVC OOP:** Toàn bộ hệ thống được xây dựng bằng PHP 8.3 thuần theo mô hình MVC phân tầng rõ rệt (Core, Controllers, Services, Repositories, Views) mà không phụ thuộc vào các framework đóng sẵn (như Laravel hay Symfony). Điều này chứng minh năng lực hiểu sâu về luồng xử lý HTTP, nạp tự động PSR-4, điều hướng Router, Dependency Injection cơ bản và quản lý vòng đời ứng dụng web.
* **Chuẩn mực mã nguồn và kiểm thử tự động:** Toàn bộ 170 tệp mã nguồn tuân thủ nghiêm ngặt quy tắc `strict_types=1`, xử lý ngoại lệ bài bản và đạt kết quả kiểm thử tự động 100% trên 93 tiêu chí kỹ thuật.
* **Cơ sở dữ liệu quan hệ mạnh mẽ:** Thiết kế CSDL 29 bảng chuẩn hóa 3NF, bảo đảm tính toàn vẹn tham chiếu 100% thông qua Foreign Keys, hỗ trợ Transaction ACID cho các giao dịch nhạy cảm (mượn sách, trả sách, thu tiền phạt, kiểm kê kho).

### 1.2. Về mặt giá trị nghiệp vụ thực tiễn
* Giải quyết triệt để bài toán **Quản lý tồn kho theo bản sao vật lý (Copy-level Inventory)**, khắc phục tình trạng sai lệch giữa sách trên hệ thống và sách thực tế trên giá.
* Tích hợp đầy đủ các quy trình nghiệp vụ chuyên sâu: Tính phạt lũy tiến hàng ngày, phân bổ thanh toán FIFO, miễn giảm phạt có thẩm quyền, giữ chỗ đặt trước tự động khi có sách trả về, quét mã vạch Barcode Code 128 và mã phản hồi nhanh QR Code định dạng SVG độc lập.
* **Đạo đức dữ liệu khoa học:** Tuân thủ triệt để nguyên tắc **Real Books Only**, loại bỏ hoàn toàn các bìa sách do AI tự vẽ giả mạo và các chuỗi ISBN ảo, bảo vệ tính trung thực và chuẩn tắc của thư viện học.

---

## 2. NHỮNG HẠN CHẾ CÒN TỒN TẠI TRONG PHẠM VI MÔN HỌC

Mặc dù hệ thống đã vận hành hoàn chỉnh và đáp ứng tốt yêu cầu đồ án, nhóm thực hiện thẳng thắn nhìn nhận một số hạn chế khách quan:

1. **Vấn đề bản quyền hình ảnh bìa sách:**
   - Trong số 129 đầu sách thật, hiện mới chỉ có 1 đầu sách (B301 "Lược sử thời gian" - NXB Trẻ) được đối soát và tích hợp ảnh bìa chính thức từ kho đối soát. 128 đầu sách còn lại hiện đang sử dụng **Trình giữ chỗ trung tính (SVG Book Placeholder)** do nhóm chưa có quyền sở hữu hợp pháp hình ảnh bìa từ các nhà xuất bản.
2. **Cơ chế gửi thông báo thời gian thực:**
   - Hệ thống thông báo hiện tại được lưu trữ trong CSDL và hiển thị trên giao diện người dùng (In-app Notification). Do chưa cấu hình máy chủ hàng đợi nền (Message Queue như Redis/RabbitMQ), việc gửi email tự động qua giao thức SMTP đến hàng ngàn độc giả chưa được kích hoạt để tránh làm nghẽn luồng xử lý web.
3. **Mô hình Trợ lý ảo khi chạy Offline:**
   - Bộ máy Chatbot khi chạy ở chế độ cục bộ (Offline) phụ thuộc vào bộ quy tắc khớp mẫu Regex. Mặc dù tốc độ phản hồi cực nhanh (<2ms) và độ ổn định cao, khả năng hiểu ngôn ngữ tự nhiên còn giới hạn so với việc kết nối trực tiếp đến các mô hình ngôn ngữ lớn (LLM API) bên ngoài.

---

## 3. HƯỚNG PHÁT TRIỂN VÀ NÂNG CẤP TIẾP THEO

Để đưa Paper Haven từ một sản phẩm đồ án học phần trở thành một giải pháp phần mềm quản lý thư viện sẵn sàng triển khai thực tế cho các trường đại học hoặc tổ chức giáo dục, lộ trình phát triển tiếp theo được định hướng như sau:

### 3.1. Nâng cấp kiến trúc sang Framework Hiện đại (Laravel 11+)
* Chuyển đổi tầng ứng dụng sang nền tảng **Laravel 11+** kết hợp kiến trúc Clean Architecture / Domain-Driven Design (DDD).
* Tận dụng Eloquent ORM, Database Seeder mạnh mẽ, Laravel Queue / Horizon để xử lý tác vụ nền không đồng bộ (gửi email, tính toán số liệu thống kê định kỳ ban đêm).
* Xây dựng giao diện Single-Page Application (SPA) hoặc sử dụng Livewire / Vue.js để tăng cường trải nghiệm người dùng không cần tải lại trang.

### 3.2. Mở rộng tính năng và Tích hợp phần cứng
1. **Tích hợp Cổng thanh toán trực tuyến:**
   - Kết nối với cổng thanh toán VNPAY, MoMo hoặc VietQR tự động sinh mã thanh toán tiền phạt trực tuyến, giúp độc giả có thể nộp tiền phạt ngay trên điện thoại di động mà không cần đến quầy thủ thư.
2. **Ứng dụng công nghệ nhận diện sóng vô tuyến (RFID):**
   - Nâng cấp từ mã vạch quang học sang gắn chip RFID trên từng cuốn sách, kết hợp với cổng an ninh thư viện để tự động phát hiện sách bị mang ra ngoài trái phép và cho phép độc giả tự làm thủ tục mượn/trả sách tự động (Self-Check Machine).
3. **Chuẩn hóa giao thức liên thư viện quốc tế (Z39.50 & MARC 21):**
   - Hỗ trợ nhập và xuất dữ liệu biên mục theo định dạng tiêu chuẩn quốc tế **MARC 21**, kết nối máy chủ **Z39.50** để tự động kéo thông tin sách từ Thư viện Quốc gia Việt Nam hoặc Thư viện Quốc hội Hoa Kỳ (Library of Congress) chỉ bằng thao tác quét mã ISBN.
4. **Tích hợp Trí tuệ nhân tạo RAG (Retrieval-Augmented Generation):**
   - Huấn luyện trợ lý ảo thư viện dựa trên nội dung tóm tắt và mục lục của toàn bộ kho sách, cho phép bạn đọc hỏi đáp chuyên sâu: *"Hãy tìm cho tôi 3 cuốn sách nói về thuật toán cân bằng cây nhị phân và so sánh cách tiếp cận của từng tác giả"*.

---

## 4. LỜI KẾT

Dự án **Paper Haven – Hệ thống Quản lý Thư viện Trực tuyến** là kết quả của sự đầu tư công phu, nghiêm túc từ khâu khảo sát nghiệp vụ, thiết kế kiến trúc, chuẩn hóa cơ sở dữ liệu cho đến lập trình và kiểm thử tự động. Đồ án không chỉ khẳng định tính khả thi của việc ứng dụng công nghệ mã nguồn mở trong quản lý thông tin mà còn thể hiện tinh thần trách nhiệm, đạo đức dữ liệu khoa học và tư duy kiến trúc phần mềm chuyên nghiệp.
