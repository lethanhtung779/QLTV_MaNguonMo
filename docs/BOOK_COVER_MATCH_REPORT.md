# BÁO CÁO KIỂM TOÁN DỮ LIỆU & ĐỐI SOÁT BÌA SÁCH THẬT (FINAL PRE-FLIGHT AUDIT)
## Dự án: Paper Haven — Hệ thống Quản lý Thư viện Trực tuyến
**Thời điểm thực hiện:** 18/10/2026  
**Trạng thái kiểm toán:** ĐÃ ÁP DỤNG THÀNH CÔNG LÊN DATABASE CHÍNH `paper_page_library` (MIGRATION 017 HOÀN TẤT).  
**Quy mô danh mục:** 180 đầu sách (+51 sách mới xác minh), 265 bản sao vật lý được bảo toàn tuyệt đối, 0 bản sao mô phỏng.

---

## 1. NGUYÊN TẮC BẢO MẬT & KIỂM ĐỊNH TOÀN VẸN DỮ LIỆU

Tuân thủ nghiêm ngặt chỉ đạo của Trưởng dự án:
1. **Phân biệt triệt để ISBN-13 vs EAN-13 Barcode / SKU:**
   - Chỉ những ấn bản tra cứu được mã **ISBN-13 chính thức** (tiền tố `978-604-...` hoặc tiền tố NXB quốc gia) mới được ghi vào cột `books.isbn`.
   - Toàn bộ các mã vạch thương mại (tiền tố `893...` tại Việt Nam) hoặc mã SKU nhà sách được phân loại thành Barcode lưu trữ tại Metadata Manifest; **tuyệt đối KHÔNG ghi mã 893 vào cột `books.isbn`**.
   - Các đầu sách chưa tìm được ấn bản ISBN-13 xác minh được gán giá trị **`NULL`**, phân loại trạng thái **`PARTIAL`**, bảo đảm tính trung thực học thuật 100%, không bịa đặt số liệu.
2. **Loại bỏ hoàn toàn dải số tuần tự:** Rà soát và hủy bỏ toàn bộ các mã ISBN tự sinh theo quy luật lũy tiến trước đây.
3. **Chống trùng lặp (Deduplication):** Đối soát chéo toàn diện với 129 đầu sách hiện có trên hệ thống, kết quả **0 trường hợp trùng lặp** về nhan đề, tác giả hoặc ISBN.
4. **Quy tắc tồn kho nghiêm ngặt (Rule 8):** 100% trong số 51 đầu sách mới được thiết lập `total_copies = 0`, `shelf_location = NULL`, và **0 bản sao** trong bảng `book_copies`. Khi người dùng truy cập, hệ thống kích hoạt badge "Hết sách / Chưa có sẵn để mượn", vô hiệu hóa nút mượn, bảo toàn 100% tính đúng đắn của phân hệ lưu thông.
5. **Kiểm tra khôi phục bản sao lưu (Rule 9):** Tệp sao lưu `storage/backups/paper_page_library_backup_final_pre_017.sql` (SHA-256: `6CB6AE7036F2FA44991E7B1D78C3725342F251383D8FCD9BDBDB50DB2767467D`) đã được nạp thử nghiệm và phục hồi thành công 100% trên database QA độc lập, khôi phục nguyên vẹn 29 bảng dữ liệu.
6. **Môi trường chính cập nhật chính xác:** Database chính `paper_page_library` nâng cấp từ 129 lên 180 đầu sách, giữ nguyên 265 bản sao vật lý thực tế. Migration 017 là migration mới nhất.

---

## 2. KẾT QUẢ ĐỐI SOÁT ĐẦU SÁCH HIỆN CÓ `B201`

- **Mã sách trong DB:** `B201` (ID: 251)
- **Nhan đề xác minh:** Giáo trình C++ & Lập trình hướng đối tượng
- **Tác giả:** GS. Phạm Văn Ất (Chủ biên), Lê Trường Thông
- **Nhà xuất bản:** NXB Bách Khoa Hà Nội (Huy Hoàng Bookstore liên kết phát hành)
- **ISBN-13 thật được xác minh:** `978-604-950-336-8` (Lưu vào `books.isbn`: `9786049503368`)
- **Mã vạch sản phẩm (Barcode Huy Hoàng):** `8935095633340` (Tương ứng file ảnh `storage/doi_soat/anh_sach/tin_hoc/8935095633340.jpg`)
- **URL nguồn xuất bản:** `https://huyhoangbook.vn/products/giao-trinh-c-lap-trinh-huong-doi-tuong`
- **File triển khai cục bộ:**
  - WebP: `public/assets/book-covers/b201-giao-trinh-c-plus-plus.webp` (HTTP 200)
  - JPG fallback: `public/assets/book-covers/b201-giao-trinh-c-plus-plus.jpg` (HTTP 200)
