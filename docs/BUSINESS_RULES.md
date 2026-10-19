# Quy tắc nghiệp vụ

Các quy tắc dưới đây phản ánh luồng hiện tại. Mức thời hạn, giới hạn mượn và phí được lấy từ system settings/policy version thay vì cố định trong giao diện.

## Danh mục và kho

1. Một đầu sách có thể có nhiều bản sao vật lý; tồn kho khả dụng được tính từ trạng thái từng bản sao, không nhận số “còn” do browser gửi lên.
2. Mã sách, mã bản sao/barcode, mã độc giả và mã phiếu có tính duy nhất theo ràng buộc schema.
3. Một bản sao đã mượn/đặt trước/hỏng/mất/ngừng phục vụ không được checkout như bản khả dụng.
4. Lịch sử mượn không xóa cứng; đầu sách/bản sao đã được tham chiếu cần được ngừng phục vụ hoặc từ chối xóa.
5. Tác giả liên kết nhiều-nhiều; publisher liên kết với nhiều đầu sách. Trường author/publisher cũ được giữ để tương thích.

## Độc giả và mượn/trả

1. Chỉ độc giả hoạt động, còn quyền mượn theo chính sách, được checkout.
2. Phiếu có một hoặc nhiều `loan_items`; mỗi item trỏ tới một bản sao cụ thể.
3. Checkout kiểm tra điều kiện độc giả và khả dụng của từng copy trong transaction; bản sao được khóa trước khi đổi trạng thái.
4. Unique constraint trên `active_copy_id` chặn một bản sao thuộc nhiều lượt mượn đang mở.
5. Trả sách cập nhật một item. Phiếu cha chỉ đóng khi không còn item đang hoạt động; trả trùng bị từ chối.
6. Gia hạn áp dụng theo policy snapshot, số lần tối đa và ngày gia hạn; item quá hạn/đang có reservation không đủ điều kiện theo policy.
7. FIFO reservation xác định bằng thời điểm tạo và id; bản được giữ không tính là khả dụng; có hạn nhận và xử lý hết hạn/hủy.
8. Mỗi thay đổi nhiều bảng nghiệp vụ dùng transaction; khi lỗi phát sinh phải rollback thay vì để trạng thái kho và phiếu lệch nhau.

## Phí và thanh toán

1. Phí quá hạn gắn với từng item, đồng bộ lũy kế; deduplication key ngăn tính lặp cùng một khoản.
2. Phí hỏng/mất được ghi nhận theo tình trạng lúc nhận trả và mức phí tại thời điểm đó.
3. Không cho số tiền phí/phân bổ âm.
4. Payment được phân bổ theo khoản phí cũ nhất; tổng thanh toán không được vượt dư nợ.
5. Miễn/giảm lưu khoản, lý do và người thực hiện; theo role guard hiện hành chỉ admin được miễn/giảm.

## Tài khoản và riêng tư

1. Tài khoản dùng `password_hash()`/`password_verify()`; role guard chạy phía server.
2. Độc giả chỉ truy cập hồ sơ, phí, thông báo và phiếu liên quan tới tài khoản của chính mình.
3. Admin mới sửa cài đặt nhạy cảm; thủ thư thực hiện vận hành nghiệp vụ nhưng không được đổi policy hoặc miễn phí.
4. CSRF bảo vệ form POST; output hiển thị phải được escape.

## Báo cáo

- KPI và báo cáo lấy từ MySQL; phân biệt đầu sách, bản sao và lượt mượn.
- Export CSV giới hạn số dòng/ngày theo service, xuất UTF-8 BOM; ô bắt đầu bằng ký tự spreadsheet formula được neutralize.
- Báo cáo kho/phí riêng và concurrency hai session đã PASS; PDF mượn/trả/thu phí đã có với kiểm tra quyền và ownership. Kho đếm copy vật lý, phiếu đếm DISTINCT loan_id. Ngừng thẻ phải hết sách/đặt trước/nợ; ngừng sách không xóa lịch sử đã đóng.

Mật khẩu/phiên cập nhật11/10: giới hạn72byte, không trim; đổi/reset thu hồi phiên khác. Xem AUDIT_REPORT_20261011.md.

## Bổ sung ngày 1 — 11/10/2026

- _request_key bắt buộc trên HTTP checkout/renew/payment; lặp cùng actor/operation/key/payload trả kết quả đã commit, không thu/gia hạn/lập phiếu thêm. Giao dịch lỗi rollback cả key.
- Thẻ ngừng hoạt động/hết hạn không được gia hạn. Daily fine của item cũ và ledger gia hạn cũ không bị đổi khi lưu chính sách mới; gia hạn mới dùng policy hiện hành.
- Ngừng sử dụng bản cuối vẫn giữ bản sao/lịch sử, tổng tồn phục vụ có thể bằng0. Chỉnh sách đã có chấp nhận0; tạo mới qua UI cần ít nhất1. Phục hồi bản sửa xong cập nhật tồn.
- Category chưa phân loại là null trong MySQL/option0 ở form, là lựa chọn hợp lệ.
- [Bằng chứng ngày 1](DAY1_BACKEND_REPORT_20261011.md).
