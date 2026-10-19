# Kịch bản demo Trợ lý Thư viện

Mở http://127.0.0.1:8010 và bấm **Trợ lý Thư viện** ở góc dưới bên phải.

1. Khách: nhập “Tìm sách trí tuệ xúc cảm”, rồi “tim sach tri tue xuc cam”. Kiểm tra tên/tác giả/bìa và liên kết chi tiết theo dữ liệu thật.
2. Nhập “Có sách tâm lý học không?”, rồi “Cuốn thứ hai còn không?”, “Tôi muốn xem chi tiết”. Chatbot dùng danh sách trước và kiểm tra lại tồn kho.
3. Nhập “Tôi muốn học lập trình web”. Đây là gợi ý theo chủ đề trong database, không khẳng định đánh giá độ khó hoặc cá nhân hóa học tập.
4. Nhập từ khóa không tồn tại: chatbot báo chưa tìm thấy, không dựng tên sách. Hỏi “Quy định mượn sách” để xem phiên bản chính sách MySQL.
5. Khi chưa đăng nhập, hỏi “Tôi đang mượn sách nào?”: yêu cầu đăng nhập. Đăng nhập Độc giả để xem phiếu của chính mình, hạn trả và tiền phạt. Nếu tài khoản không có phiếu, trạng thái rỗng là kết quả đúng.
6. Đăng nhập Thủ thư, hỏi báo cáo sách quá hạn; thử báo cáo Admin để kiểm tra quyền. Admin xem tổng hợp theo dữ liệu thật.
7. Hỏi gia hạn: chatbot hướng dẫn và mở trang nghiệp vụ, không tự gia hạn hoặc thanh toán.
8. Thử “DROP TABLE books”: từ chối. Nội dung HTML trong câu hỏi được hiển thị như văn bản. Xóa trò chuyện, thu nhỏ, đóng và mở lại; thử Enter/Shift+Enter và màn hình 390px.

Không có khóa API vẫn demo được các bước trên. Bìa local hoạt động khi mất Internet; sáu bìa Open Library từ sprint trước vẫn phụ thuộc mạng. Không tuyên bố tất cả bìa đã lưu local. Kiểm thử tạo phiếu/thu tiền chỉ dùng database QA độc lập ở cổng 8015; không chạy test phá hoại lên 8010. Không ghi mật khẩu demo vào tài liệu công khai.
