# Checklist triển khai Paper & Page

Cập nhật lần cuối: 08/10/2026. Đây là checklist theo trạng thái source và kiểm thử thực tế, không phải cam kết rằng các mục chưa đánh dấu đã hoàn thành.

## Giai đoạn 1 — Kiểm kê dữ liệu và migration an toàn

- [x] Kiểm tra database `paper_page_library` trên MySQL 8.4.3 trước khi thay đổi.
- [x] Xác nhận dữ liệu đang có: 1 tài khoản quản trị, 7 thể loại, 9 đầu sách; chưa có độc giả hoặc phiếu mượn trước migration.
- [x] Bảo toàn schema và dữ liệu cũ; chỉ bổ sung bảng/cột ở migration mới, không `DROP` hoặc thay thế bảng cũ.
- [x] Ghi nhận phiên bản `002_copy_level_inventory` trong `schema_migrations`.
- [x] Chuyển số lượng đầu sách hiện có thành 63 bản sao vật lý với mã duy nhất.
- [x] Thiết kế ánh xạ lịch sử mượn cũ sang bản sao theo các khoảng mượn; dừng migration có thông báo nếu tồn kho cũ không đủ để ánh xạ các lượt mượn chồng lấn.

## Giai đoạn 2 — Bản sao vật lý và phiếu nhiều sách

- [x] Thêm bảng `book_copies` và `loan_items`, khóa ngoại, ràng buộc, chỉ mục và chống cho mượn đồng thời một bản sao.
- [x] Khi tạo/sửa số lượng đầu sách, tạo hoặc ngừng phục vụ bản sao cụ thể; không giảm số lượng bằng bản đang mượn/giữ chỗ.
- [x] Cho phép tạo một phiếu có nhiều bản sao, kiểm tra điều kiện độc giả và tồn kho trong transaction.
- [x] Hiển thị mã bản sao, hạn trả và thao tác trả riêng từng bản.
- [x] Chỉ đóng phiếu cha khi mọi bản sao đã được trả.
- [x] Ghi nhận tình trạng tốt, hao mòn, hỏng hoặc mất khi nhận trả.
- [x] Kiểm tra luồng tạo đầu sách/độc giả, mượn hai bản cùng phiếu, trả một phần, trả bản cuối và số tồn thực tế qua HTTP/MySQL.

## Giai đoạn 3 — Gia hạn, đặt trước và thông báo

- [x] Gia hạn từng bản theo cấu hình chính sách/version, chặn quá hạn và vượt số lần; lưu người xử lý, thời hạn cũ/mới và snapshot version.
- [x] Hàng đợi đặt trước FIFO gắn bản sao cụ thể, có hạn nhận; cấp khi trả/thêm bản, fulfillment đúng độc giả và tự phân bổ khi reservation hết hạn/bị hủy.
- [x] Thông báo nghiệp vụ lưu MySQL, có trạng thái đọc, đánh dấu đã đọc và unique key chống tạo trùng.
- [x] Giao diện quản trị cấu hình chính sách và giao diện độc giả gia hạn/xem thông báo.
- [x] Kiểm thử MySQL thật với renewal limit, snapshot version, duplicate reservation, FIFO/expiry/fulfillment, inventory state, notifications và render các trang liên quan.

## Giai đoạn 4 — Phí, thanh toán và xử lý quá hạn

- [x] Ghi khoản phạt theo từng mục mượn (quá hạn, hỏng, mất), tự cập nhật phí quá hạn và chống sinh trùng.
- [x] Ghi nhận thanh toán có phương thức, người nhận, phân bổ theo khoản phí cũ nhất và chặn thu vượt dư nợ trong transaction.
- [x] Theo dõi miễn/giảm có lý do, người thực hiện, audit log và thông báo tới độc giả.
- [x] Cấu hình mức phí hỏng/mất cùng snapshot chính sách; hiển thị số dư, chi tiết khoản phí và lịch sử thanh toán ở đúng vai trò.
- [x] Kiểm thử integration MySQL thật cho phí, phân bổ, overpayment guard, miễn giảm và render giao diện.

## Giai đoạn 5 — Metadata, độc giả và phân quyền

- [x] Chuẩn hóa tác giả/nhà xuất bản và quan hệ sách-tác giả; backfill catalog cũ, giữ trường legacy, hỗ trợ nhiều tác giả khi CRUD.
- [x] Hồ sơ độc giả, thông tin liên hệ, hạn thẻ, lịch sử lượt mượn và số dư/chi tiết phí được hiển thị qua cổng độc giả.
- [x] Ranh giới admin/thủ thư/độc giả: cài đặt và miễn/giảm chỉ admin; CRUD/mượn/thu phí cho admin/thủ thư; độc giả chỉ truy cập dữ liệu tài khoản của mình.
- [x] Thêm kiểm thử MySQL cho quan hệ authors/publishers và các màn hình báo cáo; kiểm tra quyền tiếp tục theo role guard hiện có.

## Giai đoạn 6 — Dashboard, báo cáo và tài liệu bàn giao

- [x] KPI dashboard theo số đầu sách/bản sao/đang có/đang mượn/độc giả/phiếu/quá hạn/đặt trước/dư nợ.
- [x] Bộ lọc báo cáo theo ngày/trạng thái, dữ liệu lượt mượn theo bản sao, CSV UTF-8 BOM và chống formula injection.
- [x] Cập nhật README, migration guide, audit, design/database docs, ERD, data dictionary và báo cáo kiểm thử.
- [x] Kiểm thử tự động MySQL thật cho migrations, ledger, metadata, báo cáo và render; cập nhật PASS/FAIL/BLOCKED theo bằng chứng.
- [x] Kiểm thử concurrency thực sự trên hai session; PDF vẫn ngoài phạm vi.

## Giai đoạn 7 — Phạm vi mở rộng theo đặc tả đồ án

- [x] Seed fixture opt-in local: 21 sách bổ sung, tài khoản thủ thư/độc giả, phiếu đang mượn/quá hạn/trả một phần/đã trả, reservation ready và ledger phí; có nhãn `DEMO`, tự sinh credential vào `.env` nếu trống, không xuất password. MySQL đã xác nhận 30 đầu sách, 105 bản sao tổng cộng (42 bản demo) và 5 phiếu fixture; chạy lại không nhân đôi.
- [ ] Quản trị tài khoản/thủ thư và permission matrix chi tiết; hiện role guard giới hạn theo ba vai trò tổng quát.
- [x] Trang CRUD tác giả/nhà xuất bản riêng, đồng bộ metadata legacy.
- [x] Backend pagination catalog/sách/độc giả/metadata, sort whitelist, báo cáo kho/phí CSV.
- [x] Browser 22 route × 5 viewport; 273 checks, modal/sidebar/form và screenshots kho.
- [ ] Security review độc lập, session fixation/timeout và ma trận trái quyền đầy đủ trước triển khai production.

Hiện trạng cuối 08/10/2026 nằm trong FINAL_GAP_ANALYSIS.md/TEST_REPORT.md; các phần mô tả trước đây là lịch sử từng giai đoạn. Session fixation/timeout/throttle đã có automated test; security audit độc lập vẫn ngoài lượt này.
