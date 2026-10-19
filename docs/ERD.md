# ERD hiện tại

```mermaid
erDiagram
    USERS ||--o| READERS : "tài khoản độc giả"
    USERS o|--o{ LOANS : "nhân viên lập"
    CATEGORIES o|--o{ BOOKS : phân_loại
    BOOKS ||--o{ BOOK_AUTHORS : có_tác_giả
    AUTHORS ||--o{ BOOK_AUTHORS : được_gán
    PUBLISHERS o|--o{ BOOKS : xuất_bản
    BOOKS ||--|{ BOOK_COPIES : có_bản_sao
    READERS ||--o{ LOANS : mượn
    BOOKS ||--o{ LOANS : "legacy reference"
    LOANS ||--|{ LOAN_ITEMS : gồm
    BOOK_COPIES ||--o{ LOAN_ITEMS : "lịch sử mượn"
    LOAN_ITEMS ||--o{ LOAN_RENEWALS : gia_hạn
    LIBRARY_POLICY_VERSIONS ||--o{ LOAN_RENEWALS : "chính sách áp dụng"
    READERS ||--o{ RESERVATIONS : đặt
    BOOKS ||--o{ RESERVATIONS : được_đặt
    BOOK_COPIES o|--o{ RESERVATIONS : giữ
    USERS o|--o{ RESERVATIONS : xử_lý
    USERS o|--o{ ACTIVITY_LOGS : thực_hiện
    USERS ||--o{ NOTIFICATIONS : nhận
    READERS ||--o{ FINE_CHARGES : phát_sinh
    LOAN_ITEMS ||--o{ FINE_CHARGES : bị_tính
    USERS o|--o{ FINE_CHARGES : xác_nhận
    READERS ||--o{ FINE_PAYMENTS : thanh_toán
    USERS o|--o{ FINE_PAYMENTS : tiếp_nhận
    FINE_PAYMENTS ||--|{ FINE_PAYMENT_ALLOCATIONS : phân_bổ
    FINE_CHARGES ||--o{ FINE_PAYMENT_ALLOCATIONS : được_thanh_toán
    FINE_CHARGES ||--o{ FINE_WAIVERS : miễn_giảm
    USERS ||--o{ FINE_WAIVERS : duyệt
    USERS o|--o{ SYSTEM_SETTINGS : cập_nhật
    USERS o|--o{ LIBRARY_POLICY_VERSIONS : ban_hành

    USERS {
        bigint id PK
        varchar email UK
        varchar password_hash
        enum role
        tinyint is_active
    }
    CATEGORIES {
        bigint id PK
        varchar name UK
    }
    BOOKS {
        bigint id PK
        varchar book_code UK
        varchar isbn UK
        bigint category_id FK
        bigint publisher_id FK
        int total_copies
        tinyint is_active
    }
    AUTHORS {
        bigint id PK
        varchar name UK
    }
    PUBLISHERS {
        bigint id PK
        varchar name UK
    }
    BOOK_AUTHORS {
        bigint book_id PK_FK
        bigint author_id PK_FK
    }
    READERS {
        bigint id PK
        bigint user_id FK_UK
        varchar card_number UK
        varchar full_name
        date expires_on
    }
    BOOK_COPIES {
        bigint id PK
        bigint book_id FK
        varchar copy_code UK
        varchar barcode UK
        enum item_condition
        enum status
        varchar shelf_location
        date acquired_at
    }
    LOANS {
        bigint id PK
        varchar loan_code UK
        bigint reader_id FK
        bigint book_id FK_legacy
        bigint issued_by FK
        date borrowed_at
        date due_at
        date returned_at
    }
    LOAN_ITEMS {
        bigint id PK
        bigint loan_id FK
        bigint book_copy_id FK
        date borrowed_at
        date due_date
        date returned_at
        decimal daily_fine
        bigint active_copy_id UK_generated
    }
    LOAN_RENEWALS {
        bigint id PK
        bigint loan_item_id FK
        smallint renewal_number
        bigint policy_version FK
        smallint renewal_days
        date previous_due_date
        date new_due_date
        bigint renewed_by FK
    }
    RESERVATIONS {
        bigint id PK
        bigint reader_id FK
        bigint book_id FK
        bigint book_copy_id FK
        enum status
        datetime expires_at
    }
    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        varchar title
        varchar message
        varchar deduplication_key UK
        datetime read_at
    }
    SYSTEM_SETTINGS {
        varchar setting_key PK
        varchar setting_value
        bigint updated_by FK
    }
    LIBRARY_POLICY_VERSIONS {
        bigint id PK
        smallint max_books_per_reader
        smallint default_loan_days
        smallint max_renewals
        smallint renewal_days
        decimal fine_per_overdue_day
        smallint reservation_expiry_days
        bigint updated_by FK
    }
    FINE_CHARGES {
        bigint id PK
        bigint loan_item_id FK
        bigint reader_id FK
        enum charge_type
        decimal amount
        varchar deduplication_key UK
        bigint assessed_by FK
    }
    FINE_PAYMENTS {
        bigint id PK
        bigint reader_id FK
        decimal amount
        enum method
        varchar reference_code
        bigint received_by FK
    }
    FINE_PAYMENT_ALLOCATIONS {
        bigint payment_id PK_FK
        bigint fine_charge_id PK_FK
        decimal amount
    }
    FINE_WAIVERS {
        bigint id PK
        bigint fine_charge_id FK
        decimal amount
        varchar reason
        bigint waived_by FK
    }
    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        varchar action
        varchar subject_type
        bigint subject_id
    }
```

ERD thể hiện schema sau migration `006`; `books.author` và `books.publisher` vẫn được giữ làm trường legacy trong giai đoạn tương thích.

## Migrations 007–008

~~~mermaid
erDiagram
    permissions ||--o{ role_permissions : default_grants
    permissions ||--o{ user_permissions : overrides
    users ||--o{ user_permissions : grants
    users ||--o{ readers : owns
    loans ||--|{ loan_items : includes
~~~

permissions.code → role_permissions.permission_code và user_permissions.permission_code; users.id → user_permissions.user_id. loan_items.return_condition lưu good/worn/damaged/lost; lịch sử cũ NULL. Sau migration: 24 bảng, 34 khóa ngoại.
