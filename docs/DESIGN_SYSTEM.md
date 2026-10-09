# PAPER & PAGE — Hệ thống thiết kế

## Tinh thần

Hiệu sách cổ điển và thư viện biên tập đương đại: giấy ấm, xanh rừng sâu, chữ serif giàu cá tính, nhịp đọc thoáng và trang trí tiết chế. Không dùng xanh SaaS/pastel làm màu thương hiệu.

## Bảng màu

| Token | Giá trị | Công dụng |
|---|---|---|
| `--forest` | `#193E30` | Nút chính, logo, liên kết quan trọng |
| `--forest-deep` | `#112E23` | Sidebar, vùng hero tối |
| `--paper` | `#F8F3EB` | Nền trang giấy |
| `--paper-light` | `#FFFCF7` | Card, biểu mẫu, vùng đọc |
| `--beige` | `#E8D8C5` | Viền và vùng phụ |
| `--gold` | `#C58B54` | Nhấn, trạng thái chọn, chi tiết nhỏ |
| `--ink` | `#302B25` | Nội dung chính |
| `--muted` | `#786F63` | Nội dung phụ |
| `--line` | `#E5DCCE` | Viền mảnh, phân tách |
| `--danger` | `#A94335` | Lỗi và quá hạn |
| `--success` | `#396D4E` | Thành công / sẵn sàng |

Màu được khai báo một lần trong `public/assets/css/app.css` và dùng qua CSS variables. Màu trạng thái nghiệp vụ là ngoại lệ có giới hạn để duy trì khả năng nhận biết.

## Typography

- Tiêu đề: Playfair Display; fallback Georgia, serif.
- Nội dung và biểu mẫu: Inter; fallback Lato, Segoe UI, sans-serif.
- Nhãn biên tập: Cormorant Garamond khi cần nhấn trang trí.
- Google Fonts được nạp có fallback cục bộ; trang vẫn hiển thị rõ khi ngoại tuyến.

## Bố cục và thành phần

- Thanh điều hướng công khai có nền giấy sáng, thanh quản trị cố định gọn; main dùng grid `minmax(0, 1fr)` để không ép tràn ngang.
- Lưới sách co giãn theo số cột, ảnh bìa có tỷ lệ ổn định và bìa dự phòng khi thiếu ảnh.
- Card nền giấy sáng, bo góc 8–14 px, viền beige và bóng rất nhẹ.
- Nút chính xanh rừng; hover đổi sắc nhẹ. Nút xóa/đánh dấu quá hạn dùng màu cảnh báo.
- Bảng quản trị cuộn ngang trong viewport cục bộ; trang không bị tràn ngang.
- Form có nhãn hiển thị, thông báo validation, token CSRF và trạng thái thông báo.
- Empty state dùng lời nhắc rõ ràng; lỗi server được báo bằng flash message; truy vấn không hiển thị dữ liệu giả khi lỗi.

## Responsive

- Desktop từ 1200 px: quản trị sidebar + nội dung, dashboard có cột phụ.
- Tablet 768–1024 px: cột phụ chuyển xuống dưới, menu thu gọn.
- Điện thoại 390 px: một cột, menu di động, thao tác chạm đủ rộng.
- Breakpoint mục tiêu gồm 1440, 1366, 1024, 768 và 390 px; tránh width cố định cho khung chính.

## Ảnh

- Ảnh bìa dùng endpoint ảnh công khai của Open Library khi sách có ISBN.
- Hero dùng ảnh Unsplash Source theo URL ảnh cụ thể; thay ảnh hoặc tắt ảnh từ xa không ảnh hưởng nội dung.
- Khi bìa thất bại, view tạo bìa màu giấy/xanh rừng theo thể loại, không phụ thuộc dịch vụ ảnh.
- Không nhúng hoặc tái tạo thương hiệu/bìa sách từ ảnh tham chiếu.

## Triển khai trong ứng dụng PHP

- Token màu, typography, nút, thẻ sách, biểu mẫu, bảng, menu responsive, modal và trạng thái nằm trong `public/assets/css/app.css`.
- `layouts/public.php` và `layouts/app.php` dùng chung font, thông báo, thương hiệu và bộ CSS; các trang riêng chỉ khai báo nội dung.
- `public/assets/js/app.js` xử lý menu di động, modal thêm/sửa, xác nhận thao tác có tác động, hiện/ẩn mật khẩu, trạng thái gửi form và fallback bìa.
- Các ảnh ngoài là tùy chọn: nội dung mô tả và fallback CSS vẫn dùng được nếu dịch vụ ảnh hoặc mạng ngoài không khả dụng.
- Nội dung tài khoản, tìm kiếm, thống kê và bảng quản lý được render từ PHP/MySQL; thiết kế không dựa vào dữ liệu localStorage.
