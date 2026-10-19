# Bổ sung bìa các sách còn lại — 12/10/2026

Source chính thức: QLTV_MaNguonMo-github-stage; branch minh, không push/merge. Main http://127.0.0.1:8010 → paper_page_library; QA8015 → paper_page_test_20261011_214305_37d8.

## Kết quả hiện tại

42 đầu sách trong MySQL, 21 đang công khai: 12 bìa Standard Ebooks CC0 đúng ấn bản đã lưu local từ phiên trước; 6 ảnh bìa tiếng Anh liên kết theo ISBN đang lưu; 3 sách công khai chưa có bìa xác minh. **Không tuyên bố 18 bìa đúng ấn bản tiếng Việt hoặc mọi thông tin xuất bản đã xác minh.** 21 bản ghi demo đã được ẩn công khai theo chấp thuận trước đó; phiên này không đổi bất cứ is_public nào và không ẩn 9 sách còn lại.

Sáu bìa khớp tác phẩm/tác giả và có liên kết ISBN ở Open Library; tên tiếng Việt/NXB/năm trong dữ liệu có mâu thuẫn. Giữ nguyên các trường đó. Card ghi “Bìa bản tiếng Anh theo ISBN”; trang chi tiết ghi nguồn và cảnh báo chưa xác minh ấn bản tiếng Việt. Metadata Open Library cũng có khác biệt năm nên chỉ dùng làm nguồn liên kết ảnh, không sửa dữ liệu thư mục theo nó.

| Mã | Tên đang lưu | ISBN | Nguồn ảnh tiếng Anh |
|---|---|---|---|
| B001 | Giết con chim nhại | 9780060935467 | [To Kill a Mockingbird](https://openlibrary.org/books/OL37027463M) |
| B002 | Sapiens: Lược sử loài người | 9780062316097 | [Sapiens A brief Story of Human Kind](https://openlibrary.org/books/OL28440487M) |
| B003 | Trí tuệ xúc cảm | 9780553383713 | [Emotional Intelligence](https://openlibrary.org/books/OL7826621M) |
| B004 | Nhà giả kim | 9780061122415 | [The Alchemist](https://openlibrary.org/books/OL7288233M) |
| B005 | Harry Potter và Hòn đá phù thủy | 9780590353427 | [Harry Potter and the Sorcerer's Stone](https://openlibrary.org/books/OL61088892M) |
| B008 | HTML & CSS – Thiết kế Web | 9781118008188 | [HTML and CSS](https://openlibrary.org/books/OL25431044M) |

Ảnh HTTP200/JPEG, dung lượng 20–71KB được kiểm tra và xem bằng mắt; dấu vết SHA trong database/isbn_cover_manifest.json. Dùng trực tiếp nguồn theo [hướng dẫn Open Library Covers API](https://openlibrary.org/dev/docs/api/covers), không công bố bản sao tải nghiên cứu. Sáu ảnh cần Internet; 12 ảnh local vẫn có thể chạy offline. Chưa có căn cứ cho phép lưu lại sáu ảnh có bản quyền như tài sản public của dự án.

## Lỗi đã sửa và triển khai

- bootstrap/app.php: CSP chặn chuyển hướng hợp lệ từ Open Library sang archive.org, tái hiện bằng Chrome. Thêm HTTPS archive.org và subdomain vào img-src; giữ các directive còn lại.
- app/Helpers/functions.php: chỉ chấp nhận URL nằm trong manifest đã đối chiếu đủ mã/tên/tác giả/ISBN; không dùng ISBN làm ảnh tự động cho sách khác.
- app/Views/partials/book_card.php và app/Views/public/book.php: ghi rõ ngôn ngữ, ISBN và nguồn, dùng component chung và fallback khi lỗi mạng.
- app/Repositories/LoanRepository.php, ReservationRepository.php: lấy ISBN cùng query hiện có để component nhận đúng sách, không thêm query từng dòng.
- database/import_isbn_covers.php: transaction/lock và đối chiếu danh tính, chỉ sửa sáu cover_url, giữ updated_at; từ chối ghi đè bìa đã có, replay QA cập nhật0.
- Không đổi CSS, màu, font hay design system trong phiên này.

## Chưa hoàn thành / BLOCKED

- **B006 — Vật lý lý thú / Y. Perelman**: Không có ISBN; tìm thấy nhiều bản Perelman nhưng NXB/năm không khớp bản ghi Giáo dục 2019. Giữ công khai, hiện fallback tên sách và tác giả.
- **B007 — Nền kinh tế tăng trưởng và thịnh vượng / William Bernstein**: Không có ISBN; chưa tìm thấy nguồn xuất bản xác nhận đúng tên này của William Bernstein. Giữ công khai, hiện fallback tên sách và tác giả.
- **B009 — Kiến thức lập trình cơ bản / Nguyễn Văn A**: Không có ISBN; chưa xác minh được sách đúng tên/tác giả Nguyễn Văn A/NXB Khoa học 2022. Giữ công khai, hiện fallback tên sách và tác giả.

Nguồn đối chiếu B006: [danh mục thư viện ĐH Sư phạm TP.HCM](https://lib.hcmue.edu.vn/sites/default/files/UE1-DM%20SACH%20TANG%203%20LE%20VAN%20SY.pdf) có bản khác NXB/năm. B007: danh mục thư viện TP.HCM có sách đầu tư khác của Bernstein, không đủ căn cứ thay bằng ảnh đó. B009: sách lập trình của NXB Khoa học Kỹ thuật tìm thấy có tên/tác giả khác. Kết quả tìm kiếm không chứng minh sách không tồn tại; chỉ chưa xác minh được đúng bản ghi. Không thay tên/ISBN, không lấy bìa cuốn khác, không gắn ảnh AI.

## Kiểm thử thực thi phiên này

- Chrome thực tế, zoom100%, 1440/1024/768/390px: **155 assertions PASS**, chín sách vẫn hiện, sáu ảnh decode được, ba fallback, đường dẫn chi tiết đúng, không tràn ngang; sáu detail ghi đúng ISBN/nguồn; lỗi mạng hiện fallback.
- QA Chrome: sáu ảnh/nguồn PASS sau sửa CSP. Lỗi CSP trước sửa giữ trong qa-browser.log.
- PHP lint: **127 file,0 failure**.
- MySQL test độc lập: phase3 **18 assertions**, return-all atomic **7 assertions PASS**. Không chạy nghiệp vụ phá hoại trên main.
- Snapshot25 bảng main: **25/25 exact**, chỉ cho phép sáu cover_url thay đổi. Tên/tác giả/ISBN/NXB/năm, visibility, timestamp,105 copies, loan/reservation/fine/history không đổi.
- Backup SQL trước sửa phục hồi vào schema test mới: **25/25 bảng exact rows/timestamps PASS**.
- 824 checks catalog,273/108 sprint là lịch sử phiên trước, không cộng vào155 phiên này. Không tuyên bố đã kiểm tra lại toàn bộ UI ba vai trò trong phiên bổ sung sáu ảnh.

Bằng chứng riêng tư và ảnh chụp: storage/backups/remaining-covers-20261012/{browser-results.json,browser.log,catalog-1440.png,catalog-1024.png,catalog-768.png,catalog-390.png,preservation.json,restore.log,lint.log,mysql-phase3.log,mysql-return-all.log}. Source-before.zip có thể kiểm tra CRC; SQLbackup paper_page_library-20261012-094222-a50091.sql. Không reset database chính.
