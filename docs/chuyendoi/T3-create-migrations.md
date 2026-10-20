# Task T3: Tạo migration bảng `orders`, `order_items`, `payments`, `customers`

**Mục tiêu:** Tạo các bảng dữ liệu mới cho hệ thống bán sách, thay thế các bảng liên quan đến mượn/trả.

## Các thay đổi chi tiết

### 1. Bảng `customers` (thay thế `readers`)

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(32) NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### 2. Bảng `orders`

```sql
CREATE TABLE orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cod','bank_transfer') NOT NULL DEFAULT 'cod',
    payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created (created_at)
);
```

### 3. Bảng `order_items`

```sql
CREATE TABLE order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    book_id BIGINT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_book (book_id)
);
```

### 4. Bảng `payments`

```sql
CREATE TABLE payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) UNSIGNED NOT NULL,
    method ENUM('cod','bank_transfer') NOT NULL,
    reference_code VARCHAR(120) NULL,
    note VARCHAR(300) NULL,
    received_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_created (created_at)
);
```

## File migration cần tạo

Trong thư mục `database/migrations/`:

| File | Mô tả |
|------|-------|
| `018_create_customers_table.php` | Tạo bảng customers |
| `019_create_orders_table.php` | Tạo bảng orders |
| `020_create_order_items_table.php` | Tạo bảng order_items |
| `021_create_payments_table.php` | Tạo bảng payments |

## Definition of Done

- [ ] Tất cả 4 file migration được tạo đúng cú pháp
- [ ] Bảng `orders` có `id` kiểu INT auto-increment PRIMARY KEY
- [ ] Foreign keys đúng cách liên kết các bảng
- [ ] Index cho các cột thường dùng truy vấn
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
