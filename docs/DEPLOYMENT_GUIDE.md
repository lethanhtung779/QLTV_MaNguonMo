# Hướng dẫn triển khai

## Chuẩn bị

1. Dùng PHP 8.2+, bật PDO MySQL và mbstring; dùng MySQL 8/MariaDB tương thích.
2. Tạo database riêng và tài khoản DB riêng với quyền cần thiết cho migration. Không dùng quyền root cho runtime production.
3. Cấu hình `.env` từ template an toàn; `APP_ENV=production`, `APP_DEBUG=false`, URL/base path đúng, mật khẩu riêng mạnh.
4. Tạo admin bằng seed có kiểm soát trong quá trình triển khai; thay/kiểm soát thông tin seed theo chính sách nội bộ.
5. Sao lưu DB trước migration; kiểm tra bản sao trước khi nâng cấp.

## Web server

- Apache/Nginx document root trỏ duy nhất tới `public/`.
- Bật HTTPS, secure session cookie, HSTS tại proxy khi phù hợp, giới hạn request body và timeout.
- Không cho truy cập source/config/log/test; quyền ghi tối thiểu cho `storage/logs/` và `storage/cache/login_attempts/`.
- PHP production tắt hiển thị lỗi; log ra file được bảo vệ và xoay vòng.
- Không triển khai bằng PHP built-in server ra Internet; built-in server chỉ dành cho dev.

## Migration và rollback

Chạy `php database/setup.php` sau khi backup. Migration `001`–`008` được ghi trong `schema_migrations` và thiết kế additive; setup có thể chạy lại, không phải lệnh reset. Không xóa thủ công `schema_migrations` và không rollback bằng cách drop bảng. Nếu migration lỗi, giữ nguyên database, lưu log, khôi phục từ backup/nhờ DBA xử lý trước khi thử lại.

## Sau triển khai

- Kiểm tra trang login, catalog, dashboard và các quyền route bằng tài khoản test.
- Kiểm tra kết nối DB, log, backup/restore, TLS, cấu hình cookie và document root.
- Không chạy fixture `tests/mysql_phase3.php` / `tests/mysql_phase4.php` trên production.
- CLI database/maintenance.php hỗ trợ Task Scheduler để đồng bộ reminder/expiry/phí; xem INSTALLATION_GUIDE.md. Đã hỗ trợ PDF/upload; CI deployment chưa có. Restore local đã đối chiếu24bảng ngày11/10; production chưa kiểm chứng. Test ghi bị guard theo database test riêng.
