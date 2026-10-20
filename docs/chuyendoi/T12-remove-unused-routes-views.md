# Task T12: Xóa bỏ route/view không dùng đến

**Mục tiêu:** Dọn dẹp code, xóa các route, controller, view không còn cần thiết cho hệ thống bán sách.

## Các thành phần cần xóa

### Controllers (xóa hoàn toàn)

| File | Lý do |
|------|-------|
| `ChatController.php` | Loại bỏ chat AI |
| `InventoryAuditController.php` | Không cần kiểm kê |
| `MetadataQualityController.php` | Không cần |
| `FineController.php` | Thay bằng `PaymentController` |
| `LoanController.php` | Thay bằng `OrderController` |
| `ReaderController.php` | Thay bằng `CustomerController` |

### Views (xóa hoặc ẩn)

| Thư mục/File | Lý do |
|--------------|-------|
| `admin/loans.php` | Không còn mượn/trả |
| `admin/fines.php` | Không còn phí phạt |
| `admin/inventory_audits.php` | Không cần kiểm kê |
| `admin/inventory_audit_detail.php` | Không cần kiểm kê |
| `admin/metadata_quality.php` | Không cần |
| `admin/print_labels.php` | Không cần |
| `reader/` | Thay bằng `customer/` |
| `public/book.php` (phần đánh giá) | Có thể giữ nhưng đơn giản hóa |

### Routes (xóa khỏi Router.php)

- `/admin/loans/*`
- `/admin/fines/*`
- `/admin/reservations`
- `/admin/inventory/*`
- `/chat/*`
- `/admin/metadata-quality/*`

## Definition of Done

- [ ] Xóa controller không dùng
- [ ] Xóa view không dùng
- [ ] Loại bỏ route khỏi `Router.php`
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
