/* =========================================================
   Dữ liệu mẫu (mock data) - sau này sẽ thay bằng dữ liệu từ
   backend (PHP + MySQL) qua AJAX/fetch
   ========================================================= */

const categories = [
  { id: 1, name: 'Văn học', count: 0 },
  { id: 2, name: 'Khoa học', count: 0 },
  { id: 3, name: 'Lịch sử', count: 0 },
  { id: 4, name: 'Tin học', count: 0 },
  { id: 5, name: 'Kinh tế', count: 0 },
  { id: 6, name: 'Tâm lý - Kỹ năng sống', count: 0 }
];

const books = [
  { id: 'B001', title: 'Giết con chim nhại', author: 'Harper Lee', category: 'Văn học', year: 2015, total: 8, available: 6 },
  { id: 'B002', title: 'Sapiens: Lược sử loài người', author: 'Yuval Noah Harari', category: 'Lịch sử', year: 2017, total: 5, available: 3 },
  { id: 'B003', title: 'Trí tuệ xúc cảm', author: 'Daniel Goleman', category: 'Tâm lý - Kỹ năng sống', year: 2018, total: 6, available: 5 },
  { id: 'B004', title: 'Nhà giả kim', author: 'Paulo Coelho', category: 'Văn học', year: 2016, total: 10, available: 7 },
  { id: 'B005', title: 'Harry Potter và Hòn đá phù thủy', author: 'J.K. Rowling', category: 'Văn học', year: 2014, total: 12, available: 9 },
  { id: 'B006', title: 'Vật lý lý thú', author: 'Y. Perelman', category: 'Khoa học', year: 2019, total: 4, available: 2 },
  { id: 'B007', title: 'Nền kinh tế tăng trưởng và thịnh vượng', author: 'William Bernstein', category: 'Kinh tế', year: 2020, total: 5, available: 4 },
  { id: 'B008', title: 'HTML & CSS – Thiết kế Web', author: 'Jon Duckett', category: 'Tin học', year: 2021, total: 7, available: 5 },
  { id: 'B009', title: 'Kiến thức lập trình cơ bản', author: 'Nguyễn Văn A', category: 'Tin học', year: 2022, total: 6, available: 6 }
];

const readers = [
  { card: 'DG001', name: 'Nguyễn Văn An', gender: 'Nam', phone: '0901234567', email: 'an@gmail.com', borrowing: 1 },
  { card: 'DG002', name: 'Trần Thị Bình', gender: 'Nữ', phone: '0912345678', email: 'binh@gmail.com', borrowing: 2 },
  { card: 'DG003', name: 'Lê Minh Cường', gender: 'Nam', phone: '0923456789', email: 'cuong@gmail.com', borrowing: 0 },
  { card: 'DG004', name: 'Phạm Thu Dung', gender: 'Nữ', phone: '0934567890', email: 'dung@gmail.com', borrowing: 1 },
  { card: 'DG005', name: 'Hoàng Văn Em', gender: 'Nam', phone: '0945678901', email: 'em@gmail.com', borrowing: 0 }
];

let borrows = [
  { code: 'PM001', reader: 'Nguyễn Văn An', book: 'Sapiens: Lược sử loài người', startDate: '2026-09-05', dueDate: '2026-09-19', status: 'Quá hạn' },
  { code: 'PM002', reader: 'Trần Thị Bình', book: 'Nhà giả kim', startDate: '2026-09-10', dueDate: '2026-09-24', status: 'Đang mượn' },
  { code: 'PM003', reader: 'Lê Minh Cường', book: 'Giết con chim nhại', startDate: '2026-08-20', dueDate: '2026-09-03', status: 'Đã trả' },
  { code: 'PM004', reader: 'Phạm Thu Dung', book: 'Harry Potter và Hòn đá phù thủy', startDate: '2026-09-12', dueDate: '2026-09-26', status: 'Đang mượn' },
  { code: 'PM005', reader: 'Nguyễn Văn An', book: 'Trí tuệ xúc cảm', startDate: '2026-09-01', dueDate: '2026-09-15', status: 'Đã trả' },
  { code: 'PM006', reader: 'Trần Thị Bình', book: 'Vật lý lý thú', startDate: '2026-09-14', dueDate: '2026-09-28', status: 'Đang mượn' }
];

