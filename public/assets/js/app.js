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
      if (!dialog || !form) return;
      form.reset();
      const idField = form.elements.namedItem('id');
      if (idField) idField.value = '0';
      if (title) title.textContent = dialog.id === 'bookDialog' ? 'Thêm đầu sách' : dialog.id === 'readerDialog' ? 'Thêm độc giả' : dialog.id === 'loanDialog' ? 'Lập phiếu mượn' : 'Thêm thể loại';
      const item = button.dataset.book || button.dataset.category || button.dataset.reader;
      if (item) {
        try {
          const values = JSON.parse(item);
          Object.entries(values).forEach(([key, value]) => {
            const field = form.elements.namedItem(key);
            if (field && !field.type?.startsWith('submit')) field.value = value ?? '';
          });
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
    if (event.target === dialog) dialog.close();
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
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }
      const submitter = event.submitter;
      if (submitter && submitter.type !== 'button') {
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.textContent;
        submitter.textContent = 'Đang xử lý…';
      }
    });
  });
  document.querySelectorAll('img[data-cover-fallback]').forEach((image) => {
    image.addEventListener('error', () => {
      const fallback = document.createElement('span');
      fallback.className = `book-cover-fallback ${image.dataset.coverFallback}`;
      const initial = document.createElement('span');
      initial.textContent = (image.alt.replace(/^Bìa sách\s*/u, '').trim().charAt(0) || 'P').toLocaleUpperCase('vi');
      const brand = document.createElement('small');
      brand.textContent = 'PAPER & PAGE';
      fallback.append(initial, brand);
      image.replaceWith(fallback);
    }, { once: true });
    if (image.complete && image.naturalWidth === 0) image.dispatchEvent(new Event('error'));
  });
})();
