import os
import sys
sys.stdout.reconfigure(encoding='utf-8')
import re
from PIL import Image

BOOKS_DATA = [
    # B201 match
    {
        "type": "match_existing",
        "book_code": "B201",
        "title": "Giáo trình C++ & Lập trình hướng đối tượng",
        "author": "GS. Phạm Văn Ất, Lê Trường Thông",
        "publisher": "NXB Bách Khoa Hà Nội",
        "publication_year": 2019,
        "isbn": "8935095633340",
        "category_id": 4, # Tin học
        "slug": "b201-giao-trinh-c-plus-plus",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8935095633340.jpg",
        "description": "Giáo trình nền tảng về ngôn ngữ C++ và phương pháp lập trình hướng đối tượng của GS. Phạm Văn Ất, chuẩn mực đào tạo kỹ sư công nghệ."
    },
    # 51 New Books:
    # --- KINH TẾ (12 books) ---
    {
        "type": "new_book",
        "book_code": "AUTH-001",
        "title": "Dốc Hết Trái Tim (Pour Your Heart Into It)",
        "author": "Howard Schultz, Dori Jones Yang",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2019,
        "isbn": "9786041151628",
        "category_id": 5,
        "slug": "auth-001-doc-het-trai-tim",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/copy_16_nxbtre_thumb_21112019_111109.jpg",
        "description": "Câu chuyện cách Starbucks xây dựng công ty bằng từng tách cà phê từ khởi đầu khiêm tốn đến thương hiệu đồ uống toàn cầu."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-002",
        "title": "Giấc Mơ Phá Kỷ Lục Champions League (The Dream)",
        "author": "Carlo Ancelotti, Chris Brady",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249769",
        "category_id": 5,
        "slug": "auth-002-giac-mo-pha-ky-luc-champions-league",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_03142026_021449.jpg",
        "description": "Tự truyện và bài học quản trị lãnh đạo đỉnh cao của HLV Carlo Ancelotti trong hành trình chinh phục đỉnh cao bóng đá châu Âu."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-003",
        "title": "Nhượng Quyền Khởi Nghiệp - Con Đường Ngắn Để Bước Ra Thế Giới",
        "author": "Nguyễn Phi Vân",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2020,
        "isbn": "9786041164826",
        "category_id": 5,
        "slug": "auth-003-nhuong-quyen-khoi-nghiep",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_04312026_113158.jpg",
        "description": "Cẩm nang thực chiến về mô hình kinh doanh nhượng quyền thương mại (Franchise) dành cho các doanh nhân và startup Việt Nam."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-004",
        "title": "Từ Bỏ Để Tự Do - Chiến Lược Rút Lui Để Tiến Xa Hơn",
        "author": "Julia Keller",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249110",
        "category_id": 5,
        "slug": "auth-004-tu-bo-de-tu-do",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_07022026_090259.jpg",
        "description": "Góc nhìn khoa học về nghệ thuật từ bỏ đúng lúc để tái cấu trúc nguồn lực và đạt được tự do trong sự nghiệp."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-005",
        "title": "Đừng Bao Giờ Lãnh Đạo Một Mình (Never Lead Alone)",
        "author": "Keith Ferrazzi, Paul Hill",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2025,
        "isbn": "9786041262010",
        "category_id": 5,
        "slug": "auth-005-dung-bao-gio-lanh-dao-mot-minh",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_08202025_082040.jpg",
        "description": "10 bước chuyển từ tinh thần lãnh đạo đơn độc sang tinh thần đồng đội gắn kết và cùng kiến tạo thành công."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-006",
        "title": "Làm Ít Được Nhiều - Bài Học Từ Những Người Làm Việc Hiệu Quả Nhất",
        "author": "Morten T. Hansen",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2023,
        "isbn": "9786041235120",
        "category_id": 5,
        "slug": "auth-006-lam-it-duoc-nhieu",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_08452025_084511.jpg",
        "description": "Nghiên cứu thực nghiệm kéo dài 5 năm đúc kết 7 thói quen giúp làm việc thông minh và đạt hiệu suất vượt trội."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-007",
        "title": "Quyền Lực Hướng Thiện (Good Power)",
        "author": "Ginni Rometty",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041248038",
        "category_id": 5,
        "slug": "auth-007-quyen-luc-huong-thien",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_13562026_015640.jpg",
        "description": "Bài học lãnh đạo nhân văn của cựu CEO IBM về cách tạo ra bước chuyển đổi tích cực cho tập đoàn công nghệ hàng đầu."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-008",
        "title": "Tư Duy Đầu Nguồn - Giải Quyết Vấn Đề Từ Trong Trứng Nước (Upstream)",
        "author": "Dan Heath",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2022,
        "isbn": "9786041208940",
        "category_id": 5,
        "slug": "auth-008-tu-duy-dau-nguon",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_15302026_103024.jpg",
        "description": "Phương pháp tiếp cận thượng nguồn giúp giải quyết gốc rễ các vấn đề nan giải trong quản trị và đời sống xã hội."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-009",
        "title": "Thương Hiệu Bước Qua Thao Túng Để Chữa Lành",
        "author": "Nguyễn Khoa Hồng Thành",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249554",
        "category_id": 5,
        "slug": "auth-009-thuong-hieu-buoc-qua-thao-tung",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_15372026_103701.jpg",
        "description": "Ứng dụng tâm lý học chiều sâu và hệ mẫu hình cổ điển trong việc xây dựng thương hiệu chân thật, bền vững."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-010",
        "title": "Năm Tính Cách Tiền Bạc (The 5 Money Personalities)",
        "author": "Taylor & Megan Kovar",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2023,
        "isbn": "9786041231894",
        "category_id": 5,
        "slug": "auth-010-nam-tinh-cach-tien-bac",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_24362025_093634.jpg",
        "description": "Nhận diện 5 loại tính cách tài chính cá nhân để thấu hiểu cách tiêu dùng và lập kế hoạch tài chính hòa hợp."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-011",
        "title": "Nghề Truyền Thông Thị Giác - Truyền Sao Cho Thông?",
        "author": "Phan Trọng Bình",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041248885",
        "category_id": 5,
        "slug": "auth-011-nghe-truyen-thong-thi-giac",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_24392026_113944.jpg",
        "description": "Tác phẩm thực tiễn giải mã ngôn ngữ thiết kế hình ảnh và truyền thông thị giác dành cho người làm sáng tạo nội dung."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-012",
        "title": "Tỷ Phú Nhà Bên Làm Gì Sau 5 Giờ Chiều?",
        "author": "Shimamura Yoshihiro",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041247925",
        "category_id": 5,
        "slug": "auth-012-ty-phu-nha-ben",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_29102025_041022.jpg",
        "description": "Kinh nghiệm tận dụng thời gian sau giờ làm việc để xây dựng cộng đồng và kiến tạo mô hình kinh doanh khởi nghiệp tự chủ."
    },

    # --- THIẾU NHI (12 books) ---
    {
        "type": "new_book",
        "book_code": "AUTH-013",
        "title": "Cổ Tích Thế Giới Hay Nhất: Cô Bé Lọ Lem",
        "author": "Hiếu Minh, Cloud Pillow Studio",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301121",
        "category_id": 7,
        "slug": "auth-013-co-tich-co-be-lo-lem",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/1af1d946-7619-4c27-bdc0-5156181367cf.jpg",
        "description": "Truyện cổ tích thiếu nhi kinh điển ngợi ca lòng nhân ái, với tranh minh họa màu nước hiện đại rực rỡ."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-014",
        "title": "Cổ Tích Thế Giới Hay Nhất: Bà Chúa Tuyết",
        "author": "Phương Thúy, Cloud Pillow Studio",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301138",
        "category_id": 7,
        "slug": "auth-014-co-tich-ba-chua-tuyet",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/4da9362b-869d-4969-b3ee-bdd7b2627959.jpg",
        "description": "Tác phẩm cổ tích Andersen về hành trình quả cảm vượt qua giá lạnh và thử thách để cứu bạn của cô bé Gerda."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-015",
        "title": "Cổ Tích Thế Giới Hay Nhất: Bộ Quần Áo Mới Của Hoàng Đế",
        "author": "Hiếu Minh, Cloud Pillow Studio",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301145",
        "category_id": 7,
        "slug": "auth-015-co-tich-bo-quan-ao-moi-cua-hoang-de",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/58b95a79-8de9-4650-b588-1bb8760082a2.webp",
        "description": "Câu chuyện ngụ ngôn hài hước và sâu cay của Andersen giáo dục lòng dũng cảm và tính trung thực ở trẻ thơ."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-016",
        "title": "Cổ Tích Thế Giới Hay Nhất: Chó Sói Và Bảy Chú Dê Con",
        "author": "Hiếu Minh, Cloud Pillow Studio",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301152",
        "category_id": 7,
        "slug": "auth-016-co-tich-cho-soi-va-bay-chu-de-con",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/dfe8afd1-fdfd-40a0-a7c4-a41d79b5cfd7.jpg",
        "description": "Bài học an toàn và cảnh giác đầu đời cho bé trước những mưu mẹo nguy hiểm khi ở nhà một mình."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-017",
        "title": "Bà Ngoại Trên Cây Táo (Die Omama im Apfelbaum)",
        "author": "Mira Lobe, Susi Weigel",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042298919",
        "category_id": 7,
        "slug": "auth-017-ba-ngoai-tren-cay-tao",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/8277c970-2ec6-4887-a8e7-63a40781765f.jpg",
        "description": "Tác phẩm văn học thiếu nhi kinh điển của Áo đạt giải thưởng Sách Quốc gia về tình thương gia đình và trí tưởng tượng ấm áp."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-018",
        "title": "Khởi Đầu Nhỏ - Hành Trình Lớn: Hạt Giống Bay Bồng Bềnh",
        "author": "Mary Auld, Dawn Cooper",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042295628",
        "category_id": 7,
        "slug": "auth-018-hat-giong-bay-bong-benh",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/20059183-d57e-4dda-ac8f-89d33e06601f.webp",
        "description": "Sách tranh khoa học tự nhiên sinh động giải thích cơ chế phát tán hạt giống và vòng tuần hoàn của cây cỏ."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-019",
        "title": "90% Trẻ Thông Minh Nhờ Cách Trò Chuyện Đúng Đắn Của Cha Mẹ",
        "author": "Urako Kanamori",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042293419",
        "category_id": 7,
        "slug": "auth-019-90-phan-tram-tre-thong-minh",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/ho-cach-tro-chuyen-bia-tb3-final-in-1-4652a90b10334642ac514df6f07e8a20-a22ca7f148da429aae122790de915d59-1d898388-e129-4375-a89f-61a643f51d90.webp",
        "description": "Cẩm nang giao tiếp giáo dục từ chuyên gia tâm lý Nhật Bản giúp phụ huynh khơi dậy sự thông minh và tự tin của trẻ."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-020",
        "title": "Hành Trình Khám Phá: Mây",
        "author": "Thanh Hà, Phạm Cương",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249684",
        "category_id": 7,
        "slug": "auth-020-hanh-trinh-kham-pha-may",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/nxbtre_thumb_06422026_044241.jpg",
        "description": "Sách tranh thiếu nhi giúp các em quan sát bầu trời, tìm hiểu khoa học khí tượng và các dạng mây lý thú."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-021",
        "title": "Em Bước Vào Thế Giới Khoa Học: Cá Voi Và Cá Heo Có Thông Minh Hơn Con Người Không?",
        "author": "Eliza Jeffery, Lays Bittencourt",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249707",
        "category_id": 7,
        "slug": "auth-021-ca-voi-va-ca-heo-co-thong-minh-khong",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/nxbtre_thumb_31492026_044918.jpg",
        "description": "Khám phá đại dương bao la và tìm hiểu về trí thông minh kỳ diệu của loài thú có vú thông minh bậc nhất dưới biển."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-022",
        "title": "Em Bước Vào Thế Giới Khoa Học: Rắn Có Biết Trước Động Đất Không?",
        "author": "Eliza Jeffery, Denis Alonso",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249714",
        "category_id": 7,
        "slug": "auth-022-ran-co-biet-truoc-dong-dat-khong",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/nxbtre_thumb_31552026_045506.jpg",
        "description": "Giải thích trực quan các giác quan đặc biệt của động vật và hiện tượng dự báo địa chấn trong giới sinh vật."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-023",
        "title": "Thủ Thỉ Cùng Con: Tình Yêu To Lớn",
        "author": "Emma Dodd",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042296014",
        "category_id": 7,
        "slug": "auth-023-thu-thi-cung-con-tinh-yeu-to-lon",
        "src_path": "storage/doi_soat/anh_sach/thieu_nhi/thu-thi-cung-con-tinh-yeu-to-lon-bia-52cd52ed7b5a4953897fd2c4381dd7ce-38985e1a-af8b-4008-b141-13b38963bd5b.webp",
        "description": "Lời thủ thỉ êm dịu trước giờ đi ngủ bồi dưỡng cảm xúc và tình yêu thương vô điều kiện giữa cha mẹ và con cái."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-024",
        "title": "500 Câu Hỏi Tại Sao Thế Nào: Động Vật 1",
        "author": "Sophie de Mullenheim, Amélie Falière",
        "publisher": "Nhà xuất bản Trẻ",
        "publication_year": 2024,
        "isbn": "9786041249448",
        "category_id": 7,
        "slug": "auth-024-500-cau-hoi-tai-sao-the-nao-dong-vat-1",
        "src_path": "storage/doi_soat/anh_sach/kinh_te/nxbtre_thumb_04132026_031337.jpg",
        "description": "Sách bách khoa mini trả lời hàng trăm câu hỏi ngộ nghĩnh về thế giới các loài động vật hoang dã."
    },

    # --- VĂN HỌC & LỊCH SỬ (6 books) ---
    {
        "type": "new_book",
        "book_code": "AUTH-025",
        "title": "Tôi Đã Trở Thành Nhà Văn Như Thế Nào",
        "author": "Phùng Quán",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042318020",
        "category_id": 1,
        "slug": "auth-025-toi-da-tro-thanh-nha-van-nhu-the-nao",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/0b8ab253-62dd-41a8-8a09-e986dabcfabc.webp",
        "description": "Những dòng hồi ức văn học xúc động về tuổi thơ nghèo khó và ngọn lửa đam mê trang viết của nhà văn Phùng Quán."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-026",
        "title": "Biệt Đội Chó Bay",
        "author": "Huỳnh Long, Mai Chi",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042316521",
        "category_id": 1,
        "slug": "auth-026-biet-doi-cho-bay",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/898250bb-ddfe-4ac5-99b9-30457cc61e92.jpg",
        "description": "Chuyện phiêu lưu giả tưởng của những chú cún biết bay bảo vệ hòa bình, ngập tràn tình bạn và lòng dũng cảm."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-027",
        "title": "Nguyễn Thái Học",
        "author": "Lê Minh Quốc",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042315890",
        "category_id": 3, # Lịch sử
        "slug": "auth-027-nguyen-thai-hoc",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/b0535f46-0598-4b0d-97e5-39ece5d8a86b.webp",
        "description": "Tiểu thuyết lịch sử tái hiện chân dung nhà cách mạng Nguyễn Thái Học và cuộc khởi nghĩa Yên Bái lẫm liệt."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-028",
        "title": "Cây Bàng Vuông Kể Chuyện",
        "author": "Phạm Duy Nghĩa",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042314954",
        "category_id": 1,
        "slug": "auth-028-cay-bang-vuong-ke-chuyen",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/cay-bang-vuong-ke-chuyen-bia.jpg",
        "description": "Tập truyện ngắn đậm chất trữ tình về thiên nhiên biển trời quê hương và tình cảm gắn kết với quần đảo Trường Sa."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-029",
        "title": "Cỏ Lưu Lạc",
        "author": "Bùi Tiểu Quyên",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042313100",
        "category_id": 1,
        "slug": "auth-029-co-luu-lac",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/d899856e-d185-43f2-96f8-9353afb63469.jpg",
        "description": "Hành trình phiêu lưu tìm về cội nguồn của chú vịt cỏ giữa thiên nhiên sông nước miền Tây Nam Bộ thanh bình."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-030",
        "title": "Hà Nội Băm Sáu Phố Phường",
        "author": "Thạch Lam",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042312219",
        "category_id": 1,
        "slug": "auth-030-ha-noi-bam-sau-pho-phuong",
        "src_path": "storage/doi_soat/anh_sach/van_hoc/van-hoc-trong-nha-truong-ha-noi-bam-sau-pho-phuong-bia-77a8d789a6704997975f92576442fb78-152e06a9-f049-43ab-a301-6bb8faaa2812.jpg",
        "description": "Tùy bút văn học kinh điển khắc họa nét đẹp văn hóa, phong vị ẩm thực tao nhã và tâm hồn người Hà Nội xưa."
    },

    # --- TIN HỌC & VĂN PHÒNG (10 books) ---
    {
        "type": "new_book",
        "book_code": "AUTH-031",
        "title": "Mạng Máy Tính (Tập 1)",
        "author": "Tống Văn On",
        "publisher": "Nhà xuất bản Thống Kê",
        "publication_year": 2005,
        "isbn": "9786048001117",
        "category_id": 4,
        "slug": "auth-031-mang-may-tinh-tap-1",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/116749.jpg",
        "description": "Giáo trình nền tảng về cấu trúc mạng máy tính, mô hình tham chiếu OSI và các giao thức truyền thông dữ liệu."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-032",
        "title": "Sổ Tay HTML & JavaScript",
        "author": "Nguyễn Trường Sinh",
        "publisher": "Nhà xuất bản Lao Động - Xã Hội",
        "publication_year": 2008,
        "isbn": "9786048002220",
        "category_id": 4,
        "slug": "auth-032-so-tay-html-javascript",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/142769.jpg",
        "description": "Sổ tay tra cứu nhanh cú pháp các phần tử HTML và lập trình tương tác client-side bằng ngôn ngữ JavaScript."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-033",
        "title": "Tin Học Văn Phòng - Công Thức Và Hàm Excel",
        "author": "VN-Guide",
        "publisher": "Nhà xuất bản Thanh Hóa",
        "publication_year": 2023,
        "isbn": "8935074130884",
        "category_id": 120, # Văn phòng
        "slug": "auth-033-cong-thuc-va-ham-excel",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8935074130884.jpg",
        "description": "Hướng dẫn ứng dụng các hàm thống kê, dò tìm logic trong Microsoft Excel giải quyết bài toán kế toán thực tế."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-034",
        "title": "Cấu Trúc Dữ Liệu Và Thuật Toán (Tập 2: Cài Đặt Trên C/C++)",
        "author": "Trần Thông Quế",
        "publisher": "Nhà xuất bản Thông tin và Truyền thông",
        "publication_year": 2020,
        "isbn": "8935217115532",
        "category_id": 4,
        "slug": "auth-034-cau-truc-du-lieu-thuat-toan-tap-2",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8935217115532.jpg",
        "description": "Phân tích và cài đặt các thuật toán cây, đồ thị, quy hoạch động chuyên sâu trên môi trường C/C++."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-035",
        "title": "Lập Trình Python Hỗ Trợ Học Sinh THPT Giải Bài Tập Tin Học",
        "author": "Trần Thông Quế",
        "publisher": "Nhà xuất bản Thông tin và Truyền thông",
        "publication_year": 2021,
        "isbn": "8935217119059",
        "category_id": 4,
        "slug": "auth-035-lap-trinh-python-thpt",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8935217119059.jpg",
        "description": "Tài liệu thực hành lập trình giải thuật Python cơ bản đến nâng cao bám sát chương trình phổ thông và bồi dưỡng tin học."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-036",
        "title": "Tớ Học Lập Trình - Làm Quen Với Lập Trình Scratch",
        "author": "Rosie Dickins, Louie Stowell, Jonathan Melmoth",
        "publisher": "Nhà xuất bản Thế Giới",
        "publication_year": 2022,
        "isbn": "8935235230804",
        "category_id": 4,
        "slug": "auth-036-to-hoc-lap-trinh-scratch",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8935235230804.jpg",
        "description": "Sách hướng dẫn nhập môn khoa học máy tính và lập trình kéo thả trực quan Scratch cho học sinh."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-037",
        "title": "Tuyệt Chiêu Kiếm Tiền Bằng AI",
        "author": "Ngô Chấn Thái, Từ Tiệp",
        "publisher": "Nhà xuất bản Công Thương",
        "publication_year": 2024,
        "isbn": "8936230474507",
        "category_id": 4,
        "slug": "auth-037-tuyet-chieu-kiem-tien-bang-ai",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/8936230474507.jpg",
        "description": "Ứng dụng các công cụ trí tuệ nhân tạo tạo sinh AI phục vụ tự động hóa quy trình làm việc và tiếp thị số."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-038",
        "title": "Chinh Phục Trình Diễn Bằng PowerPoint",
        "author": "TS. Tống Xuân Tám",
        "publisher": "Nhà xuất bản Xây dựng",
        "publication_year": 2021,
        "isbn": "9786048229177",
        "category_id": 120, # Văn phòng
        "slug": "auth-038-chinh-phuc-trinh-dien-powerpoint",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/9786048229177.jpg",
        "description": "Kỹ năng tư duy bố cục và thiết kế bài thuyết trình trực quan, giàu tính thuyết phục bằng MS PowerPoint."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-039",
        "title": "Tin Học Ứng Dụng 2 SAP2000 - Hướng Dẫn Các Dạng Kết Cấu Thông Dụng",
        "author": "ThS. Nguyễn Vy Thanh",
        "publisher": "Nhà xuất bản Xây dựng",
        "publication_year": 2022,
        "isbn": "9786048269630",
        "category_id": 4,
        "slug": "auth-039-tin-hoc-ung-dung-sap2000",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/9786048269630.jpg",
        "description": "Giáo trình tin học ứng dụng hướng dẫn mô hình hóa và tính toán nội lực kết cấu công trình xây dựng bằng SAP2000."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-040",
        "title": "Giáo Trình Lập Trình Java Căn Bản",
        "author": "Phạm Văn Trung",
        "publisher": "Nhà xuất bản Xây dựng",
        "publication_year": 2022,
        "isbn": "9786048271916",
        "category_id": 4,
        "slug": "auth-040-giao-trinh-lap-trinh-java-can-ban",
        "src_path": "storage/doi_soat/anh_sach/tin_hoc/9786048271916.png",
        "description": "Giáo trình giảng dạy đại học về lập trình Java, cấu trúc lớp đối tượng, xử lý luồng sự kiện và giao diện đồ họa."
    },

    # --- KHOA HỌC & KỸ NĂNG SỐNG (11 books) ---
    {
        "type": "new_book",
        "book_code": "AUTH-041",
        "title": "Học Là Chuyện Nhỏ: Bí Kíp Rèn Luyện Tính Tự Giác",
        "author": "Mengbao Culture",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042302319",
        "category_id": 6, # Kỹ năng sống
        "slug": "auth-041-bi-kip-ren-luyen-tinh-tu-giac",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/1fb3dbf2-1cee-4099-8d86-a980720b9a9a.webp",
        "description": "Truyện tranh kỹ năng trang bị thói quen tự giác, chủ động hoàn thành nhiệm vụ mỗi ngày cho học sinh."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-042",
        "title": "Kĩ Năng Cho Học Sinh Tiểu Học: Rèn Cách Quản Lí Thời Gian",
        "author": "Liu Yong",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301824",
        "category_id": 6,
        "slug": "auth-042-ren-cach-quan-li-thoi-gian",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/54664066-ea53-4cc7-a886-a3e1b58d08af.webp",
        "description": "20 bí quyết quản trị thời gian khoa học và phương pháp đánh bại căn bệnh trì hoãn ở lứa tuổi học đường."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-043",
        "title": "Kĩ Năng Cho Học Sinh Tiểu Học: Rèn Năng Lực Ngôn Từ",
        "author": "Liu Yong",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301831",
        "category_id": 6,
        "slug": "auth-043-ren-nang-luc-ngon-tu",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/620b5687-41ce-4f06-aede-261c3bcb8696.webp",
        "description": "26 kỹ thuật trau dồi vốn từ, đọc hiểu sâu và rèn luyện kỹ năng viết văn biểu cảm giàu hình ảnh."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-044",
        "title": "Khéo Khôn Với Tiền - Tránh Những Ưu Phiền (Tập 2)",
        "author": "Lê Thị Thuý Sen, Thăng Fly",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2024,
        "isbn": "9786042308908",
        "category_id": 5, # Kinh tế
        "slug": "auth-044-kheo-khon-voi-tien-tap-2",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/78a795c3-4b13-4c65-aa84-77d4834cc93e.webp",
        "description": "Truyện tranh kiến thức tài chính đoạt giải Sách Quốc gia, cung cấp góc nhìn thực tiễn về đầu tư và chi tiêu thông minh."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-045",
        "title": "MBTI 16 Nhóm Tính Cách: Hiểu Tính Cách Để Học Giỏi Hơn (Tập 4)",
        "author": "Jo Su Yeon, So Yoon",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042303026",
        "category_id": 6,
        "slug": "auth-045-mbti-hieu-tinh-cach-de-hoc-gioi-hon",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/87098cf1-816b-4e6d-8617-ed36a7af1e11.webp",
        "description": "Khám phá 16 nhóm tính cách tâm lý MBTI để xây dựng phương pháp học tập tối ưu hóa theo sở trường cá nhân."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-046",
        "title": "Học Là Chuyện Nhỏ: Bí Kíp Vượt Qua Áp Lực Học Hành",
        "author": "Mengbao Culture",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042302326",
        "category_id": 6,
        "slug": "auth-046-bi-kip-vuot-qua-ap-luc-hoc-hanh",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/ac5c82a6-6fb0-45f2-ac1d-9de3da5514c8.webp",
        "description": "Các kỹ năng tâm lý học đường giúp học sinh nhận diện nguyên nhân căng thẳng và xây dựng tâm thế học tập hứng khởi."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-047",
        "title": "Cẩm Nang Sinh Hoạt Bằng Tranh Cho Bé (Tập 2: Kĩ Năng Khi Ăn Uống)",
        "author": "Masaaki Yatagai, Akira Murakoshi",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042304115",
        "category_id": 7, # Thiếu nhi
        "slug": "auth-047-cam-nang-sinh-hoat-ki-nang-khi-an-uong",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/cam-nang-sinh-hoat-bang-tranh-bia-4tap-view-2-tb-2019-7d30e13c04ea4987a942dfeec9f25bca-88eb8163-826d-4831-89ae-6f0a92f83971.webp",
        "description": "Tranh cẩm nang phong cách Nhật Bản giáo dục thói quen vệ sinh, cách cầm đũa thìa và phép lịch sự trong bữa ăn gia đình."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-048",
        "title": "Comic Kĩ Năng Sống: Thoát Hiểm An Toàn",
        "author": "Hoài Thu, Hiếu Minh, Nguyễn Quang Toàn",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042305549",
        "category_id": 6,
        "slug": "auth-048-comic-thoat-hiem-an-toan",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/comic-ki-nang-song-thoat-hiem-an-toan-bia-1f27b557c67f4ae28e5b0d0e1fce5c18-99591bbf-0c05-4016-be81-3003691cf6aa.webp",
        "description": "Truyện tranh kiến thức sinh tồn trang bị cho trẻ kỹ năng phòng tránh tai nạn và tự thoát hiểm khi gặp hỏa hoạn, ngập lụt."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-049",
        "title": "Kĩ Năng Cho Học Sinh Tiểu Học: Rèn Khả Năng Vượt Qua Nghịch Cảnh",
        "author": "Liu Yong",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042301848",
        "category_id": 6,
        "slug": "auth-049-ren-kha-nang-vuot-qua-nghich-canh",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/ebdf3925-2f50-4e67-a513-d2999ca02d08.webp",
        "description": "29 bài học trau dồi chỉ số vượt khó AQ, rèn luyện bản lĩnh kiên trì không nản chí trước khó khăn cho học trò."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-050",
        "title": "MBTI 16 Nhóm Tính Cách: Hiểu Tính Cách Để Chọn Đúng Nghề Nghiệp (Tập 5)",
        "author": "Jo Su Yeon, So Yoon",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2023,
        "isbn": "9786042303033",
        "category_id": 6,
        "slug": "auth-050-mbti-hieu-tinh-cach-de-chon-dung-nghe",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/f5ab9af4-ab38-4093-8272-29e88a23cfb8.webp",
        "description": "Cẩm nang hướng nghiệp thanh thiếu niên phân tích xu hướng nghề nghiệp tương lai thích hợp theo trắc nghiệm tính cách MBTI."
    },
    {
        "type": "new_book",
        "book_code": "AUTH-051",
        "title": "Hiểu Về Tài Chính Vững Bước Tương Lai: Tiết Kiệm Cho Tương Lai",
        "author": "Cecilia Minden",
        "publisher": "Nhà xuất bản Kim Đồng",
        "publication_year": 2024,
        "isbn": "9786042306713",
        "category_id": 5, # Kinh tế
        "slug": "auth-051-hieu-ve-tai-chinh-tiet-kiem-cho-tuong-lai",
        "src_path": "storage/doi_soat/anh_sach/khoa_hoc/hieu-ve-tai-chinh-vung-buoc-tuong-lai-tiet-kiem-cho-tuong-lai-tb-2025-c4a66f397cbb407fa81e5f772c390cb5-70fd4901-2ef6-498a-a791-60c00069b168.webp",
        "description": "Sách kỹ năng tài chính cơ bản giải thích nguồn gốc tiền tệ và thói quen tiết kiệm thông minh cho trẻ em."
    }
]

