# Trang chủ và phân trang — 12/10/2026

Trang chủ trước chỉ hiển thị 5 nổi bật và 6 sách mới. Đã thêm khu vực Tất cả sách, truy vấn MySQL server-side 12 đầu sách/trang, tổng 28 sách công khai qua 3 trang (12/12/4). Không thêm sách giả, không thay đổi visibility hay tồn kho. Danh mục /catalog giữ bộ lọc hiện hữu và có thêm nút số trang; URL giữ query và trang chủ nhảy về #all-books.

File sửa: app/Controllers/CatalogController.php, app/Views/public/home.php, app/Views/partials/pagination.php. Backup trước sửa: storage/backups/home-pagination-20261012.

Kiểm thử thực thi: PHP lint 3/3; Chrome 14 checks PASS gồm số lượng từng trang, đủ 28 link chi tiết khác nhau, bấm trang 2, chặn trang vượt giới hạn, 1440/1024/768/390 không tràn ngang, catalog trang 2. Bằng chứng browser-results.json và home-*.png trong thư mục backup. Database chính 25/25 bảng vẫn khớp snapshot trước chatbot. git diff --check không lỗi.

Không bổ sung số lượng sách trong lần sửa này: 28 là số công khai thực tế, không lấy 49 tổng để đếm cả dữ liệu minh họa đã được ẩn từ sprint trước. Giữ nguyên palette/font. Không push.
