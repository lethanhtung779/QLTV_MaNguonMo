# Bổ sung đủ nhóm thể loại — 12/10/2026

Source chính thức QLTV_MaNguonMo-github-stage, branch minh. Main8010 → paper_page_library; QA8015 → paper_page_test_20261011_214305_37d8. Người dùng xác nhận thêm Văn phòng và giữ Văn học. Không push/merge/reset main.

## Kết quả và mức độ hoàn tất

Đã bổ sung 7 đầu sách thật thuộc 7 nhóm, tất cả có bìa chính thức local và nguồn/giấy phép. Giữ 12 sách Standard Ebooks và sáu ảnh ISBN từ các phiên trước. Tổng49 sách MySQL,28 công khai,105 bản sao vật lý cũ nguyên vẹn. Có19 ảnh local và6 ảnh remote tiếng Anh theo ISBN;3 sách công khai còn thiếu bìa xác minh. 21 demo ẩn trước đó không đổi visibility.

**Hoàn tất việc mỗi nhóm có sách có bìa; CHƯA đạt yêu cầu không thiếu một bìa nào.** Ba bản ghi B006/B007/B009 không có ISBN và chưa xác minh được đúng ấn bản. Không ẩn, đổi tên/ISBN hoặc lấy ảnh của cuốn khác. Không ghi 28/28 ảnh PASS. Không tuyên bố các sách tiếng Anh là ấn bản tiếng Việt.

| Thể loại | Sách công khai | Có ảnh | Còn thiếu |
|---|---:|---:|---:|
| Khoa học | 2 | 1 | 1 |
| Kinh tế | 2 | 1 | 1 |
| Lịch sử | 2 | 2 | 0 |
| Tâm lý - Kỹ năng sống | 2 | 2 | 0 |
| Thiếu nhi | 2 | 2 | 0 |
| Tin học | 3 | 2 | 1 |
| Văn học | 14 | 14 | 0 |
| Văn phòng | 1 | 1 | 0 |

## Sách đã thêm vào MySQL

