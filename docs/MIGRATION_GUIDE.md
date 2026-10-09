# Hướng dẫn migration database

## Nguyên tắc an toàn

- Cấu hình database mục tiêu trong `.env`; xác minh đúng host, port, tên database và backup trước khi nâng cấp.
- `database/setup.php` tạo database nếu cần và chạy các version chưa có trong `schema_migrations`. Không chạy `DROP DATABASE`, không xóa bảng hiện hữu.
- Dữ liệu mẫu dùng insert idempotent. Các bảng cũ tiếp tục được giữ; migration mới thêm bảng/quan hệ để chuyển dần sang tồn kho bản sao.
- Không xóa `schema_migrations` để ép chạy lại. Nếu migration lỗi, giữ nguyên bảng/dữ liệu, đọc lỗi CLI và xử lý thủ công có kiểm soát trước khi tiếp tục.

## Phiên bản hiện tại

1. `001_initial_schema`: schema người dùng, sách, độc giả, phiếu, đặt trước, audit và dữ liệu catalog nền.
2. `002_copy_level_inventory`: tạo `book_copies`/`loan_items`, sinh mã vật lý theo `book_code`, ánh xạ phiếu cũ sang bản sao không có khoảng mượn chồng lấn.
3. `003_reader_policies_reservations_notifications`: thêm chính sách nghiệp vụ, `loan_renewals`, liên kết reservation với bản sao, notifications và bảng settings; không xóa reservation cũ.
4. `004_versioned_library_policies`: tạo snapshot version chính sách và liên kết mỗi lần gia hạn với version/số ngày đã áp dụng.
5. `005_fines_and_payments`: tạo ledger `fine_charges`, payment/allocations và lịch sử miễn giảm; bổ sung mức phí sách hỏng/mất vào settings và policy snapshot. Migration chỉ tạo bảng/cột nếu chưa có và giữ nguyên khoản mượn/catalog hiện tại.
6. `006_normalized_catalog_metadata`: tạo author/publisher và bảng nối, thêm `books.publisher_id`, rồi backfill metadata hiện có; trường text cũ vẫn được giữ để tương thích.

Nếu lịch sử cũ không thể ánh xạ vào số bản hiện có, migration dừng với mã phiếu/sách gây xung đột và không ghi nhận version. Không tự động giảm số lượng hoặc sửa phiếu cũ.

## Chạy và xác minh

```powershell
php database/setup.php
```

Sau đó kiểm tra `schema_migrations`, số bản sao theo từng đầu sách, trạng thái on-loan và số `loan_items` đã ánh xạ. Seed quản trị riêng bằng `php database/seed.php` nếu cần.

## Khôi phục

Khôi phục từ backup MySQL đã xác minh theo quy trình của máy chủ. Không dùng script reset trên database có dữ liệu thật. `database/setup.php` không phải công cụ reset.

Lượt hoàn thiện cuối không thêm migration: sử dụng các cột shelf_location/barcode/status hiện có. setup.php đã sửa để seed mới tạo inventory/metadata ngay trong transaction, kiểm tra cài mới/chạy lại trên DB test; không chạy lại trên DB chính trong lượt này.
