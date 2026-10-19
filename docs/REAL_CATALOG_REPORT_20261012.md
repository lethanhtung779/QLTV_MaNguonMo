# DANH MỤC SÁCH THẬT / BÌA CHÍNH THỨC — 12/10/2026

Hoàn tất 2026-10-12T09:27:30+07:00; source QLTV_MaNguonMo-github-stage, branch minh. Người dùng đồng ý bổ sung sách thật và ngừng công khai21 sách minh họa. Không push/merge/reset main.

## Kết quả thực tế đã cập nhật MySQL

- **12 đầu sách thật được bổ sung**, đúng title/author/ấn bản **tiếng Anh của Standard Ebooks**, bìa chính thức từ publisher được tải local và ghi cover_url vào MySQL. Không dịch/đổi tên để gắn bìa khác, không đặt ISBN giả: publisher không cung cấp ISBN nên lưu NULL.
- **42 tổng đầu sách,21 công khai** (12 mới có bìa +9 sách cũ). **21 sách minh họa không còn xuất hiện ở homepage/catalog/search công khai**, guest detail404; Admin/Thủ thư vẫn quản lý được và người đăng nhập có thể xem lịch sử/chi tiết cũ.
- **105 bản sao vật lý cũ nguyên vẹn**.12 sách mới có0 bản vật lý vì chưa được kiểm kê; không tạo tồn kho giả, hiển thị Chưa có sẵn. Không chuyển phiếu hoặc bản sao cũ sang sách mới, không xóa bản ghi cũ.
- **9 sách công khai còn thiếu bìa xác minh**; [CSV hiện tại](REAL_CATALOG_MISSING_COVERS.csv). Không gọi tất cả42 sách đã có bìa, không thay bìa của21 sách minh họa bằng ảnh sách khác.

## Metadata / nguồn / quyền sử dụng

Đối chiếu title/schema:author/schema:datePublished/schema:publisher và og:image trên từng trang publisher. Năm lưu là năm phát hành ấn bản Standard Ebooks, không nhầm năm tác phẩm gốc. Bìa là JPEG phát hành chính thức cho đúngebook, không tự thiết kế từ tranh stock, không AI và không lấy tranh rời làm giả bìa.