| Thể loại | Sách | Tác giả | NXB/năm ấn bản | Nguồn và giấy phép |
|---|---|---|---|---|
| Kinh tế | The Wealth of Nations | Adam Smith | Standard Ebooks, 2021 | [Nguồn](https://standardebooks.org/ebooks/adam-smith/the-wealth-of-nations), [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/) |
| Khoa học | The Origin of Species | Charles Darwin | Standard Ebooks, 2019 | [Nguồn](https://standardebooks.org/ebooks/charles-darwin/the-origin-of-species), [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/) |
| Lịch sử | History of the Peloponnesian War | Thucydides | Standard Ebooks, 2024 | [Nguồn](https://standardebooks.org/ebooks/thucydides/history-of-the-peloponnesian-war/richard-crawley), [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/) |
| Tâm lý - Kỹ năng sống | Human Nature and Conduct | John Dewey | Standard Ebooks, 2024 | [Nguồn](https://standardebooks.org/ebooks/john-dewey/human-nature-and-conduct), [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/) |
| Thiếu nhi | Peter and Wendy | J. M. Barrie | Standard Ebooks, 2017 | [Nguồn](https://standardebooks.org/ebooks/j-m-barrie/peter-and-wendy), [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/) |
| Tin học | Think Python: How to Think Like a Computer Scientist | Allen B. Downey | Green Tea Press, 2012 | [Nguồn](https://greenteapress.com/thinkpython/thinkpython.pdf), [CC-BY-NC-3.0](https://creativecommons.org/licenses/by-nc/3.0/) |
| Văn phòng | LibreOffice 7.5 Getting Started Guide | LibreOffice Documentation Team | The Document Foundation, 2023 | [Nguồn](https://books.libreoffice.org/en/GS75/GS75-GettingStarted.pdf), [CC-BY-4.0](https://creativecommons.org/licenses/by/4.0/) |

Năm của Standard Ebooks là năm phát hành ấn bản Standard Ebooks, không phải năm nguyên tác. Tin học: trang bìa PDF Think Python phiên bản2.0.17 của Allen B. Downey/Green Tea Press,2012, CC-BY-NC-3.0 (giữ điều kiện phi thương mại). Văn phòng: bìa PDF Getting Started Guide7.5 của LibreOffice Documentation Team, tháng7/2023, CC-BY-4.0; trích nguyên trang đầu, không thêm artwork hay giả bìa. Trang chi tiết ghi attribution và link license/source. Các bìa đã mở xem bằng mắt, đúng tên/tác giả trên trang nguồn và metadata PDF. Không dùng stock/AI.

Sách mới có0 bản sao vật lý vì chưa kiểm kê thực tế, hiển thị chưa có sẵn. Không gán105 bản sao cũ sang sách mới hoặc tạo tồn kho giả để có nút mượn.

## Thay đổi source

- database/category_catalog_manifest.json:7 nguồn, license, hash/size/MIME, metadata đã đối chiếu.
- database/import_category_catalog.php: CLI, explicit main guard, kiểm tra bìa JPEG/size/hash, transaction và idempotency, thêm category Văn phòng nếu thiếu; không sửa/xóa bản ghi cũ hay visibility. QA7created và replay0.
- app/Helpers/functions.php: helper nguồn catalog dùng chung; app/Views/public/book.php hiển thị nguồn và giấy phép.
- tests/browser_category_covers.cjs: HTTP/detail/title/license/source/local offline và lọc từng category.
- tests/browser_category_responsive.cjs: toàn bộ pagination, mỗi card đối chiếu DB, đủ nhóm thể loại, ảnh decode/object-fit,404 và responsive.
- CSS/fonts/palette và composer.json/composer.lock giữ nguyên từng byte so ZIP trước thay đổi; không nâng dependency.

## Kiểm thử phiên này

- Chrome155 thật zoom100%: QA56 assertions +main56 assertions PASS về7 sách/ảnh offline/nguồn/filter. Không tính QA là kiểm chứng main.
- Main Chrome: **706 assertions PASS**,1440/1024/768/390px,3 trang/28 đầu sách,8 bộ lọc, ảnh đúng đường dẫn/không méo/không404, không tràn ngang, fallback3 sách vẫn hiện. Zero-missing requirement **BLOCKED3**, không nằm trong số PASS.
- PHP lint:128 file,0failure PASS.
- MySQL test độc lập:phase3 18 assertions,return-all atomic7 PASS.
- Bảo toàn25 bảng: mỗi dòng cũ, timestamp và quan hệ book_authors giữ nguyên; chỉ có các dòng mới trong books/categories/authors/publishers/book_authors.105copies/loan_items/reservations/history/finance không đổi.
- Backup trước thay đổi: paper_page_library-20261012-100047-4755b1.sql khôi phục vào schema test riêng,25/25 bảng exact rows/timestamps. Source-before.zip CRC và CSS/dependency exact.
- Các số824/155/273/108 trong báo cáo trước là lịch sử, không cộng vào706 phiên này. Không nói đã chạy lại tất cả nghiệp vụ/ba role UI trong phiên bổ sung catalog.

## Ba bản ghi chưa có bìa

- B006 — Vật lý lý thú/Y.Perelman/Giáo dục2019: không ISBN; các nguồn thư viện tìm được khác NXB/năm. [Danh mục thư viện ĐHSP TP.HCM](https://lib.hcmue.edu.vn/sites/default/files/UE1-DM%20SACH%20TANG%203%20LE%20VAN%20SY.pdf) chỉ đủ xác nhận tác phẩm/bản khác, chưa xác nhận bản hiện tại.
- B007 — Nền kinh tế tăng trưởng và thịnh vượng/William Bernstein/Trẻ2020: không ISBN; chưa tìm thấy nguồn tác giả/NXB/thư viện xác nhận đúng tên này. Không tự coi là The Birth of Plenty hay cuốn đầu tư khác.
- B009 — Kiến thức lập trình cơ bản/Nguyễn Văn A/Khoa học2022: không ISBN; chưa tìm thấy nguồn xuất bản xác nhận đúng bản ghi; tên tác giả có dấu hiệu dữ liệu minh họa nhưng chưa kết luận sách không tồn tại.

Tiêu chí hết BLOCKED: có căn cứ thư mục đúng tên/tác giả/ấn bản, ảnh đúng nguồn và quyền sử dụng phù hợp; kiểm chứng Chrome và bảo toàn nghiệp vụ. Chỉ thêm sách mới không tự giải quyết được metadata ba bản ghi này. Danh sách thiếu hiện hành: REAL_CATALOG_MISSING_COVERS.csv.

Bằng chứng riêng tư: storage/backups/category-covers-20261012/{main-state.json,qa-import.json,qa-replay.json,main-import.json,qa-browser.log,main-browser.log,responsive-results.json,responsive.log,lint.log,mysql-phase3.log,mysql-return-all.log,preservation-final.log,restore.log,source-before.zip,source-after.zip,evidence-manifest.json}. Ảnh chụp catalog-{1440,1024,768,390}-page-{1,2,3}.png. preservation.log ghi lỗi script đối chiếu khóa ghép trước khi sửa; kết quả cuối là preservation-final.log +main-state.json.
