const STORAGE_KEY = 'qltvDataV1';
const AUTH_KEY = 'qltvAuthenticated';
const THEME_KEY = 'qltvTheme';
const CATEGORY_COLORS = ['#2563eb', '#16a34a', '#7c3aed', '#ea580c', '#dc2626', '#0d9488'];
const COVER_ISBNS = {
  B001: '9780060935467',
  B002: '9780062316097',
  B003: '9780553383713',
  B004: '9780061122415',
  B005: '9780590353427',
  B008: '9781118008188'
};
const failedCovers = new Set();

const pageTitles = {
  home: 'Tổng quan',
  books: 'Quản lý sách',
  readers: 'Quản lý độc giả',
  borrows: 'Mượn / Trả sách',
  categories: 'Thể loại',
  stats: 'Thống kê',
  settings: 'Cài đặt'
};

function dateString(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function dateOffset(days) {
  const date = new Date();
  date.setHours(12, 0, 0, 0);
  date.setDate(date.getDate() + days);
  return dateString(date);
}

const seedData = {
  categories: [
    { id: 1, name: 'Văn học' },
    { id: 2, name: 'Khoa học' },
    { id: 3, name: 'Lịch sử' },
    { id: 4, name: 'Tin học' },
    { id: 5, name: 'Kinh tế' },
    { id: 6, name: 'Tâm lý - Kỹ năng sống' }
  ],
  books: [
    { id: 'B001', title: 'Giết con chim nhại', author: 'Harper Lee', category: 'Văn học', year: 2015, total: 8 },
    { id: 'B002', title: 'Sapiens: Lược sử loài người', author: 'Yuval Noah Harari', category: 'Lịch sử', year: 2017, total: 5 },
    { id: 'B003', title: 'Trí tuệ xúc cảm', author: 'Daniel Goleman', category: 'Tâm lý - Kỹ năng sống', year: 2018, total: 6 },
    { id: 'B004', title: 'Nhà giả kim', author: 'Paulo Coelho', category: 'Văn học', year: 2016, total: 10 },
    { id: 'B005', title: 'Harry Potter và Hòn đá phù thủy', author: 'J.K. Rowling', category: 'Văn học', year: 2014, total: 12 },
    { id: 'B006', title: 'Vật lý lý thú', author: 'Y. Perelman', category: 'Khoa học', year: 2019, total: 4 },
    { id: 'B007', title: 'Nền kinh tế tăng trưởng và thịnh vượng', author: 'William Bernstein', category: 'Kinh tế', year: 2020, total: 5 },
    { id: 'B008', title: 'HTML & CSS – Thiết kế Web', author: 'Jon Duckett', category: 'Tin học', year: 2021, total: 7 },
    { id: 'B009', title: 'Kiến thức lập trình cơ bản', author: 'Nguyễn Văn A', category: 'Tin học', year: 2022, total: 6 }
  ],
  readers: [
    { card: 'DG001', name: 'Nguyễn Văn An', gender: 'Nam', phone: '0901234567', email: 'an@gmail.com' },
    { card: 'DG002', name: 'Trần Thị Bình', gender: 'Nữ', phone: '0912345678', email: 'binh@gmail.com' },
    { card: 'DG003', name: 'Lê Minh Cường', gender: 'Nam', phone: '0923456789', email: 'cuong@gmail.com' },
    { card: 'DG004', name: 'Phạm Thu Dung', gender: 'Nữ', phone: '0934567890', email: 'dung@gmail.com' },
    { card: 'DG005', name: 'Hoàng Văn Em', gender: 'Nam', phone: '0945678901', email: 'em@gmail.com' }
  ],
  borrows: [
    { code: 'PM001', readerCard: 'DG001', readerName: 'Nguyễn Văn An', bookId: 'B002', bookTitle: 'Sapiens: Lược sử loài người', startDate: dateOffset(-18), dueDate: dateOffset(-4), status: 'Quá hạn' },
    { code: 'PM002', readerCard: 'DG002', readerName: 'Trần Thị Bình', bookId: 'B004', bookTitle: 'Nhà giả kim', startDate: dateOffset(-8), dueDate: dateOffset(6), status: 'Đang mượn' },
    { code: 'PM003', readerCard: 'DG003', readerName: 'Lê Minh Cường', bookId: 'B001', bookTitle: 'Giết con chim nhại', startDate: dateOffset(-35), dueDate: dateOffset(-21), status: 'Đã trả', returnedDate: dateOffset(-24) },
    { code: 'PM004', readerCard: 'DG004', readerName: 'Phạm Thu Dung', bookId: 'B005', bookTitle: 'Harry Potter và Hòn đá phù thủy', startDate: dateOffset(-12), dueDate: dateOffset(2), status: 'Đang mượn' },
    { code: 'PM005', readerCard: 'DG001', readerName: 'Nguyễn Văn An', bookId: 'B003', bookTitle: 'Trí tuệ xúc cảm', startDate: dateOffset(-45), dueDate: dateOffset(-31), status: 'Đã trả', returnedDate: dateOffset(-33) },
    { code: 'PM006', readerCard: 'DG002', readerName: 'Trần Thị Bình', bookId: 'B006', bookTitle: 'Vật lý lý thú', startDate: dateOffset(-16), dueDate: dateOffset(-2), status: 'Quá hạn' }
  ]
};

let categories = [];
let books = [];
let readers = [];
let borrows = [];
let modalConfirmFn = null;
let noticeTimer = null;

function escapeHTML(value) {
  return String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[character]);
}

