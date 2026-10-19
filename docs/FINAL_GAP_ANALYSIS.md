# Đối chiếu sprint sản phẩm — 10/10/2026

> Trạng thái và test thực thi 11/10/2026: [AUDIT_REPORT_20261011](AUDIT_REPORT_20261011.md), [FINAL_SPRINT_PLAN](FINAL_SPRINT_PLAN.md). Nội dung có ngày cũ bên dưới là lịch sử, không phải test chạy lại hôm nay.


| Khoảng trống trước sprint | Kết quả hiện tại |
|---|---|
| Source trùng lồng/Git stage | So sánh giống nhau; chọn stage minh; workspace chính thức; không xóa source cũ |
| Không có CRUD nhân viên | StaffController, native create/edit/lock, chặn tự khóa và Admin cuối cùng |
| Chỉ có role guard | 22 action permissions, role defaults/user overrides, backend guard mỗi request, matrix SQL/HTTP |
| Chưa upload bìa | MIME/dimension/size, GD re-encode, random path, ngoài document root |
| Chưa PDF | Dompdf/lock/DejaVu Sans, loan/return/payment; ownership và permission |
| CSV thiếu sách/độc giả/tháng | Thêm cả ba, giữ UTF-8/formula escaping |
| Report chỉ preview | COUNT/LIMIT/OFFSET, pager riêng, lọc ngày loan/fines/monthly |
| Mượn nhiều bản khó dùng mobile | Searchable checkbox picker, native select fallback không JS |
| Chưa trả toàn bộ | Transaction chung, condition snapshot, UI xác nhận, duplicate/rollback tests |
| Không tạo login cùng thẻ | Tùy chọn email/password khi tạo thẻ mới, cùng transaction |
| Cài lại chưa từ clone | Clone GitHub minh thực tế, local overlay chưa push, Composer/DB/seed/browser riêng |
| Báo cáo trước nói không có Git | Sửa handover/test/README theo Git thực tế |

Nghiệp vụ có sẵn không viết lại toàn bộ: giữ checkout FIFO, khóa row, fine allocation, partial returns/renewals, policy versions, notifications và metadata. Bổ sung quanh API hiện hữu và chạy integration/browser lại.

Giới hạn còn lại được ghi rõ trong TEST_REPORT và FINAL_HANDOVER; không có lỗi nghiêm trọng đã biết trong luồng demo đã nghiệm thu.