/* ---------- CSS lớp: mượn/trả ---------- */
const CAT_CLASSES = ['#2563eb', '#16a34a', '#7c3aed', '#ea580c', '#dc2626', '#0d9488'];

function refreshCategoryCounts() {
  categories.forEach(c => {
    c.count = books.filter(b => b.category === c.name).length;
  });
}

function badge(status) {
  const cls = {
    'Đang mượn': 'badge-blue',
    'Đã trả': 'badge-green',
    'Quá hạn': 'badge-red'
  }[status] || 'badge-gray';
  return `<span class="badge ${cls}">${status}</span>`;
}

/* =========================================================
   ĐIỀU HƯỚNG & TRẠNG THÁI
   ========================================================= */
const pageTitles = {
  home: 'Tổng quan',
  books: 'Quản lý sách',
  readers: 'Quản lý độc giả',
  borrows: 'Mượn / Trả sách',
  categories: 'Thể loại'
};

function openSection(section) {
  document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
  document.getElementById('section-' + section).classList.add('active');
  document.querySelectorAll('.nav-item[data-section]').forEach(el => {
    el.classList.toggle('active', el.dataset.section === section);
  });
  document.getElementById('pageTitle').textContent = pageTitles[section];
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* =========================================================
   TRANG TỔNG QUAN
   ========================================================= */
function renderHome() {
  document.getElementById('statBooks').textContent = books.reduce((s, b) => s + b.total, 0);
  document.getElementById('statReaders').textContent = readers.length;
  document.getElementById('statBorrowing').textContent = borrows.filter(b => b.status === 'Đang mượn').length;
  document.getElementById('statOverdue').textContent = borrows.filter(b => b.status === 'Quá hạn').length;

  document.getElementById('recentBorrows').innerHTML = borrows.slice(0, 5).map(b => `
    <tr>
      <td>${b.code}</td>
      <td>${b.reader}</td>
      <td>${b.book}</td>
      <td>${b.startDate}</td>
      <td>${badge(b.status)}</td>
    </tr>`).join('');

  const max = Math.max(...categories.map(c => c.count), 1);
  document.getElementById('categoryStats').innerHTML = categories.map((c, i) => `
    <div class="cat-stat-row">
      <div class="row-top"><span>${c.name}</span><span>${c.count} đầu sách</span></div>
      <div class="progress"><span style="width:${Math.round(c.count / max * 100)}%; background:${CAT_CLASSES[i % CAT_CLASSES.length]}"></span></div>
    </div>`).join('');
}

/* =========================================================
   QUẢN LÝ SÁCH
   ========================================================= */
function renderBooks() {
  const kw = document.getElementById('bookSearch').value.trim().toLowerCase();
  const cat = document.getElementById('bookCategoryFilter').value;
  let rows = books.filter(b => {
    const matchKw = !kw ||
      b.title.toLowerCase().includes(kw) ||
      b.author.toLowerCase().includes(kw) ||
      b.id.toLowerCase().includes(kw) ||
      String(b.year).includes(kw);
    const matchCat = !cat || b.category === cat;
    return matchKw && matchCat;
  });

  document.getElementById('booksTable').innerHTML = rows.map(b => `
    <tr>
      <td>${b.id}</td>
      <td><strong>${b.title}</strong></td>
      <td>${b.author}</td>
      <td>${b.category}</td>
      <td>${b.year}</td>
      <td>${b.total}</td>
      <td>${b.available}</td>
      <td><div class="actions">
        <button class="btn btn-outline btn-sm" onclick="openBookModal(${b.id})">Sửa</button>
        <button class="btn btn-danger btn-sm" onclick="deleteBook('${b.id}')">Xóa</button>
      </div></td>
    </tr>`).join('');
  document.getElementById('booksEmpty').hidden = rows.length !== 0;
}

function bookCategoryOptions(selected) {
  return categories.map(c =>
    `<option value="${c.name}" ${c.name === selected ? 'selected' : ''}>${c.name}</option>`
  ).join('');
}

function openBookModal(id) {
  const b = books.find(x => x.id === id) || { id: '', title: '', author: '', category: '', year: 2026, total: 1, available: 1 };
  openModal('Thêm sách mới', `
    <div class="form-group"><label>Mã sách *</label>
      <input id="bId" value="${b.id}" placeholder="VD: B010" ${id ? 'readonly style="background:#f1f5f9"' : ''}></div>
    <div class="form-group"><label>Tiêu đề *</label>
      <input id="bTitle" value="${b.title}" placeholder="Tên sách"></div>
    <div class="form-group"><label>Tác giả *</label>
      <input id="bAuthor" value="${b.author}" placeholder="Tên tác giả"></div>
    <div class="form-row">
      <div class="form-group"><label>Thể loại</label>
        <select id="bCategory">${bookCategoryOptions(b.category)}</select></div>
      <div class="form-group"><label>Năm xuất bản</label>
        <input id="bYear" type="number" value="${b.year}" min="1900" max="2100"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Số lượng *</label>
        <input id="bTotal" type="number" value="${b.total}" min="1"></div>
      <div class="form-group"><label>Còn lại</label>
        <input id="bAvailable" type="number" value="${b.available}" min="0" max="${b.total}"></div>
    </div>`,
    () => saveBook(id));
}

function saveBook(id) {
  const data = {
    title: document.getElementById('bTitle').value.trim(),
    author: document.getElementById('bAuthor').value.trim(),
    category: document.getElementById('bCategory').value,
    year: Number(document.getElementById('bYear').value) || 2026,
    total: Number(document.getElementById('bTotal').value) || 1,
    available: Number(document.getElementById('bAvailable').value) || 0
  };
  if (!data.title || !data.author) { alert('Vui lòng nhập tiêu đề và tác giả!'); return; }

  if (id) {
    const b = books.find(x => x.id === id);
    Object.assign(b, data);
  } else {
    const bId = document.getElementById('bId').value.trim();
    if (!bId) { alert('Vui lòng nhập mã sách!'); return; }
    if (books.some(x => x.id === bId)) { alert('Mã sách đã tồn tại!'); return; }
    books.unshift({ id: bId, ...data });
  }
  closeModal(); renderAll();
}

function deleteBook(id) {
  if (!confirm('Bạn có chắc muốn xóa sách ' + id + '?')) return;
  const idx = books.findIndex(x => x.id === id);
  if (idx > -1) books.splice(idx, 1);
  renderAll();
}

/* =========================================================
   QUẢN LÝ ĐỘC GIẢ
   ========================================================= */
function renderReaders() {
  const kw = document.getElementById('readerSearch').value.trim().toLowerCase();
  const rows = readers.filter(r =>
    !kw || r.name.toLowerCase().includes(kw) || r.card.toLowerCase().includes(kw) || r.phone.includes(kw)
  );

  document.getElementById('readersTable').innerHTML = rows.map(r => `
    <tr>
      <td>${r.card}</td>
      <td><strong>${r.name}</strong></td>
      <td>${r.gender}</td>
      <td>${r.phone}</td>
      <td>${r.email}</td>
      <td>${r.borrowing ? `<span class="badge badge-orange">${r.borrowing} phiếu</span>` : '<span class="badge badge-gray">0</span>'}</td>
      <td><div class="actions">
        <button class="btn btn-outline btn-sm" onclick="openReaderModal('${r.card}')">Sửa</button>
        <button class="btn btn-danger btn-sm" onclick="deleteReader('${r.card}')">Xóa</button>
      </div></td>
    </tr>`).join('');
  document.getElementById('readersEmpty').hidden = rows.length !== 0;
}

function openReaderModal(card) {
  const r = readers.find(x => x.card === card) || { card: '', name: '', gender: 'Nam', phone: '', email: '' };
  openModal(card ? 'Sửa độc giả' : 'Thêm độc giả mới', `
    <div class="form-group"><label>Mã thẻ *</label>
      <input id="rCode" value="${r.card}" placeholder="VD: DG006" ${card ? 'readonly style="background:#f1f5f9"' : ''}></div>
    <div class="form-group"><label>Họ tên *</label>
      <input id="rName" value="${r.name}" placeholder="Họ và tên độc giả"></div>
    <div class="form-row">
      <div class="form-group"><label>Giới tính</label>
        <select id="rGender">
          <option value="Nam" ${r.gender === 'Nam' ? 'selected' : ''}>Nam</option>
          <option value="Nữ" ${r.gender === 'Nữ' ? 'selected' : ''}>Nữ</option>
        </select></div>
      <div class="form-group"><label>Số điện thoại</label>
        <input id="rPhone" value="${r.phone}" placeholder="0xxxxxxxxx"></div>
    </div>
    <div class="form-group"><label>Email</label>
      <input id="rEmail" type="email" value="${r.email}" placeholder="email@example.com"></div>`,
    () => saveReader(card));
}

function saveReader(card) {
  const data = {
    name: document.getElementById('rName').value.trim(),
    gender: document.getElementById('rGender').value,
    phone: document.getElementById('rPhone').value.trim(),
    email: document.getElementById('rEmail').value.trim()
  };
  if (!data.name) { alert('Vui lòng nhập họ tên độc giả!'); return; }

  if (card) {
    Object.assign(readers.find(x => x.card === card), data);
  } else {
    const code = document.getElementById('rCode').value.trim();
    if (!code) { alert('Vui lòng nhập mã thẻ!'); return; }
    if (readers.some(x => x.card === code)) { alert('Mã thẻ đã tồn tại!'); return; }
    readers.unshift({ card: code, ...data, borrowing: 0 });
  }
  closeModal(); renderAll();
}

function deleteReader(card) {
  if (!confirm('Bạn có chắc muốn xóa độc giả ' + card + '?')) return;
  const idx = readers.findIndex(x => x.card === card);
  if (idx > -1) readers.splice(idx, 1);
  renderAll();
}

/* =========================================================
   MƯỢN / TRẢ SÁCH
   ========================================================= */
function renderBorrows() {
  const kw = document.getElementById('borrowSearch').value.trim().toLowerCase();
  const st = document.getElementById('borrowStatusFilter').value;
  const rows = borrows.filter(b => {
    const matchKw = !kw || b.code.toLowerCase().includes(kw) || b.reader.toLowerCase().includes(kw) || b.book.toLowerCase().includes(kw);
    const matchSt = !st || b.status === st;
    return matchKw && matchSt;
  });

  document.getElementById('borrowsTable').innerHTML = rows.map(b => `
    <tr>
      <td>${b.code}</td>
      <td>${b.reader}</td>
      <td>${b.book}</td>
      <td>${b.startDate}</td>
      <td>${b.dueDate}</td>
      <td>${badge(b.status)}</td>
      <td>${b.status === 'Đang mượn' || b.status === 'Quá hạn'
          ? `<button class="btn btn-success btn-sm" onclick="returnBook('${b.code}')">Trả sách</button>`
          : '<span class="badge badge-gray">Hoàn tất</span>'}</td>
    </tr>`).join('');
  document.getElementById('borrowsEmpty').hidden = rows.length !== 0;
}

function openBorrowModal() {
  const readerOptions = readers.map(r => `<option value="${r.name}">${r.name} (${r.card})</option>`).join('');
  const bookOptions = books.filter(b => b.available > 0)
    .map(b => `<option value="${b.title}">${b.title} – còn ${b.available}</option>`).join('');
  const today = new Date().toISOString().slice(0, 10);

  openModal('Lập phiếu mượn mới', `
    <div class="form-group"><label>Độc giả *</label><select id="pReader">${readerOptions}</select></div>
    <div class="form-group"><label>Sách *</label><select id="pBook">${bookOptions}</select></div>
    <div class="form-row">
      <div class="form-group"><label>Ngày mượn</label>
        <input id="pStart" type="date" value="${today}"></div>
      <div class="form-group"><label>Hạn trả</label>
        <input id="pDue" type="date" value="${today}"></div>
    </div>`,
    () => saveBorrow());
}

function saveBorrow() {
  const reader = document.getElementById('pReader').value;
  const book = document.getElementById('pBook').value;
  const startDate = document.getElementById('pStart').value;
  const dueDate = document.getElementById('pDue').value;
  if (!reader || !book) { alert('Vui lòng chọn độc giả và sách!'); return; }
  if (!startDate || !dueDate) { alert('Vui lòng chọn ngày mượn và hạn trả!'); return; }

  const b = books.find(x => x.title === book);
  if (b && b.available > 0) b.available--;
  const r = readers.find(x => x.name === reader);
  if (r) r.borrowing++;

  const code = 'PM' + String(borrows.length + 1).padStart(3, '0');
  borrows.unshift({ code, reader, book, startDate, dueDate, status: 'Đang mượn' });
  closeModal(); renderAll();
}

function returnBook(code) {
  const borrow = borrows.find(b => b.code === code);
  if (!borrow) return;
  borrow.status = 'Đã trả';
  const b = books.find(x => x.title === borrow.book);
  if (b) b.available++;
  const r = readers.find(x => x.name === borrow.reader);
  if (r && r.borrowing > 0) r.borrowing--;
  renderAll();
}

/* =========================================================
   THỂ LOẠI
   ========================================================= */
function renderCategories() {
  refreshCategoryCounts();
  document.getElementById('categoriesList').innerHTML = categories.map(c => `
    <div class="category-chip">
      <div>
        <strong>${c.name}</strong><br>
        <span class="count">${c.count} đầu sách</span>
      </div>
      <div class="actions">
        <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.id})">Xóa</button>
      </div>
    </div>`).join('');
  document.getElementById('categoriesEmpty').hidden = categories.length !== 0;
}

function addCategory() {
  openModal('Thêm thể loại mới', `
    <div class="form-group"><label>Tên thể loại *</label>
      <input id="cName" placeholder="VD: Thiếu nhi"></div>`,
    () => {
      const name = document.getElementById('cName').value.trim();
      if (!name) { alert('Vui lòng nhập tên thể loại!'); return; }
      if (categories.some(c => c.name === name)) { alert('Thể loại đã tồn tại!'); return; }
      categories.push({ id: Date.now(), name, count: 0 });
      closeModal(); renderAll();
    });
}

function deleteCategory(id) {
  const cat = categories.find(c => c.id === id);
  if (!cat) return;
  if (!confirm('Xóa thể loại "' + cat.name + '"?')) return;
  const idx = categories.findIndex(c => c.id === id);
  if (idx > -1) categories.splice(idx, 1);
  renderAll();
}

/* =========================================================
   MODAL - dùng chung
   ========================================================= */
let modalConfirmFn = null;

function openModal(title, bodyHtml, confirmFn) {
  modalConfirmFn = confirmFn;
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = bodyHtml +
    `<div class="modal-actions">
       <button class="btn btn-outline" onclick="closeModal()">Hủy</button>
       <button class="btn btn-primary" onclick="modalConfirmFn && modalConfirmFn()">Lưu</button>
     </div>`;
  document.getElementById('modalOverlay').hidden = false;
}

function closeModal() {
  document.getElementById('modalOverlay').hidden = true;
  modalConfirmFn = null;
}

/* =========================================================
   RENDER TẤT CẢ + SỰ KIỆN
   ========================================================= */
function renderAll() {
  renderHome();
  renderBooks();
  renderReaders();
  renderBorrows();
  renderCategories();
}

document.addEventListener('DOMContentLoaded', function () {
  // Đổ danh sách thể loại vào bộ lọc sách
  document.getElementById('bookCategoryFilter').innerHTML =
    '<option value="">Tất cả thể loại</option>' +
    categories.map(c => `<option value="${c.name}">${c.name}</option>`).join('');

  renderAll();

  // Điều hướng sidebar
  document.querySelectorAll('.nav-item[data-section]').forEach(el => {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openSection(this.dataset.section);
      document.getElementById('sidebar').classList.remove('open');
    });
  });

  // Liên kết "Xem tất cả"
  document.querySelectorAll('[data-goto]').forEach(el => {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openSection(this.dataset.goto);
    });
  });

  // Nút mở menu (mobile)
  document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('open');
  });

  // Tìm kiếm / lọc
  document.getElementById('bookSearch').addEventListener('input', renderBooks);
  document.getElementById('bookCategoryFilter').addEventListener('change', renderBooks);
  document.getElementById('readerSearch').addEventListener('input', renderReaders);
  document.getElementById('borrowSearch').addEventListener('input', renderBorrows);
  document.getElementById('borrowStatusFilter').addEventListener('change', renderBorrows);

  // Nút thêm
  document.getElementById('addBookBtn').addEventListener('click', () => openBookModal(null));
  document.getElementById('addReaderBtn').addEventListener('click', () => openReaderModal(null));
  document.getElementById('addBorrowBtn').addEventListener('click', openBorrowModal);
  document.getElementById('addCategoryBtn').addEventListener('click', addCategory);

  // Modal
  document.getElementById('modalClose').addEventListener('click', closeModal);
  document.getElementById('modalOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });

  // Tìm kiếm toàn cục
  document.getElementById('globalSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && this.value.trim()) {
      const kw = this.value.trim();
      document.getElementById('bookSearch').value = kw;
      document.getElementById('readerSearch').value = kw;
      document.getElementById('borrowSearch').value = kw;
      openSection('books');
      this.value = '';
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });
});