function normalizeText(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/Đ/g, 'D')
    .toLocaleLowerCase('vi');
}

function showNotice(message, isError = false) {
  const notice = document.getElementById('appNotice');
  if (!notice) {
    window.alert(message);
    return;
  }

  notice.textContent = message;
  notice.classList.toggle('error', isError);
  notice.hidden = false;
  window.clearTimeout(noticeTimer);
  noticeTimer = window.setTimeout(() => { notice.hidden = true; }, 4200);
}

function cloneSeedData() {
  return JSON.parse(JSON.stringify(seedData));
}

function loadData() {
  let rawData;
  try {
    rawData = window.localStorage.getItem(STORAGE_KEY);
  } catch (error) {
    showNotice('Trình duyệt không cho phép đọc dữ liệu đã lưu. Dữ liệu mẫu sẽ được sử dụng trong phiên này.', true);
  }

  if (!rawData) return cloneSeedData();

  try {
    const data = JSON.parse(rawData);
    if (!data || !Array.isArray(data.categories) || !Array.isArray(data.books) ||
        !Array.isArray(data.readers) || !Array.isArray(data.borrows)) {
      throw new Error('Cấu trúc dữ liệu không hợp lệ.');
    }
    return data;
  } catch (error) {
    showNotice('Dữ liệu lưu bị lỗi định dạng. Hệ thống đã nạp lại dữ liệu mẫu.', true);
    return cloneSeedData();
  }
}

function persistData() {
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ categories, books, readers, borrows }));
    return true;
  } catch (error) {
    showNotice('Không thể lưu dữ liệu vào trình duyệt. Thay đổi hiện tại chỉ còn trong phiên này.', true);
    return false;
  }
}

function bookForBorrow(borrow) {
  return books.find(book => book.id === borrow.bookId) ||
    books.find(book => book.title === borrow.bookTitle);
}

function readerForBorrow(borrow) {
  return readers.find(reader => reader.card === borrow.readerCard) ||
    readers.find(reader => reader.name === borrow.readerName);
}

function isActiveBorrow(borrow) {
  return borrow.status !== 'Đã trả';
}

function syncDerivedData() {
  const today = dateString(new Date());
  borrows.forEach(borrow => {
    if (borrow.status !== 'Đã trả') {
      borrow.status = borrow.dueDate < today ? 'Quá hạn' : 'Đang mượn';
    }
  });

  books.forEach(book => {
    const onLoan = borrows.filter(borrow => isActiveBorrow(borrow) && bookForBorrow(borrow) === book).length;
    book.available = Math.max(0, Number(book.total) - onLoan);
  });

  readers.forEach(reader => {
    reader.borrowing = borrows.filter(borrow =>
      isActiveBorrow(borrow) && readerForBorrow(borrow) === reader
    ).length;
  });
}

function displayBorrow(borrow) {
  const reader = readerForBorrow(borrow);
  const book = bookForBorrow(borrow);
  return {
    reader: reader ? reader.name : borrow.readerName,
    book: book ? book.title : borrow.bookTitle
  };
}

function formatDate(value) {
  if (!value) return '—';
  const parts = value.split('-');
  return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
}

function badge(status) {
  const className = {
    'Đang mượn': 'badge-blue',
    'Đã trả': 'badge-green',
    'Quá hạn': 'badge-red'
  }[status] || 'badge-gray';
  return `<span class="badge ${className}">${escapeHTML(status)}</span>`;
}

function renderBookCard(book, showManageActions = true) {
  const available = Number(book.available) > 0;
  const isbn = COVER_ISBNS[book.id];
  const coverKey = `${book.id}:${isbn || ''}`;
  const coverURL = isbn && !failedCovers.has(coverKey)
    ? `https://covers.openlibrary.org/b/isbn/${isbn}-M.jpg?default=false`
    : '';
  const categoryIndex = Math.max(0, categories.findIndex(category => category.name === book.category));
  return `<article class="book-card">
    <div class="book-cover cover-tone-${categoryIndex % CATEGORY_COLORS.length} ${coverURL ? 'is-loading' : 'is-fallback'}" data-cover-key="${escapeHTML(coverKey)}">
      <div class="cover-fallback" aria-hidden="true">
        <span>THƯ VIỆN MÂY</span>
        <strong>${escapeHTML(book.title)}</strong>
        <small>${escapeHTML(book.author)}</small>
      </div>
      ${coverURL ? `<img src="${coverURL}" alt="Bìa sách ${escapeHTML(book.title)}" loading="lazy">` : ''}
      <span class="book-status ${available ? 'is-available' : 'is-unavailable'}">${available ? 'Còn sách' : 'Đang hết'}</span>
    </div>
    <div class="book-card-content">
      <span class="book-category">${escapeHTML(book.category || 'Chưa phân loại')}</span>
      <h3 title="${escapeHTML(book.title)}">${escapeHTML(book.title)}</h3>
      <p class="book-author">${escapeHTML(book.author)}</p>
      <div class="book-card-meta"><span>${escapeHTML(book.id)}</span><span>${escapeHTML(book.available)} / ${escapeHTML(book.total)} bản</span></div>
      <div class="book-card-actions">
        <button class="btn btn-primary btn-sm" data-action="borrow-book" data-id="${escapeHTML(book.id)}" ${available ? '' : 'disabled'}>Mượn sách</button>
        ${showManageActions
          ? `<button class="btn btn-icon-text btn-sm" data-action="edit-book" data-id="${escapeHTML(book.id)}" aria-label="Sửa ${escapeHTML(book.title)}">✎</button>
             <button class="btn btn-icon-text btn-sm delete-action" data-action="delete-book" data-id="${escapeHTML(book.id)}" aria-label="Xóa ${escapeHTML(book.title)}">⌫</button>`
          : ''}
      </div>
    </div>
  </article>`;
}

