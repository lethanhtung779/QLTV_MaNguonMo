# Routes và giao diện HTTP

Ứng dụng hiện là PHP server-rendered, không cung cấp REST/JSON API. Các form thay đổi dữ liệu dùng `POST`, session cookie và CSRF token; truy cập trái role trả lỗi 403. Nguồn route chuẩn là `app/Core/Router.php`.

## Public và xác thực

| Method | Path | Mục đích |
|---|---|---|
| GET | `/` | Trang chủ |
| GET | `/catalog` | Danh mục, tìm kiếm và lọc |
| GET | `/book/{id}` | Chi tiết sách |
| GET, POST | `/login` | Form và đăng nhập |
| POST | `/logout` | Đăng xuất |
| GET, POST | `/register` | Đăng ký độc giả khi cấu hình cho phép |

## Cổng độc giả

| Method | Path | Mục đích |
|---|---|---|
| GET | `/account`, `/account/loans` | Dashboard, phiếu và lịch sử của tài khoản hiện tại |
| GET, POST | `/account/profile` | Xem/cập nhật hồ sơ |
| POST | `/account/password` | Đổi mật khẩu |
| POST | `/account/reservations` | Đặt trước |
| POST | `/account/reservations/cancel` | Hủy đặt trước của chính độc giả |
| POST | `/account/loans/renew` | Yêu cầu gia hạn |
| GET | `/notifications` | Xem thông báo |
| POST | `/notifications/read` | Đánh dấu đã đọc |

## Quản trị/thủ thư

| Method | Path | Mục đích |
|---|---|---|
| GET | `/admin` | Dashboard |
| GET, POST | `/admin/books` | Danh sách/lưu sách |
| POST | `/admin/books/delete` | Ngừng hoặc xóa theo quy tắc lịch sử |
| GET, POST | `/admin/categories` | Danh sách/lưu thể loại |
| POST | `/admin/categories/delete` | Xử lý xóa/ngừng thể loại |
| GET, POST | `/admin/readers` | Danh sách/lưu độc giả |
| POST | `/admin/readers/delete` | Xử lý hồ sơ độc giả |
| GET | `/admin/loans` | Sổ mượn và chi tiết |
| POST | `/admin/loans` | Lập phiếu |
| POST | `/admin/loans/return` | Nhận trả |
| POST | `/admin/loans/renew` | Gia hạn |
| POST | `/admin/reservations` | Xử lý reservation |
| GET | `/admin/fines` | Sổ phí |
| POST | `/admin/fines/payments` | Thu tiền |
| POST | `/admin/fines/waive` | Miễn/giảm, admin-only |
| GET | `/admin/statistics` | Báo cáo |
| GET | `/admin/statistics/export` | Xuất báo cáo CSV |
| GET, POST | `/admin/settings` | Xem/lưu cài đặt, admin-only |

## Input, validation và lỗi

- Các form cần token CSRF do view/helper hiện hành phát sinh; không gửi POST thủ công thiếu token.
- ID route sách phải là số nguyên dương; input nghiệp vụ được kiểm tra server-side.
- Response thành công thường dùng redirect/flash; không có JSON response contract.
- 403 là lỗi phân quyền; 404 là route/tài nguyên không tồn tại; lỗi validation được phản hồi trên giao diện.
- Export CSV bị giới hạn theo service và yêu cầu quyền staff.

Không dùng route này làm API tích hợp bên thứ ba; nếu cần API versioned/authentication contract phải thiết kế riêng.

## Routes bổ sung 08/10/2026

| Method | Path | Quyền / chức năng |
|---|---|---|
| GET, POST | /admin/books/{id}/copies | admin/librarian; kho từng bản |
| GET, POST | /admin/authors, /admin/publishers | admin/librarian; metadata |
| POST | /admin/authors/delete, /admin/publishers/delete | admin/librarian; chỉ mục chưa dùng |
| GET | /admin/readers/{id} | admin/librarian; lịch sử/phí |
| GET, POST | /profile | admin/librarian; hồ sơ |
| POST | /profile/password | admin/librarian; đổi mật khẩu |

/catalog và /admin/books nhận page/sort=newest,title,author; /admin/readers nhận page/state=active,inactive. CSV kind=inventory,fines xuất toàn kho/ledger; không áp dụng bộ lọc ngày của CSV lượt mượn. POST save reader nhận is_active=0/1; thao tác ngừng phải qua validation trạng thái sách/đặt trước/nợ.
