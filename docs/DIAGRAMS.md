# Sơ đồ hệ thống

Các sơ đồ Mermaid dưới đây mô tả source hiện tại; có thể chỉnh sửa trực tiếp.

## Kiến trúc

```mermaid
flowchart LR
    B[Browser] --> W[Apache / PHP server]
    W --> P[public/index.php]
    P --> R[Router]
    R --> C[Controllers]
    C --> S[Services]
    C --> Q[Repositories]
    S --> Q
    Q --> D[(MySQL)]
    C --> V[PHP Views]
    V --> B
    CLI[setup.php / seed.php] --> D
```

## Hoạt động mượn và trả

```mermaid
flowchart TD
    A[Nhận yêu cầu mượn] --> G{Độc giả hợp lệ?}
    G -- Không --> X[Từ chối]
    G -- Có --> T[Bắt đầu transaction]
    T --> L[Khóa các book copies]
    L --> I{Mọi copy khả dụng?}
    I -- Không --> RB[Rollback]
    I -- Có --> H[Tạo loan header và items]
    H --> U[Cập nhật trạng thái copy + activity]
    U --> CM[Commit]
    CM --> RT[Nhận trả từng item]
    RT --> F[Ghi phí nếu có]
    F --> C{Còn item chưa trả?}
    C -- Có --> O[Giữ phiếu mở]
    C -- Không --> Z[Đóng phiếu]
```

## Sequence lập phiếu

```mermaid
sequenceDiagram
    actor Staff as Thủ thư
    participant C as LoanController
    participant S as LoanService
    participant R as LoanRepository
    participant DB as MySQL
    Staff->>C: POST /admin/loans + CSRF
    C->>S: Validate reader/copy IDs
    S->>DB: BEGIN
    S->>R: Lock reader/copies FOR UPDATE
    R->>DB: SELECT ... FOR UPDATE
    DB-->>R: Trạng thái hiện tại
    R-->>S: Reader + copies
    S->>R: Insert loan/items, update copies, activity
    R->>DB: Prepared statements
    S->>DB: COMMIT
    C-->>Staff: Redirect + flash
```

## Sequence nhận trả

```mermaid
sequenceDiagram
    actor Staff as Thủ thư
    participant C as LoanController
    participant S as LoanService
    participant R as LoanRepository
    participant F as FineService
    participant DB as MySQL
    Staff->>C: POST /admin/loans/return
    C->>S: Return loan item
    S->>DB: BEGIN + lock loan item/copy
    S->>R: Mark returned, update copy, close parent if complete
    S->>F: Assess overdue/damage/loss charge
    F->>DB: Insert deduplicated charge
    S->>DB: COMMIT
    C-->>Staff: Redirect + result message
```

## Class/module view

```mermaid
classDiagram
    class Router
    class LoanController
    class FineController
    class LoanService
    class FineService
    class LoanRepository
    class FineRepository
    class Database
    Router --> LoanController
    Router --> FineController
    LoanController --> LoanService
    FineController --> FineService
    LoanService --> LoanRepository
    LoanService --> FineService
    FineService --> FineRepository
    LoanRepository --> Database
    FineRepository --> Database
```

## Dòng dữ liệu mức tổng quan

```mermaid
flowchart LR
    Input[Form + Session] --> Validation[Controller validation + CSRF + role]
    Validation --> Logic[Service nghiệp vụ]
    Logic --> Tx[Transaction / row locks]
    Tx --> Repo[Repository prepared SQL]
    Repo --> DB[(MySQL)]
    DB --> Read[Repository queries]
    Read --> View[Escaped PHP view]
    View --> Output[HTML / CSV download]
```
