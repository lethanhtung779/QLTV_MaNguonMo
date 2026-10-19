(() => {
  const mobileMenu = document.querySelector('[data-mobile-menu]');
  const sidebar = document.querySelector('[data-sidebar]');
  const backdrop = document.querySelector('[data-backdrop]');

  const closeMenus = () => {
    mobileMenu?.classList.remove('is-open');
    sidebar?.classList.remove('is-open');
    backdrop?.setAttribute('hidden', '');
    document.querySelectorAll('[data-menu-toggle]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
  };

  document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.closest('.public-header') ? mobileMenu : sidebar;
      const open = !target?.classList.contains('is-open');
      closeMenus();
      if (open && target) {
        target.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        if (target === sidebar && backdrop) backdrop.hidden = false;
      }
    });
  });
  backdrop?.addEventListener('click', closeMenus);
  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) closeMenus();
  });

  document.querySelectorAll('[data-dialog-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.dialogOpen);
      const form = dialog?.querySelector('[data-dialog-form]');
      const title = dialog?.querySelector('[data-dialog-title]');
      if (!dialog) return;
      if (!form) { dialog.showModal(); return; }
      form.reset();
      const totalField = form.elements.namedItem('total_copies');
      if (totalField) totalField.min = '1';
      const idField = form.elements.namedItem('id');
      if (idField) idField.value = '0';
      if (title) title.textContent = dialog.id === 'bookDialog' ? 'Thêm đầu sách' : dialog.id === 'readerDialog' ? 'Thêm độc giả' : dialog.id === 'loanDialog' ? 'Lập phiếu mượn' : 'Thêm thể loại';
      const item = button.dataset.book || button.dataset.category || button.dataset.reader;
      if (item) {
        try {
          const values = JSON.parse(item);
          Object.entries(values).forEach(([key, value]) => {
            const field = form.elements.namedItem(key);
            if (field && !field.type?.startsWith('submit')) field.value = value ?? (key === 'category_id' ? '0' : '');
          });
          if (totalField) totalField.min = Number(values.id) > 0 ? '0' : '1';
          if (title) title.textContent = dialog.id === 'bookDialog' ? 'Cập nhật sách' : dialog.id === 'readerDialog' ? 'Cập nhật độc giả' : 'Cập nhật thể loại';
        } catch (error) {
          console.error('Không thể nạp thông tin biểu mẫu.', error);
        }
      }
      dialog.showModal();
    });
  });
  document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
  document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
  }));

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = button.closest('.password-field')?.querySelector('input');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? 'Hiện' : 'Ẩn';
      button.setAttribute('aria-label', input.type === 'password' ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
    });
  });
  document.querySelectorAll('[data-validate-password]').forEach((form) => {
    const password = form.querySelector('[data-password-input]');
    const confirmation = form.querySelector('[data-password-confirmation]');
    const validate = () => {
      if (confirmation) confirmation.setCustomValidity(confirmation.value && password?.value !== confirmation.value ? 'Mật khẩu xác nhận không khớp.' : '');
      if (password && form.action.includes('/register')) {
        const valid = password.value.length >= 12 && /[A-Z]/.test(password.value) && /[a-z]/.test(password.value) && /[0-9]/.test(password.value);
        password.setCustomValidity(valid ? '' : 'Mật khẩu cần tối thiểu 12 ký tự, gồm chữ hoa, chữ thường và số.');
      }
    };
    password?.addEventListener('input', validate);
    confirmation?.addEventListener('input', validate);
  });
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (event.defaultPrevented) return;
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }
      if (!navigator.onLine) {
        event.preventDefault();
        let notice = form.querySelector('[data-network-error]');
        if (!notice) {
          notice = document.createElement('div');
          notice.className = 'flash-message error';
          notice.setAttribute('role', 'alert');
          notice.setAttribute('data-network-error', '');
          form.prepend(notice);
        }
        notice.textContent = 'Bạn đang mất kết nối mạng. Thông tin đã nhập được giữ lại; hãy kết nối lại rồi thử gửi.';
        notice.scrollIntoView({ block: 'nearest' });
        return;
      }
      form.querySelector('[data-network-error]')?.remove();
      const submitter = event.submitter;
      if (submitter && submitter.type !== 'button') {
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.textContent;
        submitter.textContent = 'Đang xử lý…';
      }
    });
  });
  document.querySelectorAll('img[data-cover-fallback]').forEach((image) => {
    const showFallback = () => {
      const fallback = image.nextElementSibling;
      if (fallback?.matches('[data-book-cover-placeholder]')) fallback.hidden = false;
      image.hidden = true;
    };
    image.addEventListener('error', showFallback, { once: true });
    if (image.complete && image.naturalWidth === 0) showFallback();
  });
})();

document.querySelectorAll('select[name="copy_ids[]"][multiple]').forEach(select => {
  const picker = document.createElement('div'); picker.className = 'copy-picker';
  const search = document.createElement('input'); search.type = 'search'; search.placeholder = 'Tìm tên sách hoặc mã bản sao'; search.setAttribute('aria-label','Tìm bản sao');
  const count = document.createElement('small'); count.setAttribute('aria-live','polite');
  const rows = [];
  const maxCopies = Number(select.dataset.maxCopies) || Infinity;
  Array.from(select.options).forEach(option => {
    const label=document.createElement('label');label.className='choice-row';
    const input=document.createElement('input'); input.type='checkbox';input.value=option.value;
    const text=document.createElement('span');text.textContent=option.textContent;
    input.addEventListener('change',()=>{option.selected=input.checked;update();});
    label.append(input,text);picker.append(label);rows.push({option,input,label});
  });
  const update=()=>{
    rows.forEach(row=>row.input.checked=row.option.selected);
    const selected = select.selectedOptions.length;
    count.textContent='Đã chọn '+selected+(Number.isFinite(maxCopies)?' / '+maxCopies:'')+' bản sao';
    search.setCustomValidity(selected === 0 ? 'Chọn ít nhất một bản sao sách.' : selected > maxCopies ? 'Chỉ được chọn tối đa '+maxCopies+' bản sao cho một phiếu.' : '');
  };
  search.addEventListener('input',()=>rows.forEach(row=>row.label.hidden=!row.option.textContent.toLocaleLowerCase('vi').includes(search.value.toLocaleLowerCase('vi'))));
  select.addEventListener('change',update);
  select.required=false;select.hidden=true;
  const parent=select.closest('label');parent.firstChild.textContent='Chọn bản sao sách — đánh dấu từng bản cần mượn '; parent.querySelector('.muted')?.remove();
  parent.after(search,count,picker);
  select.form.addEventListener('reset',()=>setTimeout(()=>{
    search.value='';rows.forEach(row=>row.label.hidden=false);update();
  },0));update();
});
