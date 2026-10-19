# KIỂM TOÁN / SỬA HIỂN THỊ BÌA SÁCH — 11/10/2026

> Cập nhật12/10/2026: người dùng cho phép bổ sung danh mục; đã thêm12 bìa chính thức/ẩn21 demo công khai. [Báo cáo mới](REAL_CATALOG_REPORT_20261012.md). Kết quả0/30 bên dưới là lịch sử trước khi bổ sung.

Chốt 2026-10-11T23:49:10+07:00. Source chính thức: QLTV_MaNguonMo-github-stage; branch minh. Không push/merge, không reset database.

## Kết quả bìa thật: chưa đạt toàn bộ

- MySQL thực tế paper_page_library: **30 đầu sách /105 bản sao**. **0/30 bìa đúng ấn bản được xác minh và gắn local;30 cuốn cần bổ sung**. Không gọi bìa ứng viên là bìa thật đã xác minh.
- **21 đầu sách minh họa** (DEMO-B001..021): description ghi rõ dữ liệu tổng hợp, tác giả/NXB minh họa, không ISBN. Không có một bìa xuất bản thật tương ứng để thay mà vẫn giữ nguyên dữ liệu.
- **6 sách có ISBN**: Open Library trả metadata/bìa ứng viên, nhưng dữ liệu tên Việt/NXB/năm đang lưu không xác minh được cùng ấn bản. Không đổi tên/ISBN/năm/NXB để hợp thức hóa ảnh.
- **3 sách chưa ISBN**: tìm theo tên + tác giả không có kết quả. Đây không phải khẳng định sách không tồn tại; nguồn tra cứu chưa đủ.
- **0 file bìa ứng viên tải/gắn;0 câu UPDATE vào main**. Không dùng AI/stock/ngẫu nhiên; mọi trường metadata, cover_url, quan hệ, bản sao và lịch sử giữ nguyên.

## Tra cứu và quyền nguồn

