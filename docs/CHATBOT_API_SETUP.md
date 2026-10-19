# Cấu hình API AI tùy chọn

Chatbot chạy được bằng MySQL và quy tắc khi không có API key. Hiện tại chưa kiểm chứng gọi OpenAI trực tiếp vì môi trường không có khóa/model cấu hình.

Trong `.env` local, tự cấu hình (không commit khóa):

```dotenv
CHAT_LLM_ENABLED=true
OPENAI_API_KEY=<khóa của bạn>
CHAT_LLM_MODEL=<model hỗ trợ Responses và JSON mode của tài khoản>
```

PHP cần extension curl và kết nối HTTPS tới `api.openai.com`. Khởi động lại PHP server nếu cấu hình môi trường được nạp lúc khởi động. Không đưa khóa vào JavaScript, URL, Git hay nội dung chat. `.env.example` chỉ có giá trị rỗng/mặc định tắt.

Adapter dùng `/v1/responses`, `store:false`, `text.format.type:json_object`; tham khảo [tài liệu JSON mode chính thức](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses). Bật cấu hình chưa đồng nghĩa mọi câu hỏi sẽ gọi API: chỉ câu chưa được quy tắc nhận diện, người dùng đã đồng ý qua checkbox, và vượt qua bộ lọc nhạy cảm mới gửi đi. Không gửi bản ghi database, dữ liệu mượn/phạt hoặc toàn bộ cuộc hội thoại. Bộ lọc từ khóa giảm rủi ro nhưng không bảo đảm nhận diện mọi dữ liệu cá nhân do người dùng tự nhập; đồng ý vẫn cần thiết.

API chỉ phân loại `search`/`recommend` và từ khóa tối đa 160 ký tự. Kết quả sách luôn truy vấn MySQL; dữ liệu SQL, quyền hay câu trả lời nghiệp vụ do API đưa ra không được dùng. Timeout kết nối 1 giây/tổng 3 giây; giới hạn đầu vào 1.000 ký tự, đầu ra 192 token, response 64 KB. Giới hạn 6 lần/IP/phút, 100 lần/IP/ngày và 100 lần/database/ngày; lần thử thất bại cũng tính. Chi phí tùy model; đặt thêm budget tại nhà cung cấp. Thiếu khóa/curl, timeout, HTTP lỗi hay JSON sai đều quay về trợ lý nội bộ.

Kiểm tra live sau khi cấu hình: checkbox AI xuất hiện, thử câu tìm kiếm chưa được rule nhận diện có đồng ý, xác minh kết quả vẫn là sách MySQL, thử ngắt kết nối để kiểm tra fallback. Không đánh dấu live PASS chỉ bằng test mock. Test hiện tại đã kiểm tra thiếu khóa, transport ném lỗi và kết quả phân loại không hợp lệ; chưa kiểm tra timeout mạng OpenAI thực tế.
