# Task T4: Thêm cột `price`, `stock` vào bảng `books`

**Mục tiêu:** Thêm thông tin giá bán và tồn kho vào bảng sách hiện có.

## Các thay đổi chi tiết

### 1. Thêm cột vào bảng `books`

```sql
ALTER TABLE books
ADD COLUMN price DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0 AFTER publication_year,
ADD COLUMN stock INT UNSIGNED NOT NULL DEFAULT 0 AFTER price;
```

### 2. Cập nhật dữ liệu mẫu (nếu có)

- Các sách hiện có cần được cập nhật giá bán và tồn kho mặc định
- Có thể tạo migration seed dữ liệu mẫu cho testing

## File migration cần tạo

Trong thư mục `database/migrations/`:

| File | Mô tả |
|------|-------|
| `022_add_price_stock_to_books.php` | Thêm cột price, stock vào books |

## Definition of Done

- [ ] Migration file được tạo đúng cú pháp
- [ ] Cột `price` kiểu DECIMAL(12,2) UNSIGNED, default 0
- [ ] Cột `stock` kiểu INT UNSIGNED, default 0
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