function bindBookCoverFallbacks() {
  document.querySelectorAll('.book-cover img').forEach(image => {
    if (image.dataset.coverBound) return;
    image.dataset.coverBound = 'true';
    image.addEventListener('load', () => {
      image.parentElement.classList.remove('is-loading');
    }, { once: true });
    image.addEventListener('error', () => {
      const cover = image.parentElement;
      failedCovers.add(cover.dataset.coverKey);
      cover.classList.remove('is-loading');
      cover.classList.add('is-fallback');
      image.remove();
    }, { once: true });
    if (image.complete) {
      if (image.naturalWidth > 0) image.parentElement.classList.remove('is-loading');
      else image.dispatchEvent(new Event('error'));
    }
  });
}

function openSection(section) {
  if (!pageTitles[section]) return;
  document.querySelectorAll('.page-section').forEach(element => element.classList.remove('active'));
  document.getElementById(`section-${section}`).classList.add('active');
  document.querySelectorAll('.nav-item[data-section]').forEach(element => {
    element.classList.toggle('active', element.dataset.section === section);
  });
  document.getElementById('pageTitle').textContent = pageTitles[section];
  document.getElementById('sidebar').classList.remove('open');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderHome() {
  document.getElementById('statBooks').textContent = books.reduce((sum, book) => sum + Number(book.total || 0), 0);
  document.getElementById('statReaders').textContent = readers.length;
  document.getElementById('statBorrowing').textContent = borrows.filter(isActiveBorrow).length;
  document.getElementById('statOverdue').textContent = borrows.filter(borrow => borrow.status === 'Quá hạn').length;

  const popular = [...books].sort((first, second) => {
    const firstCount = borrows.filter(borrow => bookForBorrow(borrow) === first).length;
    const secondCount = borrows.filter(borrow => bookForBorrow(borrow) === second).length;
    return secondCount - firstCount || first.title.localeCompare(second.title, 'vi');
  }).slice(0, 5);
  document.getElementById('popularBooks').innerHTML = popular.length
    ? popular.map(book => renderBookCard(book, false)).join('')
    : '<div class="empty-state"><span class="empty-icon">📚</span><strong>Chưa có sách trong thư viện</strong></div>';
  bindBookCoverFallbacks();

  const recent = [...borrows].sort((first, second) => second.startDate.localeCompare(first.startDate)).slice(0, 5);
  document.getElementById('recentActivity').innerHTML = recent.length ? recent.map(borrow => {
    const names = displayBorrow(borrow);
    return `<div class="activity-item">
      <span class="activity-icon">${borrow.status === 'Đã trả' ? '✓' : '↗'}</span>
      <div class="activity-copy"><strong>${escapeHTML(names.reader)}</strong><span>${borrow.status === 'Đã trả' ? 'đã trả' : 'đang mượn'} “${escapeHTML(names.book)}”</span><small>${formatDate(borrow.startDate)} · ${escapeHTML(borrow.code)}</small></div>
    </div>`;
  }).join('') : '<p class="inline-empty">Chưa có hoạt động nào.</p>';

  const overdue = borrows.filter(borrow => borrow.status === 'Quá hạn').slice(0, 3);
  const notificationDot = document.querySelector('.notification-dot');
  if (notificationDot) notificationDot.hidden = overdue.length === 0;
  document.getElementById('overdueActivity').innerHTML = overdue.length ? overdue.map(borrow => {
    const names = displayBorrow(borrow);
    return `<div class="overdue-item"><span class="overdue-mark">!</span><div><strong>${escapeHTML(names.reader)}</strong><span>${escapeHTML(names.book)}</span><small>Hạn trả ${formatDate(borrow.dueDate)}</small></div></div>`;
  }).join('') : '<p class="inline-empty success-copy">Không có sách quá hạn.</p>';

  const counts = categories.map(category => books.filter(book => book.category === category.name).length);
  const max = Math.max(...counts, 1);
  document.getElementById('categoryStats').innerHTML = categories.length
    ? categories.map((category, index) => {
      const count = counts[index];
      return `<div class="cat-stat-row">
        <div class="row-top"><span>${escapeHTML(category.name)}</span><span>${count} đầu sách</span></div>
        <div class="progress"><span style="width:${Math.round(count / max * 100)}%; background:${CATEGORY_COLORS[index % CATEGORY_COLORS.length]}"></span></div>
      </div>`;
    }).join('')
    : '<p class="inline-empty">Chưa có dữ liệu thể loại.</p>';
}

function renderCategoryOptions(selected = '') {
  const filter = document.getElementById('bookCategoryFilter');
  const current = selected || filter.value;
  filter.innerHTML = '<option value="">Tất cả thể loại</option>' +
    categories.map(category =>
      `<option value="${escapeHTML(category.name)}">${escapeHTML(category.name)}</option>`
    ).join('');
  filter.value = categories.some(category => category.name === current) ? current : '';
}

function renderBooks() {
  renderCategoryOptions();
  const keyword = normalizeText(document.getElementById('bookSearch').value.trim());
  const category = document.getElementById('bookCategoryFilter').value;
  const availability = document.getElementById('bookAvailabilityFilter').value;
  const rows = books.filter(book => {
    const searchable = normalizeText([book.id, book.title, book.author, book.category, book.year].join(' '));
    return (!keyword || searchable.includes(keyword)) &&
      (!category || book.category === category) &&
      (!availability || (availability === 'available' ? book.available > 0 : book.available <= 0));
  });

  document.getElementById('booksGrid').innerHTML = rows.map(book => renderBookCard(book)).join('');
  document.getElementById('booksEmpty').hidden = rows.length !== 0;
  bindBookCoverFallbacks();
}

function openBookModal(id = '') {
  const book = books.find(item => item.id === id) || {
    id: '', title: '', author: '', category: categories[0]?.name || '', year: new Date().getFullYear(), total: 1, available: 1
  };
  const onLoan = Number(book.total) - Number(book.available);
  const categoryOptions = categories.map(category =>
    `<option value="${escapeHTML(category.name)}" ${category.name === book.category ? 'selected' : ''}>${escapeHTML(category.name)}</option>`
  ).join('');
  openModal(id ? 'Sửa thông tin sách' : 'Thêm sách mới', `
    <div class="form-group"><label for="bId">Mã sách *</label>
      <input id="bId" value="${escapeHTML(book.id)}" placeholder="VD: B010" ${id ? 'readonly' : ''} required></div>
    <div class="form-group"><label for="bTitle">Tiêu đề *</label>
      <input id="bTitle" value="${escapeHTML(book.title)}" placeholder="Tên sách" required></div>
    <div class="form-group"><label for="bAuthor">Tác giả *</label>
      <input id="bAuthor" value="${escapeHTML(book.author)}" placeholder="Tên tác giả" required></div>
    <div class="form-row">
      <div class="form-group"><label for="bCategory">Thể loại *</label>
        <select id="bCategory" required>${categoryOptions}</select></div>
      <div class="form-group"><label for="bYear">Năm xuất bản *</label>
        <input id="bYear" type="number" value="${escapeHTML(book.year)}" min="1" max="${new Date().getFullYear()}" required></div>
    </div>
    <div class="form-group"><label for="bTotal">Tổng số bản *</label>
      <input id="bTotal" type="number" value="${escapeHTML(book.total)}" min="${Math.max(1, onLoan)}" required>
      <small>Đang có ${onLoan} bản được mượn; tổng số bản không thể thấp hơn số này.</small></div>`,
    () => saveBook(id));
}

function saveBook(id) {
  const bookId = document.getElementById('bId').value.trim();
  const title = document.getElementById('bTitle').value.trim();
  const author = document.getElementById('bAuthor').value.trim();
  const category = document.getElementById('bCategory').value;
  const year = Number(document.getElementById('bYear').value);
  const total = Number(document.getElementById('bTotal').value);
  const existing = books.find(book => book.id === id);
  const onLoan = existing ? Number(existing.total) - Number(existing.available) : 0;

  if (!bookId || !title || !author || !category) {
    showNotice('Vui lòng điền đầy đủ mã sách, tiêu đề, tác giả và thể loại.', true);
    return;
  }
  if (!Number.isInteger(year) || year < 1 || year > new Date().getFullYear()) {
    showNotice('Năm xuất bản không hợp lệ.', true);
    return;
  }
  if (!Number.isInteger(total) || total < Math.max(1, onLoan)) {
    showNotice('Tổng số bản phải là số nguyên và không thấp hơn số bản đang được mượn.', true);
    return;
  }
  if (!id && books.some(book => normalizeText(book.id) === normalizeText(bookId))) {
    showNotice('Mã sách đã tồn tại.', true);
    return;
  }

  const data = { title, author, category, year, total, available: total - onLoan };
  if (existing) Object.assign(existing, data);
  else books.unshift({ id: bookId, ...data });
  finishMutation('Đã lưu thông tin sách.');
}

function deleteBook(id) {
  const book = books.find(item => item.id === id);
  if (!book) return;
  if (borrows.some(borrow => bookForBorrow(borrow) === book)) {
    showNotice('Không thể xóa sách đã có lịch sử mượn. Hãy giữ lại để bảo toàn lịch sử phiếu.', true);
    return;
  }
  if (!window.confirm(`Bạn có chắc muốn xóa sách "${book.title}"?`)) return;
  books = books.filter(item => item.id !== id);
  finishMutation('Đã xóa sách.');
}

function renderReaders() {
  const keyword = normalizeText(document.getElementById('readerSearch').value.trim());
  const rows = readers.filter(reader =>
    normalizeText([reader.card, reader.name, reader.gender, reader.phone, reader.email].join(' ')).includes(keyword)
  );
  document.getElementById('readersTable').innerHTML = rows.map(reader => `
    <tr>
      <td>${escapeHTML(reader.card)}</td>
      <td><strong>${escapeHTML(reader.name)}</strong></td>
      <td>${escapeHTML(reader.gender)}</td>
      <td>${escapeHTML(reader.phone || '—')}</td>
      <td>${escapeHTML(reader.email || '—')}</td>
      <td>${reader.borrowing
        ? `<span class="badge badge-orange">${reader.borrowing} phiếu</span>`
        : '<span class="badge badge-gray">0</span>'}</td>
      <td><div class="actions">
        <button class="btn btn-outline btn-sm" data-action="edit-reader" data-id="${escapeHTML(reader.card)}">Sửa</button>
        <button class="btn btn-danger btn-sm" data-action="delete-reader" data-id="${escapeHTML(reader.card)}">Xóa</button>
      </div></td>
    </tr>`).join('');
  document.getElementById('readersEmpty').hidden = rows.length !== 0;
}

function openReaderModal(card = '') {
  const reader = readers.find(item => item.card === card) || {
    card: '', name: '', gender: 'Nam', phone: '', email: ''
  };
  openModal(card ? 'Sửa thông tin độc giả' : 'Thêm độc giả mới', `
    <div class="form-group"><label for="rCode">Mã thẻ *</label>
      <input id="rCode" value="${escapeHTML(reader.card)}" placeholder="VD: DG006" ${card ? 'readonly' : ''} required></div>
    <div class="form-group"><label for="rName">Họ tên *</label>
      <input id="rName" value="${escapeHTML(reader.name)}" placeholder="Họ và tên độc giả" required></div>
    <div class="form-row">
      <div class="form-group"><label for="rGender">Giới tính</label>
        <select id="rGender">
          <option value="Nam" ${reader.gender === 'Nam' ? 'selected' : ''}>Nam</option>
          <option value="Nữ" ${reader.gender === 'Nữ' ? 'selected' : ''}>Nữ</option>
          <option value="Khác" ${reader.gender === 'Khác' ? 'selected' : ''}>Khác</option>
        </select></div>
      <div class="form-group"><label for="rPhone">Số điện thoại</label>
        <input id="rPhone" type="tel" value="${escapeHTML(reader.phone)}" placeholder="0xxxxxxxxx"></div>
    </div>
    <div class="form-group"><label for="rEmail">Email</label>
      <input id="rEmail" type="email" value="${escapeHTML(reader.email)}" placeholder="email@example.com"></div>`,
    () => saveReader(card));
}

function saveReader(card) {
  const readerCard = document.getElementById('rCode').value.trim();
  const name = document.getElementById('rName').value.trim();
  const gender = document.getElementById('rGender').value;
  const phone = document.getElementById('rPhone').value.trim();
  const email = document.getElementById('rEmail').value.trim();
  if (!readerCard || !name) {
    showNotice('Vui lòng nhập mã thẻ và họ tên độc giả.', true);
    return;
  }
  if (phone && !/^[0-9+\s().-]{9,16}$/.test(phone)) {
    showNotice('Số điện thoại không hợp lệ.', true);
    return;
  }
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotice('Địa chỉ email không hợp lệ.', true);
    return;
  }
  if (!card && readers.some(reader => normalizeText(reader.card) === normalizeText(readerCard))) {
    showNotice('Mã thẻ đã tồn tại.', true);
    return;
  }

  const existing = readers.find(reader => reader.card === card);
  if (existing) Object.assign(existing, { name, gender, phone, email });
  else readers.unshift({ card: readerCard, name, gender, phone, email, borrowing: 0 });
  finishMutation('Đã lưu thông tin độc giả.');
}

