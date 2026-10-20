# Task T9: Sửa `DashboardController` – thống kê bán hàng

**Mục tiêu:** Cập nhật dashboard admin để hiển thị thống kê bán hàng thay vì thống kê mượn/trả.

## Các thay đổi chi tiết

### 1. Thay thế metrics

| Metric cũ | Metric mới |
|-----------|------------|
| Số lượt mượn | Số đơn hàng |
| Số độc giả | Số khách hàng |
| Số phạt | Doanh thu |
| Sách phổ biến | Sách bán chạy nhất |

### 2. Dữ liệu hiển thị trên dashboard

| Phần | Mô tả |
|------|-------|
| Tổng đơn hàng | Tổng số đơn hàng trong hệ thống |
| Đơn hàng chờ xử lý | Số đơn hàng status `pending` |
| Doanh thu | Tổng tiền các đơn hàng `paid` |
| Đơn hàng gần đây | Danh sách 5-10 đơn hàng mới nhất |
| Sách bán chạy | Top sách bán chạy nhất |

### 3. Route

- `/admin/statistics` → GET → `DashboardController::statistics` (báo cáo chi tiết)
- `/admin` → GET → `DashboardController::index` (tổng quan)

## Definition of Done

- [ ] Cập nhật `DashboardController::index()` với metrics bán hàng
- [ ] Cập nhật `DashboardController::statistics()` với báo cáo doanh thu
- [ ] View `admin/dashboard` hiển thị dữ liệu bán hàng
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
