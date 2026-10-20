# Kế hoạch chuyển đổi nghiệp vụ: QLTV → Hệ thống bán sách

**Branch:** `feature/convert-to-bookstore`  
**Nguồn:** `minh` (origin/minh)  
**Ngày tạo:** 2026-10-21  
**Người thực hiện:** AI Coding Agent (Cline)

---

## 1. Mục tiêu

Chuyển đổi hệ thống quản lý thư viện (QLTV) hiện tại thành hệ thống **bán sách** đơn giản, tập trung vào luồng:  
**Đăng nhập → Xem sách → Thêm vào giỏ → Đặt hàng → Thanh toán → Giao hàng → Thông báo qua email**

---

## 2. Vai trò người dùng

| Vai trò | Mô tả | Quyền hạn chính |
|---------|-------|-----------------|
| `admin` | Quản trị hệ thống | Quản lý sách, nhân viên, khách hàng, đơn hàng, cài đặt, báo cáo doanh thu |
| `nhan_vien` | Nhân viên bán hàng | Xử lý đơn hàng, cập nhật trạng thái giao, quản lý tồn kho cơ bản, xem khách hàng |
| `nguoi_mua` | Khách hàng | Đăng nhập, duyệt sách, thêm vào giỏ, đặt hàng, thanh toán, theo dõi đơn hàng của mình |

---

## 3. Yếu cầu nghiệp vụ (đã xác nhận)

| # | Yếu cầu | Chi tiết |
|---|---------|----------|
| 1 | **Bắt buộc đăng nhập** | Khách hàng phải đăng nhập vai trò `nguoi_mua` mới được dùng giỏ hàng và đặt hàng |
| 2 | **Trừ tồn kho khi đặt hàng** | Khi đặt hàng thành công → tự động trừ `stock` của sách. Nếu đơn bị hủy hoặc giao không thành công → tự động hoàn lại `stock` |
| 3 | **Mã đơn hàng** | `id` kiểu `INT`, auto-increment, có index chính (PRIMARY KEY) |
| 4 | **Thông báo + Email** | Có chức năng thông báo trong hệ thống và kết hợp gửi email cho khách hàng khi trạng thái đơn hàng thay đổi |

---

## 4. Thay đổi kỹ thuật chi tiết

### 4.1 Core – Permissions & Router

**`app/Core/Permissions.php`**
- Cập nhật `LABELS` thành quyền bán sách:
  - `books.view`, `books.create`, `books.update`, `books.deactivate`
  - `orders.view`, `orders.create`, `orders.update`, `orders.cancel`
  - `customers.view`, `customers.create`, `customers.update`
  - `payments.view`, `payments.collect`
  - `reports.view`
  - `users.manage`, `roles.manage`, `settings.manage`, `audit.view`
- `ADMIN_ONLY`: `users.manage`, `roles.manage`, `settings.manage`, `audit.view`
- Hàm `route()` mapping các route `/admin/*` sang quyền mới.

**`app/Core/Router.php`**
- Loại bỏ route liên quan đến mượn/trả: `/admin/loans`, `/admin/fines`, `/admin/reservations`, `/admin/inventory/*`, `/chat/*`, v.v.
- Thêm route bán sách: `/admin/orders`, `/admin/payments`, `/admin/customers`, `/admin/books`, `/cart`, `/checkout`, `/order/success`, v.v.
- Route public: `/`, `/catalog`, `/book/{id}`, `/cart`, `/checkout`, `/login`, `/register`.
### 4.2 Controllers

| Controller hiện tại | Hành động | Controller mới / Ghi chú |
|---------------------|-----------|--------------------------|
| `AuthController` | Sửa | Đăng nhập 3 vai trò; đăng ký tài khoản `nguoi_mua` |
| `CatalogController` | Giữ nguyên | Xem danh mục, chi tiết sách (public) |
| `LoanController` | Thay bằng | `OrderController` – quản lý đơn hàng |
| `ReaderController` | Thay bằng | `CustomerController` – tài khoản khách hàng |
| `StaffController` | Giữ nguyên | Quản lý nhân viên (`admin`, `nhan_vien`) |
| `DashboardController` | Sửa | Thống kê doanh thu, đơn hàng |
| `FineController` | Thay bằng | `PaymentController` – thanh toán đơn hàng |
| `InventoryAuditController` | Xóa | Không cần kiểm kê phức tạp |
| `BookImportController` | Giữ nguyên | Nhập khẩu sách (có giá, tồn kho) |
| `CoverController` | Giữ nguyên | Upload bìa sách |
| `ReviewController` | Giữ nguyên | Đánh giá sản phẩm |
| `ChatController` | Xóa | Loại bỏ chat AI |
| `MetadataQualityController` | Xóa | Không cần |
| `NotificationController` | Giữ nguyên | Thông báo đơn hàng + gửi email |
| `ReceiptController` | Giữ nguyên | Hóa đơn / biên nhận thanh toán |
| `ManagementController` | Giữ nguyên | Cài đặt hệ thống |

### 4.3 Repositories & Services

**Mới:**
- `OrderRepository` – truy vấn đơn hàng
- `OrderItemRepository` – chi tiết đơn hàng
- `CartRepository` – quản lý giỏ hàng (session-based, sau khi đăng nhập)
- `PaymentRepository` – thanh toán
- `CustomerRepository` – thông tin khách hàng
- `OrderService` – tạo đơn, cập nhật trạng thái, trừ/hoàn lại tồn kho

**Giữ nguyên:**
- `BookRepository`, `CategoryRepository`, `UserRepository`, `ActivityRepository`, `SystemSettingRepository`