function deleteReader(card) {
  const reader = readers.find(item => item.card === card);
  if (!reader) return;
  if (borrows.some(borrow => isActiveBorrow(borrow) && readerForBorrow(borrow) === reader)) {
    showNotice('Độc giả còn phiếu mượn chưa trả nên không thể xóa.', true);
    return;
  }
  if (!window.confirm(`Bạn có chắc muốn xóa độc giả "${reader.name}"?`)) return;
  readers = readers.filter(item => item.card !== card);
  finishMutation('Đã xóa độc giả.');
}

function renderBorrows() {
  const keyword = normalizeText(document.getElementById('borrowSearch').value.trim());
  const status = document.getElementById('borrowStatusFilter').value;
  const rows = [...borrows]
    .sort((first, second) => second.startDate.localeCompare(first.startDate))
    .filter(borrow => {
      const names = displayBorrow(borrow);
      const searchable = normalizeText([
        borrow.code, names.reader, names.book, borrow.startDate, borrow.dueDate, borrow.status
      ].join(' '));
      return searchable.includes(keyword) && (!status || borrow.status === status);
    });

  document.getElementById('borrowsTable').innerHTML = rows.map(borrow => {
    const names = displayBorrow(borrow);
    return `<tr>
      <td>${escapeHTML(borrow.code)}</td>
      <td>${escapeHTML(names.reader)}</td>
      <td>${escapeHTML(names.book)}</td>
      <td>${formatDate(borrow.startDate)}</td>
      <td>${formatDate(borrow.dueDate)}</td>
      <td>${badge(borrow.status)}</td>
      <td>${isActiveBorrow(borrow)
        ? `<button class="btn btn-success btn-sm" data-action="return-book" data-id="${escapeHTML(borrow.code)}">Trả sách</button>`
        : '<span class="badge badge-gray">Hoàn tất</span>'}</td>
    </tr>`;
  }).join('');
  document.getElementById('borrowsEmpty').hidden = rows.length !== 0;
}