def main():
    dest_dir = "public/assets/book-covers"
    os.makedirs(dest_dir, exist_ok=True)
    
    converted_count = 0
    print(f"Starting conversion for {len(BOOKS_DATA)} items...")
    
    for item in BOOKS_DATA:
        src = item["src_path"]
        slug = item["slug"]
        if not os.path.isfile(src):
            print(f"ERROR: Source file not found: {src}")
            continue
            
        webp_path = os.path.join(dest_dir, f"{slug}.webp")
        jpg_path = os.path.join(dest_dir, f"{slug}.jpg")
        
        with Image.open(src) as im:
            # Convert RGBA or Palette to RGB for JPG
            rgb_im = im.convert("RGB")
            
            # Save WebP
            im.save(webp_path, "WEBP", quality=85, method=6)
            # Save JPG fallback
            rgb_im.save(jpg_path, "JPEG", quality=85, optimize=True)
            
            w, h = im.size
            sz_webp = os.path.getsize(webp_path)
            sz_jpg = os.path.getsize(jpg_path)
            print(f"[{item['book_code']}] {item['title'][:35]} -> {slug}.webp ({w}x{h}, {sz_webp} B) | {slug}.jpg ({sz_jpg} B)")
            converted_count += 1
            
    print(f"\nSUCCESS: Converted {converted_count}/{len(BOOKS_DATA)} book covers!")

if __name__ == "__main__":
    main()
