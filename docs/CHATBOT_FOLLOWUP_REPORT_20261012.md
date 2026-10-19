# Chatbot — đối chiếu yêu cầu gửi lại ngày 12/10/2026

Đọc đầy đủ attachment 72a4ca37-afc2-43a5-82ed-766425deef7c. Phạm vi tương ứng module chatbot đã triển khai; tận dụng nguyên kiến trúc và tài liệu hiện có, không tạo module trùng.

Phát hiện và sửa: câu “Tổng phí phạt còn nợ là bao nhiêu?” trước rơi vào unknown. IntentClassifier nay nhận personal_fines và đi qua ChatAuthorizationService/FineService để chỉ đọc phí của độc giả đang đăng nhập. Không tính phí bằng quy tắc mới và không thu tiền qua chat.

Thêm regression 9 câu hỏi bắt buộc về phí còn nợ, hạn trả, đã trả hết, lịch sử, quá hạn, gia hạn, báo cáo Admin/Thủ thư, cùng 1 kiểm tra service phí cá nhân. Thực thi lần này: MySQL chatbot 48 assertions PASS; isolation hai độc giả/phí/thu hồi quyền 12 PASS; Chrome chatbot 65 PASS; lint 2 file sửa PASS; git diff --check sạch. Main 25/25 bảng không đổi. Bằng chứng độc lập: storage/backups/chatbot-followup-20261012, gồm backup trước sửa, mysql-results.json, isolation-results.json, browser-results.json và các log.

273 checks core, 140 file lint và 14 checks phân trang thuộc các lần chạy trước trong ngày, không chạy lại và không tính thành test mới trong lần đối chiếu này.

Bàn giao hiện có: widget công khai và tài khoản, search/gợi ý MySQL, tồn kho, ngữ cảnh phiên, policy thật, tra cứu cá nhân, phân quyền báo cáo, CSRF/rate/input/XSS, fallback; docs/CHATBOT_ARCHITECTURE.md, CHATBOT_API_SETUP.md, CHATBOT_DEMO.md. Chỉ hướng dẫn/link cho thao tác ghi. API live và timeout mạng nhà cung cấp vẫn BLOCKED vì chưa có khóa/model; test transport mock không thay bằng chứng gọi live. Không tuyên bố chatbot rule-based là đã tích hợp AI live. Không reset DB/push hoặc đổi design system.