function nextBorrowCode() {
  const next = borrows.reduce((largest, borrow) => {
    const number = Number(String(borrow.code).replace(/^\D+/, ''));
    return Number.isFinite(number) ? Math.max(largest, number) : largest;
  }, 0) + 1;
  return `PM${String(next).padStart(3, '0')}`;
}

function openBorrowModal(preselectedBookId = '') {
  if (!readers.length) {
    showNotice('Cần thêm độc giả trước khi lập phiếu mượn.', true);
    return;
  }
  if (!books.some(book => book.available > 0)) {
    showNotice('Hiện không còn bản sách nào có thể cho mượn.', true);
    return;
  }
  const readerOptions = readers.map(reader =>
    `<option value="${escapeHTML(reader.card)}">${escapeHTML(reader.name)} (${escapeHTML(reader.card)})</option>`
  ).join('');
  const bookOptions = books.filter(book => book.available > 0).map(book =>
    `<option value="${escapeHTML(book.id)}" ${book.id === preselectedBookId ? 'selected' : ''}>${escapeHTML(book.title)} — còn ${book.available}</option>`
  ).join('');
  const today = dateString(new Date());
  openModal('Lập phiếu mượn mới', `
    <div class="form-group"><label for="pReader">Độc giả *</label><select id="pReader" required>${readerOptions}</select></div>
    <div class="form-group"><label for="pBook">Sách *</label><select id="pBook" required>${bookOptions}</select></div>
    <div class="form-row">
      <div class="form-group"><label for="pStart">Ngày mượn *</label>
        <input id="pStart" type="date" value="${today}" max="${today}" required></div>
      <div class="form-group"><label for="pDue">Hạn trả *</label>
        <input id="pDue" type="date" value="${dateOffset(14)}" min="${today}" required></div>
    </div>`,
    saveBorrow);
}