**Xóa / không dùng:**
- `LoanRepository`, `ReservationRepository`, `FineRepository`, `ReaderRepository`, `FavoriteRepository` (nếu không cần)

### 4.4 Views

| Thư mục hiện tại | Thư mục mới | Nội dung |
|------------------|-------------|----------|
| `admin/` | `admin/` | dashboard, orders, books, customers, staff, settings, reports, payments |
| `public/` | `public/` | home, catalog, product detail, cart, checkout |
| `reader/` | `customer/` | dashboard (tài khoản), orders (lịch sử đơn), profile |
| `auth/` | `auth/` | login, register (khách hàng) |
| `layouts/`, `partials/` | Giữ nguyên | Layout chung, pagination, book_card |
---

## 5. Cơ sở dữ liệu (Migrations)

### Bảng mới / thay đổi

| Bảng | Mô tả |
|------|-------|
| `users` | Thêm role `nhan_vien`, `nguoi_mua` |
| `customers` (mới, thay `readers`) | Thông tin khách hàng: tên, email, điện thoại, địa chỉ |
| `books` | Thêm cột `price` (giá bán), `stock` (tồn kho) |
| `orders` (mới) | Đơn hàng: `id` INT auto-increment PK, `customer_id`, `total`, `status`, `payment_method`, `created_at` |
| `order_items` (mới) | Chi tiết đơn: `id` INT auto-increment PK, `order_id`, `book_id`, `qty`, `unit_price` |
| `payments` (mới/thay `fine_payments`) | Thanh toán đơn hàng |

### Bảng có thể xóa (nếu không cần)
- `loans`, `loan_items`, `fine_charges`, `fine_payments`, `fine_waivers`
- `reservations`, `favorites` (nếu không giữ yêu thích), `reviews` (nếu không giữ đánh giá)

---

## 6. Luồng nghiệp vụ chi tiết

### 6.1 Khách hàng đặt hàng
1. Đăng nhập với vai trò `nguoi_mua`
2. Xem danh mục sách → chọn sách → thêm vào giỏ hàng (lưu trong session)
3. Vào trang `/cart` xem giỏ hàng
4. Nhấn đặt hàng → `/checkout` chọn hình thức thanh toán (COD hoặc Chuyển khoản)
5. Hệ thống tạo đơn hàng với status `pending`
6. Tự động trừ `stock` của từng sách trong đơn
7. Gửi email thông báo đơn hàng đã đặt thành công
8. Khách hàng có thể theo dõi đơn hàng trong `/customer/orders`

### 6.2 Nhân viên xử lý đơn
1. Xem danh sách đơn hàng tại `/admin/orders`
2. Cập nhật trạng thái: `pending` → `processing` → `shipped` → `delivered` hoặc `cancelled`
3. Nếu `cancelled` → tự động hoàn lại `stock` cho từng sách trong đơn
4. Gửi email thông báo cho khách hàng khi trạng thái thay đổi

### 6.3 Admin quản trị
1. Quản lý sách: thêm/sửa/xóa, cập nhật `price`, `stock`
2. Quản lý nhân viên: tạo tài khoản `nhan_vien`, phân quyền
3. Xem báo cáo doanh thu, thống kê đơn hàng

---

## 7. Danh sách task triển khai (chia nhỏ)

| Task | Mô tả | Ưu tiên |
|------|-------|---------|
| T1 | Cập nhật `app/Core/Permissions.php` – quyền bán sách | Cao |
| T2 | Cập nhật `app/Core/Router.php` – route bán sách | Cao |
| T3 | Tạo migration bảng `orders`, `order_items`, `payments`, `customers` | Cao |
| T4 | Thêm cột `price`, `stock` vào bảng `books` | Cao |
| T5 | Sửa `AuthController` – hỗ trợ 3 vai trò, đăng ký `nguoi_mua` | Cao |
| T6 | Tạo `OrderController`, `OrderRepository`, `OrderService` | Cao |
| T7 | Tạo `CustomerController`, `CustomerRepository` | Trung bình |
| T8 | Tạo `PaymentController`, `PaymentRepository` | Trung bình |
| T9 | Sửa `DashboardController` – thống kê bán hàng | Trung bình |
| T10 | Cập nhật `NotificationController` – thông báo + gửi email | Trung bình |
| T11 | Tạo View `public/cart`, `public/checkout`, `admin/orders`, `customer/orders` | Cao |
| T12 | Xóa bỏ route/view không dùng đến (fines, reservations, inventory, chat) | Thấp |
| T13 | Kiểm thử toàn bộ luồng đăng nhập → giỏ hàng → checkout → đơn hàng | Cao |

---

## 8. Lệnh git khuyên dùng

```bash
# Tạo nhánh tính năng
git checkout -b feature/convert-to-bookstore

# Sau khi hoàn thiện từng task, commit theo định dạng:
git add docs/chuyendoi/ke-hoach-chuyen-doi.md
git commit -m "docs: add conversion plan from QLTV to bookstore system"

# Khi hoàn tất toàn bộ tính năng:
git add .
git commit -m "feat: convert library management system to bookstore system

- Replace loan/reservation/fine modules with order/payment/customer modules
- Add roles: admin, nhan_vien, nguoi_mua
- Add stock deduction/restoration on order place/cancel
- Add notification with email integration
- Remove chat AI, inventory audit, metadata quality modules"

git push origin feature/convert-to-bookstore
```

---

## 9. Ghi chú

- Tất cả commit sẽ được thực hiện trên nhánh `feature/convert-to-bookstore`
- Sau khi review và kiểm thử xong, có thể tạo Pull Request gộp vào `minh` hoặc `main`
- Commit message tuân theo Convention Commits (feat, docs, fix, refactor, etc.)
---