# BÁO CÁO KIỂM THỬ TỔNG HỢP (FINAL RELEASE TEST REPORT)
## PAPER HAVEN — HỆ THỐNG QUẢN LÝ THƯ VIỆN TRỰC TUYẾN

* **Ngày thực thi kiểm thử:** 18/10/2026
* **Môi trường thực thi:** Windows 11, Laragon 6.0+, PHP 8.3.33 x64, MySQL 8.4.3 LTS
* **Database kiểm định:** `paper_page_library` (Port 3306)
* **Web Server phục vụ:** PHP CLI Server (Port 8010)

---

## 1. BẢNG TỔNG HỢP CÁC BỘ KIỂM THỬ TỰ ĐỘNG

| Bộ kiểm thử | Tệp lệnh thực thi | Số lượng kiểm tra | Kết quả | Trạng thái |
| :--- | :--- | :---: | :---: | :---: |
| **PHP Syntax Linting** | `php tests/lint.php` | 170 tệp PHP | 0 lỗi cú pháp | **PASS** |
| **Smoke Testing** | `php tests/run.php` | 10 kiểm tra | 10/10 PASS | **PASS** |
| **Master V8 Feature Tests** | `php tests/test_v8_features.php` | 11 kiểm tra | 11/11 PASS | **PASS** |
| **Inventory Audit Deep Tests** | `php tests/test_v8_inventory_audit_deep.php` | 18 kiểm tra | 18/18 PASS | **PASS** |
| **Chatbot Hardening & Security** | `php tests/test_v8_chatbot_hardening.php` | 17 kiểm tra | 17/17 PASS | **PASS** |
| **Postflight Schema Invariants** | `php tests/postflight_check.php` | 12 kiểm tra | 12/12 PASS | **PASS** |
| **Postflight Business Workflow** | `php tests/test_v12_2_postflight_business.php` | 14 kiểm tra | 14/14 PASS | **PASS** |
| **Read-only Metrics Audit** | `php storage/verify_readonly_metrics.php` | 11 kiểm tra | 11/11 PASS | **PASS** |

**TỔNG CỘNG:** **93 / 93 Phép thử kỹ thuật đạt PASS tuyệt đối (100%)**.

---

## 2. KẾT QUẢ ĐỐI SOÁT CHỈ SỐ CƠ SỞ DỮ LIỆU BẤT BIẾN

Từ kết quả thực thi script `storage/verify_readonly_metrics.php`:

```text
================================================================================
              DATABASE READ-ONLY VERIFICATION AUDIT                             
================================================================================
Connected Database: paper_page_library

--- 1. BOOKS & INVENTORY METRICS ---
Total Books:               129 (Expected: 129) -> PASS
Total Copies:              265 (Expected: 265) -> PASS
Copy Status Breakdown:
  - available: 102
  - on_loan: 2
  - damaged: 1
  - pending_audit: 160
Duplicate Copy Codes:      0 (Expected: 0) -> PASS
Duplicate Valid ISBNs:     0 (Expected: 0) -> PASS
Books with ISBN = NULL:    110 (Expected: 110) -> PASS
Books with Verified ISBN:  19 (Expected: 19) -> PASS

--- 2. CIRCULATION & OPERATIONS ---
Active Loans (Header):     2
Active Borrowed Items:     2
Open Fine Charges:         3
Total Fines Assessed:      85.000 VND
Total Fines Paid:          0 VND
Total Fines Waived:        0 VND
Total Audit Activities:    99

--- 3. REFERENTIAL INTEGRITY ---
Orphaned Copies:           0 (Expected: 0) -> PASS
Orphaned Loan Items:       0 (Expected: 0) -> PASS
Orphaned Reservations:     0 (Expected: 0) -> PASS

--- 4. MIGRATION HISTORY (Total: 16) ---
  [APPLIED] 001_initial_schema at 2026-10-08 17:28:04
  [APPLIED] 002_copy_level_inventory at 2026-10-08 17:40:37
  [APPLIED] 003_reader_policies_reservations_notifications at 2026-10-08 17:50:23
  [APPLIED] 004_versioned_library_policies at 2026-10-08 18:00:21
  [APPLIED] 005_fines_and_payments at 2026-10-08 18:18:26
  [APPLIED] 006_normalized_catalog_metadata at 2026-10-08 18:22:48
  [APPLIED] 007_action_permissions at 2026-10-10 11:28:33
  [APPLIED] 008_return_receipt_snapshot at 2026-10-10 11:28:33
  [APPLIED] 009_mutation_requests at 2026-10-11 20:38:59
  [APPLIED] 010_zero_operational_inventory at 2026-10-11 20:50:49
  [APPLIED] 011_public_catalog_visibility at 2026-10-12 09:21:55
  [APPLIED] 012_reader_favorites_reviews_and_audits at 2026-10-17 10:12:28
  [APPLIED] 013_enrich_v9_book_metadata at 2026-10-17 20:03:47
  [APPLIED] 014_real_books_provenance_and_shelves at 2026-10-17 20:48:27
  [APPLIED] 015_import_80_authentic_books at 2026-10-17 21:35:28
  [APPLIED] 016_quarantine_simulated_copies_and_clean_isbns at 2026-10-17 23:32:50

================================================================================
READ-ONLY AUDIT RESULT: ALL 100% INVARIANTS VERIFIED (PASS)
================================================================================
```

---

## 3. KẾT QUẢ ĐỐI SOÁT BÌA SÁCH (BOOK COVERS AUDIT)

* **Tổng số tệp ảnh đối soát trong ZIP:** 56 tệp.
* **Tệp ảnh đối soát thành công 100%:**
  - Tệp: `storage/doi_soat/anh_sach/khoa_hoc/B301_luoc-su-thoi-gian.jpg`
  - Đầu sách tương ứng: **ID 261 — "Lược sử thời gian"** (Tác giả: Stephen Hawking, NXB Trẻ, ISBN: 978-604-1-01204-2).
  - Tệp triển khai sản phẩm: `public/assets/book-covers/b301-luoc-su-thoi-gian.webp` (137 KB) & `b301-luoc-su-thoi-gian.jpg` (208 KB).
  - Trạng thái HTTP: **200 OK** khi truy cập trang chi tiết sách `/book/261`.
* **53 tệp ảnh còn lại trong ZIP:**
  - Không ghép bừa bãi ("râu ông nọ cắm cằm bà kia"). Các đầu sách chưa có ảnh đúng ấn bản được hiển thị bằng **Neutral SVG Book Placeholder** hiển thị tiêu đề và tác giả rõ ràng.
  - Chi tiết đối soát đầy đủ tại `docs/BOOK_COVER_MATCH_REPORT.md`.

---

## 4. KẾT LUẬN NGHIỆM THU

Toàn bộ hệ thống phần mềm **Paper Haven** đạt trạng thái ổn định, an toàn và sẵn sàng 100% để bảo vệ và nộp đồ án môn Lập trình Mã nguồn mở.