function saveBorrow() {
  const readerCard = document.getElementById('pReader').value;
  const bookId = document.getElementById('pBook').value;
  const startDate = document.getElementById('pStart').value;
  const dueDate = document.getElementById('pDue').value;
  const reader = readers.find(item => item.card === readerCard);
  const book = books.find(item => item.id === bookId);
  const today = dateString(new Date());
  if (!reader || !book || book.available < 1) {
    showNotice('Độc giả hoặc sách không còn hợp lệ. Vui lòng kiểm tra lại.', true);
    return;
  }
  if (!startDate || !dueDate || startDate > today || dueDate < startDate) {
    showNotice('Ngày mượn phải hợp lệ và hạn trả không được trước ngày mượn.', true);
    return;
  }

  borrows.unshift({
    code: nextBorrowCode(),
    readerCard: reader.card,
    readerName: reader.name,
    bookId: book.id,
    bookTitle: book.title,
    startDate,
    dueDate,
    status: dueDate < today ? 'Quá hạn' : 'Đang mượn'
  });
  finishMutation('Đã lập phiếu mượn.');
}

function returnBook(code) {
  const borrow = borrows.find(item => item.code === code);
  if (!borrow || !isActiveBorrow(borrow)) return;
  borrow.status = 'Đã trả';
  borrow.returnedDate = dateString(new Date());
  finishMutation(`Đã ghi nhận trả sách cho phiếu ${code}.`);
}

function renderCategories() {
  const categoryCounts = new Map(categories.map(category => [
    category.id,
    books.filter(book => book.category === category.name).length
  ]));
  document.getElementById('categoriesList').innerHTML = categories.map(category => `
    <div class="category-chip">
      <div>
        <strong>${escapeHTML(category.name)}</strong><br>
        <span class="count">${categoryCounts.get(category.id)} đầu sách</span>
      </div>
      <div class="actions">
        <button class="btn btn-outline btn-sm" data-action="edit-category" data-id="${escapeHTML(category.id)}">Sửa</button>
        <button class="btn btn-danger btn-sm" data-action="delete-category" data-id="${escapeHTML(category.id)}">Xóa</button>
      </div>
    </div>`).join('');
  document.getElementById('categoriesEmpty').hidden = categories.length !== 0;
}

