# Bảo mật — trạng thái 11/10/2026

Đây là kiểm tra triển khai, không phải chứng nhận audit độc lập.

| Hạng mục | Trạng thái / căn cứ |
|---|---|
| SQL | Prepared PDO, whitelist SQL identifiers/sort, bound IDs/LIKE, server validation; test transaction/duplicate/concurrency PASS |
| XSS | Escape output, fallback ảnh dùng textContent; helper test PASS |
| CSRF | Token session mọi form POST; browser thiếu token 419, invalid type/token unit PASS |
| Session fixation/timeout | strict mode, only cookies, HttpOnly/SameSite, ID rotation login/password; timeout 1.800s; security test PASS |
| Login throttle | File hash email/IP + flock outside public; đổi cookie không xóa bộ đếm; không lưu email/password/token; instance test PASS |
| Backend roles | DB account/role/credential version rechecked;22action permissions/HTTP matrix |
| Ownership | Reader lấy reader_id từ user; notification/renewal ownership MySQL PASS; không thấy lịch sử người khác |
| Lỗi SQL | PDO text không đưa ra flash ở fee/reader; production debug tắt; generic error views |
| Password/session | Đổi/reset thu hồi phiên khác; current session sau commit;72byte/NUL/spaces/suffix/seed regression |
| Backup | Giữ DEFAULT_GENERATED; restore/clone exact rows24/24 |
| Secrets/source | .env ngoài public, bị ignore; .env/schema SQL trả 404 qua QA HTTP; QA credentials/log/cache/artifact bị ignore |
| CSV | UTF-8 BOM và neutralize formula; test PASS |
| Inventory/payment | Row lock, unique active copy, transaction; rollback/overpayment/two-session tests PASS |
| Upload | MIME/size/dimension, GD re-encode/random path ngoài public; PNG/JPEG/WebP browser QA |

Trước production cần TLS, APP_DEBUG=false/APP_ENV=production, document root public/, quyền DB/runtime tối thiểu, rate-limit tổng theo IP ở proxy (limiter ứng dụng hiện theo email/IP, một máy chủ), xoay/xóa cache/log và kiểm tra backup/restore. Không tin X-Forwarded-For khi proxy chưa cấu hình. Chưa có scanner độc lập, pentest, tải lớn hay kiểm chứng distributed deployment. Không ghi mật khẩu/token/session IDs vào log.

Bằng chứng/giới hạn phiên11/10: [AUDIT_REPORT_20261011](AUDIT_REPORT_20261011.md). Không chứng nhận pentest độc lập.