36 request metadata:6 ISBN +30 tên/tác giả, từng HTTP200, MIME application/json, giới hạn3 kết quả/tên, truy vấn tuần tự với khoảng nghỉ. URL và response đầy đủ ở storage/backups/covers-20261011/source-research.json. Không crawl Cover API. Metadata Open Library có thể thiếu/mâu thuẫn: B002 trả năm2011 trong khi [nguồn trường đại học của tác giả](https://cris.huji.ac.il/en/publications/sapiens-a-brief-history-of-humankind-6/) ghi ISBN này năm2015; MySQL lại2017. B008 [Wiley](https://uat.store.wiley.com/en-us/html-and-css-design-and-build-websites-p-9781118008188) ghi November2011, MySQL2021. Không tự sửa dữ liệu dựa trên một nguồn.

[Open Library Covers API — Guidelines for Cover Use](https://openlibrary.org/dev/docs/api/covers) hướng dẫn trang công khai dùng URL covers.openlibrary.org và không crawl API; bulk download có nguồn riêng. Không coi API/metadata mở là giấy phép blanket cho artwork. Vì không cuốn nào vượt bước xác minh ấn bản, không tải ảnh chỉ để kiểm tra MIME/dimensions rồi gắn sai. HTTP/MIME/kích thước/dung lượng/SHA của bìa thật mới: **không áp dụng vì chưa có ảnh được duyệt**. Cơ chế local upload hiện hữu được kiểm thử riêng; fixture QA không phải bìa thật của30 sách chính.

| Mã | ISBN MySQL | Tên ấn bản trả về | NXB / năm trả về |
|---|---|---|---|---|
| B001 | 9780060935467 | To Kill a Mockingbird | HarperPerennial ModernClassics / 2016 |
| B002 | 9780062316097 | Sapiens A brief Story of Human Kind | Harper / 2011 |
| B003 | 9780553383713 | Emotional Intelligence | Bantam Books / September 27, 2005 |
| B004 | 9780061122415 | The Alchemist | HarperSanFrancisco / 2006 |
| B005 | 9780590353427 | Harry Potter and the Sorcerer's Stone | Scholastic Inc. / 1999 |
| B008 | 9781118008188 | HTML and CSS | Wiley; Wiley [distributor] / 2011-11-8 |

## Thay đổi trực tiếp đã hoàn thành

- app/Views/partials/book_cover.php + app/Helpers/functions.php: component/helper dùng chung cho ảnh/fallback, local whitelist và kiểm tra file tồn tại. Bỏ tự động lấy ảnh từ ISBN chưa xác minh. Không dùng localStorage làm dữ liệu.
- Fallback có đầy đủ tên/tác giả lấy MySQL, ghi “Chưa có bìa xác minh”; không còn bìa chữ T/chữ đầu. Khi ảnh lỗi, hiện chính fallback server đã escape; không ghép text từ alt hoặc innerHTML.
- Trang chủ/nổi bật/mới, catalog/tìm kiếm, chi tiết, Admin books/dashboard, bản sao, lịch sử độc giả, phiếu mượn/đặt trước Thủ thư, account độc giả/mượn/lịch sử/đặt trước dùng cùng component.
- LoanRepository/ReservationRepository chỉ thêm metadata cover_url/author/category/book_id vào các JOIN sẵn có; không thêm query theo từng row, không thay transaction/idempotency/eligibility.
- Card thẳng hàng, tiêu đề3 dòng (tên đầy đủ vẫn ở DOM/chi tiết), object-fit:contain giữ toàn bìa, không zoom crop. Có Xem chi tiết; độc giả thấy đường dẫn Đặt trước khi tồn0, form/điều kiện nghiệp vụ vẫn ở chi tiết. Admin hiện số bản khả dụng/tổng và các thao tác theo quyền.
- CSS chỉ thêm layout/sizing/object-fit; không thay màu/font family/variables/design tổng thể, không dependency/schema/migration mới.

## Kiểm thử thực thi phiên này

| Kiểm tra | Kết quả | Bằng chứng storage/backups/covers-20261011/ |
|---|---|---|
| Chrome thật zoom100%,1440/1024/768/390 | **1053 checks PASS**;136 render công khai +44 render authenticated | browser-result.json, browser-final.log, ảnh PNG |
| Toàn bộ30 chi tiết ×4 widths |120 render PASS; title/author/year đối chiếu snapshot MySQL | browser-result.json |
| Homepage/catalog3pages, links Xem chi tiết, fallback đúng tên | PASS, không page overflow/JS exception/cover404 | main-*.png, featured-*.png |
| Admin/Thủ thư dashboard/books/loans/copies/history; reader account |44 render PASS, screenshot mobile | admin/librarian/reader-*.png |
| Ảnh local QA: cùng origin, contain, offline sau load, reload chặn Internet, lỗi ảnh -> named fallback | PASS kỹ thuật; **không gọi là bìa thật30 sách** | browser-result.json |
| Upload JPG/WebP, extension/MIME HTTP |6 PASS | cover-formats.log |
| PHP lint |124 file,0 lỗi | lint.log |
| MySQL phase3 / trả toàn bộ atomic |18 /7 PASS, database test riêng | mysql-phase3.log,mysql-return-all.log |
| JS syntax / git diff --check |PASS | syntax-final.json,diff-check.log |
| Bảo toàn main |25/25 bảng exact rows/timestamps,30 sách105 bản sao | database-before.json,database-preservation.json |

Phiên này dùng Chrome155.0.8059.39, main PHP8010 chỉ truy cập public read-only. Authenticated/ảnh lỗi/upload/nghiệp vụ kiểm thử trên8015 với database paper_page_test_20261011_214305_37d8. 273/108 trong báo cáo ngày2 là lịch sử của sprint trước, không tính lại vào1053. Các ảnh đã được mở kiểm tra trực quan; headless Chrome chạy100%, không thumbnail VS Code. Offline nghĩa là chặn Internet mà local PHP còn hoạt động; nếu chặn cả localhost thì không thể reload ứng dụng PHP.

## PASS / BLOCKED

**PASS:** cơ chế hiển thị dùng chung, fallback rõ tên/tác giả, local upload/offline, responsive, điều hướng, dữ liệu nguyên vẹn.

**BLOCKED:** mục tiêu thay toàn bộ bằng bìa thật đúng30 ấn bản. Cần bổ sung danh mục/ISBN/ấn bản có thật và ảnh được phép sử dụng. Không tự xóa21 sách minh họa, không thay danh mục, không thay ảnh của ấn bản khác. Không tuyên bố sản phẩm đã hoàn thành yêu cầu bìa thật.

## Danh sách cần bổ sung thủ công

[CSV có đủ30 cuốn](BOOK_COVERS_MISSING.csv). Mỗi cuốn cần ISBN (nếu có), NXB/năm/ấn bản tương ứng, ảnh bìa scan/nguồn có quyền sử dụng. Với21 sách minh họa cần quyết định riêng về danh mục sách thật; việc đổi danh mục nằm ngoài quyền sửa bìa hiện tại.

| Mã | Tên sách / tác giả từ MySQL | ISBN | NXB / năm trong MySQL | Trạng thái / lý do |
|---|---|---|---|---|
| B001 | Giết con chim nhại — Harper Lee | 9780060935467 | Nhà xuất bản Văn học / 2015 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B002 | Sapiens: Lược sử loài người — Yuval Noah Harari | 9780062316097 | Harper / 2017 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B003 | Trí tuệ xúc cảm — Daniel Goleman | 9780553383713 | Bantam / 2018 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B004 | Nhà giả kim — Paulo Coelho | 9780061122415 | HarperOne / 2016 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B005 | Harry Potter và Hòn đá phù thủy — J.K. Rowling | 9780590353427 | Bloomsbury / 2014 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B006 | Vật lý lý thú — Y. Perelman | Chưa có | Nhà xuất bản Giáo dục / 2019 | CẦN BỔ SUNG: Không ISBN; tra cứu tên + tác giả không có kết quả; cần ISBN/ấn bản và nguồn bìa được phép. |
| B007 | Nền kinh tế tăng trưởng và thịnh vượng — William Bernstein | Chưa có | Nhà xuất bản Trẻ / 2020 | CẦN BỔ SUNG: Không ISBN; tra cứu tên + tác giả không có kết quả; cần ISBN/ấn bản và nguồn bìa được phép. |
| B008 | HTML & CSS – Thiết kế Web — Jon Duckett | 9781118008188 | Wiley / 2021 | CẦN BỔ SUNG: ISBN trả về bìa ứng viên nhưng tên/ấn bản tiếng Anh và năm/NXB không khớp hoặc mâu thuẫn với MySQL; chưa đủ căn cứ gắn bìa. |
| B009 | Kiến thức lập trình cơ bản — Nguyễn Văn A | Chưa có | Nhà xuất bản Khoa học / 2022 | CẦN BỔ SUNG: Không ISBN; tra cứu tên + tác giả không có kết quả; cần ISBN/ấn bản và nguồn bìa được phép. |
| DEMO-B001 | Tủ sách minh họa Paper & Page 01 — Tác giả minh họa 01 | Chưa có | Nhà xuất bản minh họa / 2007 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B002 | Tủ sách minh họa Paper & Page 02 — Tác giả minh họa 02 | Chưa có | Nhà xuất bản minh họa / 2014 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B003 | Tủ sách minh họa Paper & Page 03 — Tác giả minh họa 03 | Chưa có | Nhà xuất bản minh họa / 2021 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B004 | Tủ sách minh họa Paper & Page 04 — Tác giả minh họa 04 | Chưa có | Nhà xuất bản minh họa / 2003 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B005 | Tủ sách minh họa Paper & Page 05 — Tác giả minh họa 05 | Chưa có | Nhà xuất bản minh họa / 2010 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B006 | Tủ sách minh họa Paper & Page 06 — Tác giả minh họa 06 | Chưa có | Nhà xuất bản minh họa / 2017 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B007 | Tủ sách minh họa Paper & Page 07 — Tác giả minh họa 07 | Chưa có | Nhà xuất bản minh họa / 2024 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B008 | Tủ sách minh họa Paper & Page 08 — Tác giả minh họa 08 | Chưa có | Nhà xuất bản minh họa / 2006 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B009 | Tủ sách minh họa Paper & Page 09 — Tác giả minh họa 09 | Chưa có | Nhà xuất bản minh họa / 2013 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B010 | Tủ sách minh họa Paper & Page 10 — Tác giả minh họa 10 | Chưa có | Nhà xuất bản minh họa / 2020 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B011 | Tủ sách minh họa Paper & Page 11 — Tác giả minh họa 11 | Chưa có | Nhà xuất bản minh họa / 2002 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B012 | Tủ sách minh họa Paper & Page 12 — Tác giả minh họa 12 | Chưa có | Nhà xuất bản minh họa / 2009 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B013 | Tủ sách minh họa Paper & Page 13 — Tác giả minh họa 13 | Chưa có | Nhà xuất bản minh họa / 2016 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B014 | Tủ sách minh họa Paper & Page 14 — Tác giả minh họa 14 | Chưa có | Nhà xuất bản minh họa / 2023 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B015 | Tủ sách minh họa Paper & Page 15 — Tác giả minh họa 15 | Chưa có | Nhà xuất bản minh họa / 2005 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B016 | Tủ sách minh họa Paper & Page 16 — Tác giả minh họa 16 | Chưa có | Nhà xuất bản minh họa / 2012 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B017 | Tủ sách minh họa Paper & Page 17 — Tác giả minh họa 17 | Chưa có | Nhà xuất bản minh họa / 2019 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B018 | Tủ sách minh họa Paper & Page 18 — Tác giả minh họa 18 | Chưa có | Nhà xuất bản minh họa / 2001 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B019 | Tủ sách minh họa Paper & Page 19 — Tác giả minh họa 19 | Chưa có | Nhà xuất bản minh họa / 2008 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B020 | Tủ sách minh họa Paper & Page 20 — Tác giả minh họa 20 | Chưa có | Nhà xuất bản minh họa / 2015 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |
| DEMO-B021 | Tủ sách minh họa Paper & Page 21 — Tác giả minh họa 21 | Chưa có | Nhà xuất bản minh họa / 2022 | CẦN BỔ SUNG: Dữ liệu ghi rõ sách/tác giả minh họa; không ISBN; tra cứu tên + tác giả không có kết quả. |

## Bổ sung đúng cuốn và khôi phục

Sau khi có ảnh xác minh và quyền sử dụng: Admin → Quản lý sách → tìm đúng mã → Sửa → chọn bìa → Lưu. Cơ chế upload hiện có kiểm MIME thực, kích thước/size, lưu tên random an toàn trong storage/uploads/covers; mapping cover_url cập nhật bởi backend cho đúngID, không đổi bản sao/lịch sử khi không sửa metadata/tồn. Không dùng đường dẫn/URL tùy ý, không bulk-update bằng ISBN chưa xác minh. Thư mục public/assets/book-covers được helper hỗ trợ với filename whitelist và file tồn tại cho các asset được duyệt sau này.

Source-before.zip CRC/hash đã kiểm chứng trước sửa; database-before.json giữ toàn bộ snapshot private ngoài public (không publish thông tin người dùng). source-after.zip +evidence-manifest.json đóng gói cuối, không restore đè main. Lệnh chạy lại: node tests/browser_book_covers.cjs (main chỉ đọc; QA8015 isolated), php tests/audit_book_covers.php (read-only), php tests/lint.php; MySQL tests phải override DB_NAME test. Không tạo lại database chính.