function renderStats() {
  const activeLoans = borrows.filter(isActiveBorrow);
  const overdue = activeLoans.filter(borrow => borrow.status === 'Quá hạn');
  const availableCopies = books.reduce((sum, book) => sum + Number(book.available || 0), 0);
  document.getElementById('statsSummary').innerHTML = [
    ['📚', 'Đầu sách', books.length, 'bg-blue'],
    ['📦', 'Bản có sẵn', availableCopies, 'bg-green'],
    ['🔁', 'Đang mượn', activeLoans.length, 'bg-orange'],
    ['⏰', 'Quá hạn', overdue.length, 'bg-red']
  ].map(([icon, label, value, color]) => `
    <div class="stat-card"><div class="stat-icon ${color}">${icon}</div><div><p>${label}</p><h3>${value}</h3></div></div>
  `).join('');

  const counts = categories.map(category => books.filter(book => book.category === category.name).length);
  const max = Math.max(...counts, 1);
  document.getElementById('statsCategories').innerHTML = categories.length
    ? categories.map((category, index) => `
      <div class="cat-stat-row">
        <div class="row-top"><span>${escapeHTML(category.name)}</span><span>${counts[index]} đầu sách</span></div>
        <div class="progress"><span style="width:${Math.round(counts[index] / max * 100)}%; background:${CATEGORY_COLORS[index % CATEGORY_COLORS.length]}"></span></div>
      </div>`).join('')
    : '<p class="inline-empty">Chưa có dữ liệu thể loại.</p>';

  const ranking = [...books].map(book => ({
    book,
    count: borrows.filter(borrow => bookForBorrow(borrow) === book).length
  })).sort((first, second) => second.count - first.count || first.book.title.localeCompare(second.book.title, 'vi'));
  document.getElementById('statsPopularBooks').innerHTML = ranking.length
    ? ranking.slice(0, 5).map((item, index) => `
      <div class="ranking-item"><span class="ranking-number">${String(index + 1).padStart(2, '0')}</span><div><strong>${escapeHTML(item.book.title)}</strong><small>${escapeHTML(item.book.author)}</small></div><span class="ranking-count">${item.count} lượt</span></div>
    `).join('')
    : '<p class="inline-empty">Chưa có dữ liệu mượn sách.</p>';
}

function openCategoryModal(id = '') {
  const category = categories.find(item => String(item.id) === String(id));
  openModal(category ? 'Sửa thể loại' : 'Thêm thể loại mới', `
    <div class="form-group"><label for="cName">Tên thể loại *</label>
      <input id="cName" value="${escapeHTML(category?.name || '')}" placeholder="VD: Thiếu nhi" required></div>`,
    () => saveCategory(id));
}

function saveCategory(id) {
  const name = document.getElementById('cName').value.trim();
  if (!name) {
    showNotice('Vui lòng nhập tên thể loại.', true);
    return;
  }
  if (categories.some(category =>
    String(category.id) !== String(id) && normalizeText(category.name) === normalizeText(name)
  )) {
    showNotice('Tên thể loại đã tồn tại.', true);
    return;
  }

  const category = categories.find(item => String(item.id) === String(id));
  if (category) {
    const previousName = category.name;
    category.name = name;
    books.forEach(book => { if (book.category === previousName) book.category = name; });
  } else {
    const newId = categories.reduce((largest, item) => Math.max(largest, Number(item.id) || 0), 0) + 1;
    categories.push({ id: newId, name });
  }
  finishMutation('Đã lưu thể loại.');
}

function deleteCategory(id) {
  const category = categories.find(item => String(item.id) === String(id));
  if (!category) return;
  if (books.some(book => book.category === category.name)) {
    showNotice('Thể loại vẫn đang được sách sử dụng. Hãy chuyển sách sang thể loại khác trước khi xóa.', true);
    return;
  }
  if (!window.confirm(`Bạn có chắc muốn xóa thể loại "${category.name}"?`)) return;
  categories = categories.filter(item => String(item.id) !== String(id));
  finishMutation('Đã xóa thể loại.');
}

function openModal(title, bodyHTML, confirmFn) {
  modalConfirmFn = confirmFn;
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = `${bodyHTML}
    <div class="modal-actions">
      <button class="btn btn-outline" type="button" data-action="close-modal">Hủy</button>
      <button class="btn btn-primary" type="button" data-action="save-modal">Lưu</button>
    </div>`;
  document.getElementById('modalOverlay').hidden = false;
  document.getElementById('modalBody').querySelector('input, select')?.focus();
}

function closeModal() {
  document.getElementById('modalOverlay').hidden = true;
  modalConfirmFn = null;
}

function finishMutation(message) {
  syncDerivedData();
  const saved = persistData();
  renderAll();
  closeModal();
  if (message && saved) showNotice(message);
}

function renderAll() {
  syncDerivedData();
  renderHome();
  renderBooks();
  renderReaders();
  renderBorrows();
  renderCategories();
  renderStats();
}

