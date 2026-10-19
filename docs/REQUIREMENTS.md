# Yêu cầu và trạng thái — 11/10/2026

Nguồn PHP/MySQL chính thức QLTV_MaNguonMo-github-stage, branch minh, document root public/. Không dùng index.html cũ hoặc localStorage cho nghiệp vụ.

Giữ PAPER & PAGE #193E30/#F8F3EB/#E8D8C5/#C58B54/#302B25; tiêu đề Playfair/Cormorant, nội dung Inter/Lato.

Đã triển khai public/auth/ba vai trò/nhân viên/22 quyền; CRUD sách/thẻ/metadata/copies/barcode/kệ; upload PNG/JPEG/WebP; mượn nhiều/trả phần,toàn bộ/gia hạn/đặt trước FIFO; ledger phí/thu/miễn giảm; PDF tiếng Việt/ownership; CSV/report/pagination; notifications/policy/profile/password; backup/restore.

Migrations001–008; PHP8.3.33/MySQL8.4.3 local. Không reset main; test ghi trên DB riêng; PASS cần evidence. [Bảng trạng thái mới](AUDIT_REPORT_20261011.md), [checklist](FINAL_SPRINT_PLAN.md), [nghiệm thu cũ10/10](TEST_REPORT.md).

CHƯA CÓ: REST API/CI/vai trò tùy ý. CHƯA KIỂM CHỨNG: publicHTTPS/Linux/MariaDB/load/pentest/job scheduler thực tế; không coi là đã hoàn thành.
