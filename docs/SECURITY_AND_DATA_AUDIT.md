# Paper Haven — bảo mật & kiểm toán dữ liệu (18/10/2026)

## Kết luận có thể xác nhận

- Báo cáo Antigravity gần nhất ghi nhận bộ lọc `DEMO-` và `DEMO_FIXTURE:` đã hoạt động trên trang thống kê và sáu loại CSV. Bộ lọc **không thể** chứng minh mọi bản ghi còn lại đều có nguồn gốc xác thực hay đều là giao dịch thật.
- Báo cáo Antigravity đã làm lộ mật khẩu Admin trực tiếp trong lệnh kiểm thử. Mật khẩu cũ **phải được thay trên máy người dùng**, kể cả khi đã sửa script. Không gửi mật khẩu mới qua ChatGPT/Antigravity/log.
- Chưa có quyền truy cập MySQL local và source mới nhất sau các lượt Antigravity, nên bản vá này chỉ bổ sung công cụ kiểm tra và sửa script rất hạn chế. **Không có chứng cứ** rằng mọi mật khẩu lịch sử đã được xóa hoặc dữ liệu đã được chứng thực.

## Những gì đã được lập trình

1. `tools/paper_haven_safety_audit.py`: quét source, in **vị trí** nguy cơ mà không xuất mật khẩu. Với `--apply`, chỉ thay thế mật khẩu Admin hardcode ở các câu lệnh login QA có ngữ cảnh đủ rõ, sao lưu file cũ ngoài project trước khi sửa. Script cố ý không đọc/không in `.env` hay mật khẩu hiện hành.
2. `tests/audit_data_provenance_readonly.php`: chỉ thực thi bốn câu `SELECT`. Xuất số tổng, demo được gắn nhãn, không gắn nhãn, ISBN/bìa thiếu và bản sao `available`/`pending_audit`, không tiết lộ PII độc giả. **Không tự gán VERIFIED cho bất kỳ bản ghi nào.**
3. `tools/apply_security_audit.ps1`: chép mới hai script và tài liệu vào dự án nếu chưa tồn tại; không chép đè các Controller/Repository/View, không thay đổi database, không chạy migration.

## Cách sử dụng nhanh trong PowerShell

Giải nén ZIP ra Desktop. Mở PowerShell ở gốc project đang chạy, rồi chạy:

```powershell
$patch = Join-Path ([Environment]::GetFolderPath('Desktop')) 'Paper_Haven_Security_Data_Audit_Patch'
& "$patch\tools\apply_security_audit.ps1" -ProjectRoot (Get-Location).Path -PatchRoot $patch
python tools/paper_haven_safety_audit.py --apply
& 'D:\1.Download\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' tests/audit_data_provenance_readonly.php
```

Nếu Python không hoạt động, dùng `py -3` thay cho `python`. Đừng gửi đầy đủ đầu ra từ file chứa thông tin nhạy cảm; script không xuất danh tính/mật khẩu.

## Điều phải làm thủ công với vai trò Admin

- Đăng nhập bằng tài khoản Admin đang dùng, đổi mật khẩu từ trang Hồ sơ/Đổi mật khẩu. Dùng mật khẩu mới đủ mạnh; không gửi cho AI hoặc lưu vào `scratch/*.py`.
- Nếu mật khẩu đã dùng ở dịch vụ khác, thay cả ở dịch vụ đó. Những lệnh đã ghi trong lịch sử terminal/Antigravity không thể thu hồi bằng cách sửa source.
- Không hardcode mật khẩu trong tests hoặc prompt. Với script đã chuyển sang biến môi trường, đặt `PAPER_HAVEN_QA_ADMIN_PASSWORD` chỉ trong phiên chạy QA và xoá ngay sau khi dùng. Tránh chạy QA ghi dữ liệu trên DB chính.
- Kiểm tra `.gitignore` có `.env`, `scratch/`, `storage/http_qa.json`, `storage/backups/`, file ZIP/backup. Không push Git khi còn dữ liệu nhạy cảm.

## Giới hạn quan trọng

- Kiểm tra không gắn `DEMO-` không có nghĩa là chứng minh thực tế. Nguồn xác thực sách cần đối chiếu ISBN, **ấn bản tương ứng**, đường dẫn xuất bản và quyền ảnh bìa. Nguồn web được gắn ở manifest cũng có thể chưa được xác minh độc lập.
- Dữ liệu giao dịch thật cần chứng từ hoặc lịch sử nghiệp vụ, không thể xác định từ tiền tố mã đơn thuần.
- Các bước này **không tác động** vào số sách, bản sao, độc giả, phiếu mượn hay lịch sử phí phạt. Thống kê trên dashboard khác chưa được chỉnh bằng bản vá này.
- Nếu công cụ quét không khớp các đoạn mật khẩu ở nhật ký AI (chúng có thể chỉ xuất hiện trong command log, không nằm trong file), kết quả 0 vấn đề không đồng nghĩa không từng bị lộ.
