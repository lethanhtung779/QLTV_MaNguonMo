# PAPER HAVEN — BẢN SỬA SOURCE TRỰC TIẾP (18/10/2026)

## Phạm vi và nguyên tắc

Bản vá được thực hiện từ `Paper_Haven_Source_Review.zip` do người dùng gửi. **Không kết nối, ghi, xóa hoặc chạy migration trên** database chính `paper_page_library`. Không tự commit hoặc push GitHub. Gói cập nhật không chứa `.env`, Git history, `storage/backups` hoặc mật khẩu demo trong tài liệu bàn giao.

## Lỗi và cải tiến đã xử lý trong code

1. **Nhập sách hàng loạt:** Chỉ tạo đầu sách với `total_copies=0`, không tự sinh hai bản sao vật lý hoặc vị trí kệ. Trường ISBN được kiểm tra checksum ISBN-10/13; mã EAN-13 `893...` và mã không hợp lệ bị từ chối. ISBN hợp lệ về checksum **không đồng nghĩa đã xác minh đúng ấn bản**, cần tra cứu nguồn thật.
2. **Xác nhận nhập an toàn:** Loại bỏ việc tin tưởng `confirmed_data` JSON ẩn có thể sửa trên trình duyệt. Preview được lưu vào session server, có token dùng một lần và giới hạn 15 phút. Trước khi ghi, backend kiểm tra trùng ISBN, tên/tác giả thêm lần nữa trong transaction. Giới hạn 150 dòng/lượt và file CSV 500 KB; không đặt mô tả, năm xuất bản hay tên NXB giả.
3. **Quản trị mượn/trả:** Các bản sao trong phiếu mượn được gom thành hàng gọn, vùng nút trả/gia hạn/biên nhận được căn lại; bảng có cuộn ngang có kiểm soát ở màn hẹp thay vì ép nhiều cột tí hon.
4. **Giao diện quản trị:** Tăng cỡ chữ bảng, bộ lọc, sidebar, trạng thái; tinh chỉnh khoảng cách, KPI, bảng quản lý sách và responsive 390/768/1440px trong CSS. **Chưa kiểm chứng bằng trình duyệt trên dữ liệu thật** vì ZIP không chứa MySQL.
5. **Trang chi tiết sách:** Ngừng mặc nhiên ghi “đã mượn hết” cho bản sao không khả dụng vì có thể hỏng/chờ kiểm kê. Không gắn nhãn Open Access/Standard Ebooks chỉ theo giấy phép CC0 hay tên mã nếu không có nguồn phù hợp. Thông tin “bản quyền thuộc NXB” chung chung được thay bằng nhãn đối chiếu nguồn xuất bản. ISBN không tự gắn VERIFIED chỉ vì cột ISBN có dữ liệu ở fallback.
6. **PHP warnings:** Không hiển thị cảnh báo PHP và đường dẫn source trên trang web; vẫn ghi log cho người phát triển. Các trường metadata tùy chọn trong template hiện có đã có giá trị dự phòng.
7. **QR/Barcode:** Giữ thuật toán đang hoạt động, bổ sung validation để không âm thầm thay ký tự không hợp lệ hoặc cắt dữ liệu QR quá giới hạn (26 byte với QR version 1/2 hiện có). Endpoint phản hồi HTTP 422 cho mã không hỗ trợ thay vì trả mã sai.
8. **Kiểm thử:** Sửa một fixture smoke test cũ dùng URL ảnh đã được đổi sang đường dẫn ảnh local trong source.
9. **An toàn bàn giao:** Giá trị mật khẩu seed/demo xuất hiện trong `docs/FINAL_HANDOVER.md` đã được thay bằng placeholder. File `.env` cá nhân trên máy người dùng không bị thay đổi.

## Kiểm thử thực hiện được trong môi trường phân tích

- PHP lint: **174 file PHP, 0 lỗi cú pháp**.
- `php tests/run.php`: **10/10 PASS**.
- `php tests/source_review_smoke.php`: **18/18 PASS** (checksum ISBN, metadata AUTH-017, QR và Code128, dữ liệu quá giới hạn).
- `python tests/verify_barcode_decoding.py`: **6/6 PASS**, giải mã độc lập thực sự bằng ZBar cho QR và Code 128 trên 3 mã bản sao mẫu.

## Chưa thể tuyên bố PASS

- Các luồng thực tế đăng nhập Admin/Thủ thư/Độc giả, tạo phiếu mượn/trả, kiểm kê và nhập sách ghi MySQL: **BLOCKED**, vì ZIP không có DB QA và môi trường phân tích không kết nối database máy bạn.
- Kiểm thử camera thực và máy quét USB: **BLOCKED** vì không có phần cứng.
- Kiểm tra CSS trực tiếp bằng browser trên nội dung thật: **BLOCKED**; bạn cần mở app tại `http://127.0.0.1:8010` sau khi áp dụng bản vá.
- Trạng thái 51 sách của migration 017, xác minh ấn bản/ISBN, ảnh bìa còn thiếu: **không thay đổi**, vì chưa có quyền và DB QA để áp dụng. Không chạy migration cũ để mở web.
- Không đảm bảo toàn bộ lỗi từng tồn tại trong video đã được xử lý; đặc biệt lỗi chưa thể tái hiện nếu không có DB và thao tác thực tế.

## Áp dụng bản vá lên Windows (khuyên dùng)

1. Đóng mọi tác vụ Antigravity đang sửa cùng các file. **Sao lưu thư mục project hiện có**, hoặc tạo commit/backup cục bộ trên nhánh `minh` nếu phù hợp. Sao lưu database độc lập như thường lệ trước khi thử chức năng ghi.
2. Giải nén `Paper_Haven_Chi_Cac_File_Da_Sua.zip` vào thư mục `QLTV_MaNguonMo-github-stage`, giữ nguyên đường dẫn tương đối và **chỉ ghi đè sau khi so sánh** nếu Antigravity đã sửa file sau lúc bạn tạo ZIP nguồn.
3. **Không chép đè `.env`/`storage`/MySQL**. Gói vá không chứa các file này.
4. Reload trình duyệt `Ctrl + Shift + R`, kiểm tra `/admin/books/import`, `/admin/loans`, `/admin/books`, `/book/337`, `/catalog`, `/admin/inventory/audits` và trang in QR/barcode.
5. Trên PowerShell khi PHP đã cấu hình trong PATH: `php tests/run.php`, `php tests/source_review_smoke.php`, `php tests/lint.php`.
6. Thực hiện kiểm thử nhập sách trên DB QA, chọn CSV một đầu sách mẫu: phải tạo **1 đầu sách và 0 bản sao**; thử sửa nội dung ẩn hoặc gửi token giả phải từ chối.
7. Nếu phát hiện lỗi với tên sách/mã bản sao dài quá 26 byte, cần dùng giải pháp QR phiên bản lớn hơn hoặc rút ngắn mã sao phù hợp; **không bỏ validation để nhận mã sai**.

### Các file chính thay đổi

- `app/Controllers/BookImportController.php`
- `app/Controllers/InventoryAuditController.php`
- `app/Services/BarcodeService.php`
- `app/Views/admin/book_import.php`
- `app/Views/admin/loans.php`
- `app/Views/public/book.php`
- `app/Helpers/functions.php`
- `public/assets/css/app.css`
- `bootstrap/app.php`
- `docs/FINAL_HANDOVER.md`
- `tests/run.php`
- `tests/source_review_smoke.php` (mới)
- `tests/verify_barcode_decoding.py` (mới)
- `docs/SOURCE_REVIEW_PATCH_20261018.md` (mới)