function setTheme(isDark, shouldPersist = true) {
  document.body.classList.toggle('dark-theme', isDark);
  const themeButton = document.getElementById('themeToggle');
  if (themeButton) {
    themeButton.textContent = isDark ? '☀' : '☾';
    themeButton.setAttribute('aria-label', isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối');
    themeButton.title = isDark ? 'Giao diện sáng' : 'Giao diện tối';
  }
  if (!shouldPersist) return;
  try {
    window.localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
  } catch (error) {
    showNotice('Không thể lưu tùy chọn giao diện trên thiết bị này.', true);
  }
}

function toggleTheme() {
  setTheme(!document.body.classList.contains('dark-theme'));
}

function showNotificationSummary() {
  const overdueCount = borrows.filter(borrow => borrow.status === 'Quá hạn').length;
  showNotice(overdueCount
    ? `Thư viện đang có ${overdueCount} phiếu mượn quá hạn cần xử lý.`
    : 'Hiện không có phiếu mượn nào quá hạn.');
}

function resetData() {
  if (!window.confirm('Khôi phục dữ liệu mẫu sẽ thay thế toàn bộ sách, độc giả, thể loại và phiếu mượn hiện tại. Bạn có muốn tiếp tục?')) return;
  const data = cloneSeedData();
  categories = data.categories;
  books = data.books;
  readers = data.readers;
  borrows = data.borrows;
  syncDerivedData();
  const saved = persistData();
  renderAll();
  if (saved) showNotice('Đã khôi phục dữ liệu mẫu.');
}

function runGlobalSearch(query) {
  const keyword = normalizeText(query.trim());
  if (!keyword) return;

  const book = books.find(item => normalizeText([item.id, item.title, item.author, item.category].join(' ')).includes(keyword));
  if (book) {
    document.getElementById('bookSearch').value = query.trim();
    renderBooks();
    openSection('books');
    return;
  }
  const reader = readers.find(item =>
    normalizeText([item.card, item.name, item.phone, item.email].join(' ')).includes(keyword)
  );
  if (reader) {
    document.getElementById('readerSearch').value = query.trim();
    renderReaders();
    openSection('readers');
    return;
  }
  const borrow = borrows.find(item => {
    const names = displayBorrow(item);
    return normalizeText([item.code, names.reader, names.book].join(' ')).includes(keyword);
  });
  if (borrow) {
    document.getElementById('borrowSearch').value = query.trim();
    renderBorrows();
    openSection('borrows');
    return;
  }
  const category = categories.find(item => normalizeText(item.name).includes(keyword));
  if (category) {
    openSection('categories');
    return;
  }
  showNotice(`Không tìm thấy kết quả cho "${query.trim()}".`);
}

function handleDashboardClick(event) {
  const target = event.target.closest('[data-action]');
  if (!target) return;
  const { action, id } = target.dataset;
  switch (action) {
    case 'edit-book': openBookModal(id); break;
    case 'delete-book': deleteBook(id); break;
    case 'edit-reader': openReaderModal(id); break;
    case 'delete-reader': deleteReader(id); break;
    case 'return-book': returnBook(id); break;
    case 'borrow-book': openBorrowModal(id); break;
    case 'edit-category': openCategoryModal(id); break;
    case 'delete-category': deleteCategory(id); break;
    case 'close-modal': closeModal(); break;
    case 'save-modal': if (modalConfirmFn) modalConfirmFn(); break;
    case 'add-book': openBookModal(); break;
    case 'add-reader': openReaderModal(); break;
    case 'add-borrow': openBorrowModal(); break;
    case 'add-category': openCategoryModal(); break;
    case 'toggle-theme': toggleTheme(); break;
    case 'notifications': showNotificationSummary(); break;
    case 'reset-data': resetData(); break;
    case 'logout':
      event.preventDefault();
      window.sessionStorage.removeItem(AUTH_KEY);
      window.location.href = 'index.html';
      break;
    default: break;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.sessionStorage.getItem(AUTH_KEY) !== 'true') {
    window.location.replace('index.html');
    return;
  }

  const data = loadData();
  categories = data.categories;
  books = data.books;
  readers = data.readers;
  borrows = data.borrows;
  try {
    setTheme(window.localStorage.getItem(THEME_KEY) === 'dark', false);
  } catch (error) {
    showNotice('Không thể đọc tùy chọn giao diện đã lưu.', true);
  }
  syncDerivedData();
  persistData();
  renderAll();

  document.addEventListener('click', handleDashboardClick);
  document.querySelectorAll('.nav-item[data-section]').forEach(item => {
    item.addEventListener('click', event => {
      event.preventDefault();
      openSection(item.dataset.section);
    });
  });
  document.querySelectorAll('[data-goto]').forEach(item => {
    item.addEventListener('click', event => {
      event.preventDefault();
      openSection(item.dataset.goto);
    });
  });
  document.getElementById('menuToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
  });
  document.getElementById('bookSearch').addEventListener('input', renderBooks);
  document.getElementById('bookCategoryFilter').addEventListener('change', renderBooks);
  document.getElementById('bookAvailabilityFilter').addEventListener('change', renderBooks);
  document.getElementById('readerSearch').addEventListener('input', renderReaders);
  document.getElementById('borrowSearch').addEventListener('input', renderBorrows);
  document.getElementById('borrowStatusFilter').addEventListener('change', renderBorrows);
  document.getElementById('globalSearch').addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      runGlobalSearch(event.currentTarget.value);
      event.currentTarget.value = '';
    }
  });
  document.getElementById('modalClose').addEventListener('click', closeModal);
  document.getElementById('modalOverlay').addEventListener('click', event => {
    if (event.target === event.currentTarget) closeModal();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeModal();
      document.getElementById('sidebar').classList.remove('open');
    }
  });
});