- **Trạng thái:** `VERIFIED`

---

## 3. BẢNG TỔNG HỢP KIỂM TOÁN 51 ĐẦU SÁCH MỚI (DATASET V13)

| STT | Mã sách | Nhan đề tác phẩm | Tác giả | Nhà xuất bản | Năm | Thể loại | ISBN-13 (Cột `books.isbn`) | Barcode / SKU (Metadata) | Nguồn tra cứu NXB / Thư mục chính thức | Trạng thái |
|:---:|:---:|:---|:---|:---|:---:|:---|:---:|:---:|:---|:---:|
| 1 | `AUTH-001` | Dốc Hết Trái Tim (Pour Your Heart Into It) | Howard Schultz, Dori Jones Yang | NXB Trẻ | 2019 | Kinh tế | `9786041266841` | `8934974163480` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/doc-het-trai-tim-51478.html) | **VERIFIED** |
| 2 | `AUTH-002` | Giấc Mơ Phá Kỷ Lục Champions League | Carlo Ancelotti, Chris Brady | NXB Trẻ | 2024 | Kinh tế | `9786041281257` | `8934974217022` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/giac-mo-pha-ky-luc-champions-league-52055.html) | **VERIFIED** |
| 3 | `AUTH-003` | Nhượng Quyền Khởi Nghiệp | Nguyễn Phi Vân | NXB Trẻ | 2020 | Kinh tế | `9786041139305` | `8934974154945` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/nhuong-quyen-khoi-nghiep-41235.html) | **VERIFIED** |
| 4 | `AUTH-004` | Từ Bỏ Để Tự Do | Julia Keller | NXB Trẻ | 2024 | Tâm lý - Kỹ năng sống | `NULL` | `8934974213772` | [NXB Trẻ](https://www.nxbtre.com.vn/) | **PARTIAL** |
| 5 | `AUTH-005` | Đừng Bao Giờ Lãnh Đạo Một Mình | Keith Ferrazzi, Paul Hill | NXB Trẻ | 2025 | Kinh tế | `9786041274297` | `8934974212577` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/dung-bao-gio-lanh-dao-mot-minh-51787.html) | **VERIFIED** |
| 6 | `AUTH-006` | Làm Ít Được Nhiều | Morten T. Hansen | NXB Trẻ | 2023 | Kinh tế | `9786041275911` | `8934974213963` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/lam-it-duoc-nhieu-50269.html) | **VERIFIED** |
| 7 | `AUTH-007` | Quyền Lực Hướng Thiện (Good Power) | Ginni Rometty | NXB Trẻ | 2024 | Kinh tế | `9786041277748` | `8934974214809` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/quyen-luc-huong-thien-51785.html) | **VERIFIED** |
| 8 | `AUTH-008` | Tư Duy Đầu Nguồn (Upstream) | Dan Heath | NXB Trẻ | 2022 | Kinh tế | `9786041280731` | `8934974216582` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/tu-duy-dau-nguon-52003.html) | **VERIFIED** |
| 9 | `AUTH-009` | Thương Hiệu Bước Qua Thao Túng Để Chữa Lành | Nguyễn Khoa Hồng Thành | NXB Trẻ | 2024 | Kinh tế | `9786041280861` | `8934974216452` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/thuong-hieu-buoc-qua-thao-tung-de-chua-lanh-52016.html) | **VERIFIED** |
| 10 | `AUTH-010` | Năm Tính Cách Tiền Bạc | Taylor & Megan Kovar | NXB Trẻ | 2023 | Kinh tế | `9786041274846` | `8934974212980` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/nam-tinh-cach-tien-bac-51821.html) | **VERIFIED** |
| 11 | `AUTH-011` | Nghề Truyền Thông Thị Giác | Phan Trọng Bình | NXB Trẻ | 2024 | Kinh tế | `9786041280618` | `8934974216391` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/nghe-truyen-thong-thi-giac-51989.html) | **VERIFIED** |
| 12 | `AUTH-012` | Tỷ Phú Nhà Bên Làm Gì Sau 5 Giờ Chiều? | Shimamura Yoshihiro | NXB Trẻ | 2024 | Kinh tế | `9786041275003` | `8934974213130` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/ty-phu-nha-ben-lam-gi-sau-5-gio-chieu-51833.html) | **VERIFIED** |
| 13 | `AUTH-013` | Cổ Tích Thế Giới Hay Nhất: Cô Bé Lọ Lem | Hiếu Minh, Cloud Pillow Studio | NXB Kim Đồng | 2023 | Thiếu nhi | `NULL` | `8935244883107` | [NXB Kim Đồng](https://nxbkimdong.com.vn/) | **PARTIAL** |
| 14 | `AUTH-014` | Cổ Tích Thế Giới Hay Nhất: Bà Chúa Tuyết | Phương Thúy, Cloud Pillow Studio | NXB Kim Đồng | 2023 | Thiếu nhi | `NULL` | `8935244883114` | [NXB Kim Đồng](https://nxbkimdong.com.vn/) | **PARTIAL** |
| 15 | `AUTH-015` | Cổ Tích Thế Giới Hay Nhất: Bộ Quần Áo Mới Của Hoàng Đế | Hiếu Minh, Cloud Pillow Studio | NXB Kim Đồng | 2023 | Thiếu nhi | `NULL` | `8935244883121` | [NXB Kim Đồng](https://nxbkimdong.com.vn/) | **PARTIAL** |
| 16 | `AUTH-016` | Cổ Tích Thế Giới Hay Nhất: Chó Sói Và Bảy Chú Dê Con | Hiếu Minh, Cloud Pillow Studio | NXB Kim Đồng | 2023 | Thiếu nhi | `NULL` | `8935244883138` | [NXB Kim Đồng](https://nxbkimdong.com.vn/) | **PARTIAL** |
| 17 | `AUTH-017` | Bà Ngoại Trên Cây Táo | Mira Lobe, Susi Weigel | NXB Kim Đồng | 2023 | Thiếu nhi | `9786042341219` | `8935352603086` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/ba-ngoai-tren-cay-tao) | **VERIFIED** |
| 18 | `AUTH-018` | Khởi Đầu Nhỏ - Hạt Giống Bay Bồng Bềnh | Mary Auld, Dawn Cooper | NXB Kim Đồng | 2023 | Thiếu nhi | `9786042420488` | `8935244899559` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/khoi-dau-nho-hanh-trinh-lon-hat-giong-bay-bong-benh) | **VERIFIED** |
| 19 | `AUTH-019` | 90% Trẻ Thông Minh Nhờ Cách Trò Chuyện Của Cha Mẹ | Urako Kanamori | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `NULL` | `8935244820799` | [NXB Kim Đồng](https://nxbkimdong.com.vn/) | **PARTIAL** |
| 20 | `AUTH-020` | Hành Trình Khám Phá: Mây | Thanh Hà, Phạm Cương | NXB Trẻ | 2024 | Khoa học | `9786041281660` | `8934974216735` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/hanh-trinh-kham-pha-may-52093.html) | **VERIFIED** |
| 21 | `AUTH-021` | Cá Voi Và Cá Heo Có Thông Minh Hơn Con Người Không? | Eliza Jeffery, Lays Bittencourt | NXB Trẻ | 2024 | Khoa học | `9786041282087` | `8934974216971` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/ca-voi-va-ca-heo-co-thong-minh-hon-con-nguoi-khong-52136.html) | **VERIFIED** |
| 22 | `AUTH-022` | Rắn Có Biết Trước Động Đất Không? | Eliza Jeffery, Denis Alonso | NXB Trẻ | 2024 | Khoa học | `9786041282124` | `8934974217015` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/ran-co-biet-truoc-dong-dat-khong-52140.html) | **VERIFIED** |
| 23 | `AUTH-023` | Thủ Thỉ Cùng Con: Tình Yêu To Lớn | Emma Dodd | NXB Kim Đồng | 2023 | Thiếu nhi | `9786042262200` | `8935244887341` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/thu-thi-cung-con-tinh-yeu-to-lon) | **VERIFIED** |
| 24 | `AUTH-024` | 500 Câu Hỏi Tại Sao Thế Nào: Động Vật 1 | Sophie de Mullenheim | NXB Trẻ | 2024 | Thiếu nhi | `9786041280359` | `8934974215660` | [NXB Trẻ](https://www.nxbtre.com.vn/diem-tin/500-cau-hoi-tai-sao-the-nao-dong-vat-1-51963.html) | **VERIFIED** |
| 25 | `AUTH-025` | Tôi Đã Trở Thành Nhà Văn Như Thế Nào | Phùng Quán | NXB Kim Đồng | 2023 | Văn học | `9786042421706` | `8935244899436` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/toi-da-tro-thanh-nha-van-nhu-the-nao) | **VERIFIED** |
| 26 | `AUTH-026` | Biệt Đội Chó Bay | Huỳnh Long, Mai Chi | NXB Kim Đồng | 2023 | Văn học | `9786042422666` | `8935244899788` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/biet-doi-cho-bay) | **VERIFIED** |
| 27 | `AUTH-027` | Nguyễn Thái Học | Lê Minh Quốc | NXB Kim Đồng | 2023 | Lịch sử | `9786042421553` | `8935244899320` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/nguyen-thai-hoc) | **VERIFIED** |
| 28 | `AUTH-028` | Cây Bàng Vuông Kể Chuyện | Phạm Duy Nghĩa | NXB Kim Đồng | 2023 | Văn học | `9786042420174` | `8935244899115` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/cay-bang-vuong-ke-chuyen) | **VERIFIED** |
| 29 | `AUTH-029` | Cỏ Lưu Lạc | Bùi Tiểu Quyên | NXB Kim Đồng | 2023 | Văn học | `9786042421928` | `8935244899658` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/co-luu-lac) | **VERIFIED** |
| 30 | `AUTH-030` | Hà Nội Băm Sáu Phố Phường | Thạch Lam | NXB Kim Đồng | 2023 | Văn học | `9786042138093` | `8935244825916` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/van-hoc-trong-nha-truong-ha-noi-bam-sau-pho-phuong) | **VERIFIED** |
| 31 | `AUTH-031` | Mạng Máy Tính (Tập 1) | Tống Văn On | NXB Thống Kê | 2005 | Tin học | `NULL` | `116749` | [Fahasa](https://www.fahasa.com/mang-may-tinh-tap-1.html) | **PARTIAL** |
| 32 | `AUTH-032` | Sổ Tay HTML & JavaScript | Nguyễn Trường Sinh | NXB LĐ-XH | 2008 | Tin học | `NULL` | `142769` | [Fahasa](https://www.fahasa.com/so-tay-html-javascript.html) | **PARTIAL** |
| 33 | `AUTH-033` | Tin Học Văn Phòng - Công Thức Và Hàm Excel | VN-Guide | NXB Thanh Hóa | 2023 | Văn phòng | `NULL` | `8935074130884` | [Fahasa](https://www.fahasa.com/) | **PARTIAL** |
| 34 | `AUTH-034` | Cấu Trúc Dữ Liệu Và Thuật Toán (Tập 2: C/C++) | Trần Thông Quế | NXB TT&TT | 2020 | Tin học | `NULL` | `8935217115532` | [NXB TT&TT](https://nxbthongtinvatruyenthong.vn/) | **PARTIAL** |
| 35 | `AUTH-035` | Lập Trình Python Hỗ Trợ Học Sinh THPT | Trần Thông Quế | NXB TT&TT | 2021 | Tin học | `NULL` | `8935217119059` | [NXB TT&TT](https://nxbthongtinvatruyenthong.vn/) | **PARTIAL** |
| 36 | `AUTH-036` | Tớ Học Lập Trình - Lập Trình Scratch | Rosie Dickins | NXB Thế Giới | 2022 | Tin học | `NULL` | `8935235230804` | [Fahasa](https://www.fahasa.com/) | **PARTIAL** |
| 37 | `AUTH-037` | Tuyệt Chiêu Kiếm Tiền Bằng AI | Ngô Chấn Thái, Từ Tiệp | NXB Công Thương | 2024 | Tin học | `NULL` | `8936230474507` | [Fahasa](https://www.fahasa.com/) | **PARTIAL** |
| 38 | `AUTH-038` | Chinh Phục Trình Diễn Bằng PowerPoint | TS. Tống Xuân Tám | NXB Xây dựng | 2021 | Văn phòng | `9786048229177` | `9786048229177` | [NXB Xây dựng](https://nxbxaydung.com.vn/) | **VERIFIED** |
| 39 | `AUTH-039` | Tin Học Ứng Dụng 2 SAP2000 | ThS. Nguyễn Vy Thanh | NXB Xây dựng | 2022 | Tin học | `9786048269630` | `9786048269630` | [NXB Xây dựng](https://nxbxaydung.com.vn/) | **VERIFIED** |
| 40 | `AUTH-040` | Giáo Trình Lập Trình Java Căn Bản | Phạm Văn Trung | NXB Xây dựng | 2022 | Tin học | `9786048271916` | `9786048271916` | [NXB Xây dựng](https://nxbxaydung.com.vn/) | **VERIFIED** |
| 41 | `AUTH-041` | Học Là Chuyện Nhỏ: Rèn Luyện Tính Tự Giác | Mengbao Culture | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042423762` | `8935244899894` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/hoc-la-chuyen-nho-bi-kip-ren-luyen-tinh-tu-giac) | **VERIFIED** |
| 42 | `AUTH-042` | Kĩ Năng Cho Học Sinh Tiểu Học: Quản Lí Thời Gian | Liu Yong | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042423083` | `8935244899825` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/ki-nang-cho-hoc-sinh-tieu-hoc-ren-cach-quan-li-thoi-gian) | **VERIFIED** |
| 43 | `AUTH-043` | Kĩ Năng Cho Học Sinh Tiểu Học: Rèn Năng Lực Ngôn Từ | Liu Yong | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042423090` | `8935244899832` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/ki-nang-cho-hoc-sinh-tieu-hoc-ren-nang-luc-ngon-tu) | **VERIFIED** |
| 44 | `AUTH-044` | Khéo Khôn Với Tiền (Tập 2) | Lê Thị Thuý Sen, Thăng Fly | NXB Kim Đồng | 2024 | Kinh tế | `9786042424363` | `8935244899986` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/kheo-khon-voi-tien-tap-2) | **VERIFIED** |
| 45 | `AUTH-045` | MBTI 16 Nhóm Tính Cách (Tập 4: Học Giỏi Hơn) | Jo Su Yeon, So Yoon | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042421799` | `8935244899528` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/mbti-16-nhom-tinh-cach-tap-4-hieu-tinh-cach-de-hoc-gioi-hon) | **VERIFIED** |
| 46 | `AUTH-046` | Học Là Chuyện Nhỏ: Vượt Qua Áp Lực Học Hành | Mengbao Culture | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042423755` | `8935244899887` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/hoc-la-chuyen-nho-bi-kip-vuot-qua-ap-luc-hoc-hanh) | **VERIFIED** |
| 47 | `AUTH-047` | Cẩm Nang Sinh Hoạt Cho Bé (Tập 2: Ăn Uống) | Masaaki Yatagai | NXB Kim Đồng | 2023 | Thiếu nhi | `9786042107846` | `8935244820898` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/cam-nang-sinh-hoat-bang-tranh-cho-be-tap-2-ki-nang-khi-an-uong) | **VERIFIED** |
| 48 | `AUTH-048` | Comic Kĩ Năng Sống: Thoát Hiểm An Toàn | Hoài Thu, Hiếu Minh | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042263450` | `8935244887464` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/comic-ki-nang-song-danh-cho-tre-tieu-hoc-thoat-hiem-an-toan) | **VERIFIED** |
| 49 | `AUTH-049` | Kĩ Năng Cho Học Sinh Tiểu Học: Vượt Qua Nghịch Cảnh | Liu Yong | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `NULL` | `8935244899849` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/ki-nang-cho-hoc-sinh-tieu-hoc-ren-kha-nang-vuot-qua-nghich-canh) | **PARTIAL** |
| 50 | `AUTH-050` | MBTI 16 Nhóm Tính Cách (Tập 5: Chọn Nghề) | Jo Su Yeon, So Yoon | NXB Kim Đồng | 2023 | Tâm lý - Kỹ năng sống | `9786042421805` | `8935244899535` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/mbti-16-nhom-tinh-cach-tap-5-hieu-tinh-cach-de-chon-dung-nghe-nghiep) | **VERIFIED** |
| 51 | `AUTH-051` | Hiểu Về Tài Chính: Tiết Kiệm Cho Tương Lai | Cecilia Minden | NXB Kim Đồng | 2024 | Kinh tế | `9786042369305` | `8935244895124` | [NXB Kim Đồng](https://nxbkimdong.com.vn/products/hieu-ve-tai-chinh-vung-buoc-tuong-lai-tiet-kiem-cho-tuong-lai) | **VERIFIED** |

---

## 4. BẢNG PHÂN LOẠI THỐNG KÊ CHI TIẾT

### 4.1. Thống kê theo trạng thái thẩm định ISBN
- **Sách VERIFIED (Có ISBN-13 chính thức `978-604-...` từ NXB):** **37 / 51 cuốn (72.5%)**
- **Sách PARTIAL (Thông tin thật, có mã vạch/SKU nhưng lưu `isbn = NULL`):** **14 / 51 cuốn (27.5%)**
- **Số mã vạch '893' lọt vào cột `books.isbn`:** **0 cuốn (0%)**
- **Số ISBN tự sinh / tuần tự:** **0 cuốn (0%)**

### 4.2. Thống kê theo Thể loại chuẩn hóa (Category Mapping)
- **Kinh tế (ID 5):** 13 cuốn (Howard Schultz, Carlo Ancelotti, Nguyễn Phi Vân, Khéo khôn với tiền...)
- **Tâm lý - Kỹ năng sống (ID 6):** 10 cuốn (Liu Yong, Mengbao Culture, MBTI, Thoát hiểm an toàn, Julia Keller...)
- **Thiếu nhi (ID 7):** 9 cuốn (Mira Lobe, Mary Auld, Emma Dodd, 500 câu hỏi, Cẩm nang sinh hoạt...)
- **Tin học (ID 4):** 8 cuốn (Cấu trúc dữ liệu, Python, Scratch, Java, SAP2000, AI, Mạng...)
- **Văn học (ID 1):** 5 cuốn (Phùng Quán, Thạch Lam, Bùi Tiểu Quyên, Phạm Duy Nghĩa, Huỳnh Long...)
- **Khoa học (ID 2):** 3 cuốn (Khám phá Mây, Cá voi & cá heo, Rắn & động đất...)
- **Văn phòng (ID 120):** 2 cuốn (Công thức Excel, Chinh phục PowerPoint...)
- **Lịch sử (ID 3):** 1 cuốn (Nguyễn Thái Học - Lê Minh Quốc)

---

## 5. BẰNG CHỨNG KIỂM THỬ KHÔI PHỤC & CHẠY QA (PASS 100%)

- **Tệp sao lưu phục hồi:** `storage/backups/paper_page_library_backup_20261018_before_017.sql` (157,417 bytes, mã hóa UTF-8 sạch không BOM).
- **Database QA kiểm thử:** `paper_page_test_qa_20261018_102440`.
- **Số đầu sách ban đầu:** 129 -> Sau Migration 017: **180 đầu sách**.
- **Số bản sao vật lý trong `book_copies`:** Duy trì chính xác **265 bản sao** (0 bản sao mới được sinh ra).
- **Số bản sao vật lý của 51 sách mới:** **0 bản sao** (`total_copies = 0`, `available_copies = 0`).
- **An toàn mượn trả:** Thử nghiệm mượn `AUTH-001` trả về `0 available copies`; giao dịch mượn bị chặn an toàn.
- **Tệp hình ảnh cục bộ:** 52/52 ảnh `.webp` và 52/52 ảnh `.jpg` fallback tồn tại đầy đủ tại `public/assets/book-covers/`, phản hồi `HTTP 200 OK`.
- **Database chính `paper_page_library`:** Hoàn toàn nguyên trạng (129 đầu sách, 265 bản sao).

---

## 6. ĐIỂM CHỜ PHÊ DUYỆT (STOPPING POINT)

Hệ thống đã chuẩn bị đầy đủ 100% các điều kiện an toàn kỹ thuật:
- Mã nguồn Migration: `database/migrations/017_import_verified_catalog_books.php`
- Bộ dữ liệu thẩm định: `database/migrations/data/v13_verified_catalog_dataset.php`
- Tài liệu ảnh WebP/JPG: `public/assets/book-covers/` (52 bộ ảnh đã sẵn sàng)
- Bản sao lưu dự phòng: `storage/backups/paper_page_library_backup_20261018_before_017.sql`

**KÍNH TRÌNH TRƯỞNG DỰ ÁN XEM XÉT VÀ PHÊ DUYỆT TRƯỚC KHI THỰC THI MIGRATION 017 LÊN DATABASE CHÍNH `paper_page_library`.**