[Trang publisher ví dụ Pride and Prejudice](https://standardebooks.org/ebooks/jane-austen/pride-and-prejudice) ghi nội dung do/cho Standard Ebooks tạo được hiến tặng bằng [CC0-1.0](https://creativecommons.org/publicdomain/zero/1.0/), đồng thời phân biệt nội dung bên thứ ba. Chỉ dùng cover.jpg của các ấn bản này, không áp giấy phép chung cho ảnh từ nơi khác; trang nguồn/bằng chứng XHTML và URL lưu riêng. Đây là bìa ấn bản Standard Ebooks, không tuyên bố là bìa bản dịch tiếng Việt hay bản in của NXB khác.

HTTPS-only/host allowlist standardebooks.org, redirects kiểm tra lại scheme/host/port; HTTP200, response MIME image/jpeg, signatureJPEG, tối đa3MB. Trước nhập: getimagesize/MIME JPEG, width150..5000/height200..5000, dung lượng và SHA-256 khớp manifest. Tên file theo mã sách băm từ URL+hashnội dung, không ghi đè file khác. Local public/assets/book-covers, same origin, không CORS hay phụ thuộc mạng ngoài để hiện bìa. Manifest database/real_catalog_manifest.json có đủ12 sourceURL/coverURL/license/date/hash/bytes, importer transaction có dry-run/guard main/idempotency.

| Mã sách | Tên / tác giả đúng ấn bản | NXB / năm | Nguồn bìa chính thức |
|---|---|---|---|
| SE-4AF6489C137F | Pride and Prejudice — Jane Austen | Standard Ebooks / 2014 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/jane-austen/pride-and-prejudice) |
| SE-FA0F2ED703F0 | Sense and Sensibility — Jane Austen | Standard Ebooks / 2019 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/jane-austen/sense-and-sensibility) |
| SE-0A571FE5198E | Emma — Jane Austen | Standard Ebooks / 2018 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/jane-austen/emma) |
| SE-0C8A9E332BC1 | Alice’s Adventures in Wonderland — Lewis Carroll | Standard Ebooks / 2014 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/lewis-carroll/alices-adventures-in-wonderland/john-tenniel) |
| SE-963C9E3127FF | Through the Looking-Glass — Lewis Carroll | Standard Ebooks / 2017 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/lewis-carroll/through-the-looking-glass/john-tenniel) |
| SE-8E336F755537 | The Picture of Dorian Gray — Oscar Wilde | Standard Ebooks / 2014 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/oscar-wilde/the-picture-of-dorian-gray) |
| SE-1A523E0D9BC9 | Frankenstein — Mary Shelley | Standard Ebooks / 2015 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/mary-shelley/frankenstein) |
| SE-E04798A34809 | Dracula — Bram Stoker | Standard Ebooks / 2014 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/bram-stoker/dracula) |
| SE-141AE7FB7238 | Treasure Island — Robert Louis Stevenson | Standard Ebooks / 2015 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/robert-louis-stevenson/treasure-island) |
| SE-A084D48849EB | A Tale of Two Cities — Charles Dickens | Standard Ebooks / 2019 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/charles-dickens/a-tale-of-two-cities) |
| SE-AE44378E3A31 | Great Expectations — Charles Dickens | Standard Ebooks / 2019 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/charles-dickens/great-expectations) |
| SE-3467BE4B7557 | The Great Gatsby — F. Scott Fitzgerald | Standard Ebooks / 2021 | [Trang nhà xuất bản](https://standardebooks.org/ebooks/f-scott-fitzgerald/the-great-gatsby) |

## Source thay đổi

- database/migrations/011_public_catalog_visibility.php +database/schema.sql: is_public độc lập is_active, default1,index(is_public,is_active); không ngừng lưu hành demo hay đổi status bản sao. Chỉ21 DEMO-B001..021 đã được đối chiếu description đổi is_public=0; giữ updated_at cũ.
- database/import_real_catalog.php: validated manifest, thêm qua BookRepository::create/syncMetadata trong một transaction; không seed/reset. Chạy lại nhận đúng12 mã hiện hữu, không thêm trùng; mismatchmetadata/checksum/record demo abort/rollback. Explicit --allow-main và --apply bắt buộc khi main.
- app/Repositories/BookRepository.php: search/count/find có publicOnly tùy ngữ cảnh; Admin/internal giữ mặc định đầy đủ. featured là tuyển chọn từ12 bìa chính thức local, không giả popularity/loan counts.
- app/Controllers/CatalogController.php, ReaderController.php: public/home/catalog/count/readerrecommend lọc is_public; guest không xem hidden demo, authenticated history vẫn truy cập. Thủ thư/loan service/availability/policy không đổi.
- app/Views/public/home.php: dòng tuyển chọn phản ánh đúng dữ liệu, không nói độc giả yêu mến sách chưa từng mượn. app/Views/admin/books.php ghi Không công khai cho demo; layout/palette/font giữ nguyên.
- Component chung và fallback của phiên trước tiếp tục dùng trên mọi màn hình; card contain, tên/tác giả từ MySQL; source JavaScript/HTML không hardcode12 tên sách.

## Test thực thi lần này

| Kiểm tra | Kết quả | Bằng chứng storage/backups/real-catalog-20261012/ |
|---|---|---|
| Chrome155,100%,1440/1024/768/390 | **824 PASS**,60 public render +40 authenticated render | browser-result.json/browser.log, PNG |
|12 cover HTTP/MIME/hash trùng source |36 assertions PASS | browser-result.json |
|12 detail ×4 widths: title/author/publisher/year/photo |48 render PASS | browser-result.json, chi tiết PNG |
| Home thật5 ảnh, catalog2pages/no demo/search hidden guest404 |PASS | featured-*.png/home-*.png |
| Internet bị chặn, reload local covers vẫn tải |PASS đủ4 widths | browser-result.json |
| Admin/Thủ thư quản lý demo/loans; reader account/catalog |40 render PASS; admin hidden detail200 | browser-result.json, role PNG |
| MySQL nghiệp vụ /return-all atomic |18 /7 PASS trên test DB | mysql-phase3.log/mysql-return-all.log |
| Import QA2 lần |12 added rồi0 added, không copies giả | Initial apply console output; qa-import-replay.json |
| PHP lint /JS syntax /git diff |126 PHP0 lỗi, JS/diff PASS | lint.log,syntax-final.json,diff-check.log |
| Khôi phục SQL backup trước cập nhật |25/25 exact rows/timestamps trong schema test mới | restore.log/restore-*.json |
| Main preservation sau cập nhật |mọi bản ghi cũ25 tables nguyên metadata/timestamps;105 copies/history không đổi | preservation.json/database-before.json |

PASS là assertion đã thực thi;824 không cộng273/108 hoặc1053 lịch sử. Browser readonly public trênmain8010; authenticated/core/rollback tests dùngtest8015/paper_page_test_20261011_214305_37d8. Import main sau QA5 realcoversPASS. Không kiểm thử phá hoại/reset main.

## Bảo toàn / khôi phục / còn thiếu

SQL consistent backup: storage/backups/paper_page_library-20261012-092053-0af1d5.sql; restore chỉ vào schema test mới. Source-before.zip giữ snapshot source trước đợt này từ gói cuối đã hash/CRC của phiên trước;source-after.zip/hashmanifest lưu bản sau. Các thay đổi được phép: books thêm12rows/fieldvisibility21rows; authors/book_authors/publishers append đúng quan hệ; schema_migrations thêm011. Mọi row cũ so sánh exact, bỏ duy nhất field is_public vừa thêm; không rewrite tên/ISBN/timestamps cũ. Không tự restore đè main.

Còn9 sách công khai cũ chưa có bìa đúngấn bản; không che giấu bằng cover random. Tồn kho12 sách mới cần được thư viện kiểm kê/bổ sung bằng Admin → Bản sao khi thực sự có sách; không suy diễn có bản vật lý từ bìa ebook. Nếu cần danh mục tiếng Việt, phải lấy đúngấn bản/NXB/ISBN/nguồn quyền của bản dịch, không đổi nhãn bản tiếng Anh hiện tại.
