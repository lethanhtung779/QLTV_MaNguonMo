(() => {
  'use strict';
  const modal = document.getElementById('codePreviewModal');
  if (!modal) return;
  const container = document.getElementById('modalSvgContainer');
  const copyText = document.getElementById('modalCopyCodeText');
  const shelfText = document.getElementById('modalShelfText');
  const directLink = document.getElementById('modalDirectLink');
  const title = document.getElementById('modalCodeTitle');
  const typeLabel = document.getElementById('modalCodeTypeLabel');
  let currentCopyCode = '';

  const close = () => { modal.style.display = 'none'; };
  const open = async (button) => {
    const type = button.dataset.codeType || 'barcode';
    const endpoint = button.dataset.codeEndpoint || '';
    currentCopyCode = button.dataset.copyCode || '';
    copyText.textContent = currentCopyCode;
    shelfText.textContent = button.dataset.shelfLocation ? `⌖ Vị trí lưu kho: ${button.dataset.shelfLocation}` : '⌖ Chưa gán kệ lưu kho';
    directLink.href = endpoint;
    title.textContent = type === 'qr' ? 'Mã QR bản sao' : 'Mã vạch Code 128';
    typeLabel.textContent = type === 'qr' ? 'QUÉT BẰNG CAMERA HOẶC MÁY QUÉT 2D' : 'QUÉT BẰNG ĐẦU ĐỌC MÃ VẠCH 1D';
    container.textContent = 'Đang nạp mã định danh...';
    modal.style.display = 'flex';
    try {
      const response = await fetch(endpoint, { headers: { Accept: 'image/svg+xml' } });
      if (!response.ok || !(response.headers.get('content-type') || '').includes('image/svg+xml')) throw new Error();
      container.innerHTML = await response.text();
    } catch (_) {
      container.textContent = 'Không thể tạo mã cho bản sao này. Vui lòng tải lại trang hoặc liên hệ thủ thư.';
    }
  };

  document.querySelectorAll('[data-code-preview]').forEach(button => button.addEventListener('click', () => open(button)));
  document.querySelectorAll('[data-code-close]').forEach(button => button.addEventListener('click', close));
  modal.addEventListener('click', event => { if (event.target === modal) close(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });

  const citationButton = document.querySelector('[data-copy-citation]');
  citationButton?.addEventListener('click', async () => {
    const citation = citationButton.dataset.copyCitation || '';
    try { await navigator.clipboard.writeText(citation); }
    catch (_) { window.prompt('Nhấn Ctrl+C để sao chép trích dẫn:', citation); return; }
    const label = document.getElementById('citationText');
    if (!label) return;
    const previous = label.textContent;
    label.textContent = '✓ Đã sao chép trích dẫn!';
    setTimeout(() => { label.textContent = previous; }, 2500);
  });

  document.querySelector('[data-code-print]')?.addEventListener('click', () => {
    const svg = container.querySelector('svg');
    if (!svg) return;
    const popup = window.open('', '_blank', 'width=520,height=520');
    if (!popup) { window.print(); return; }
    const wrapper = popup.document.createElement('div');
    wrapper.style.cssText = 'font-family:Arial,sans-serif;text-align:center;padding:24px;color:#302B25';
    const heading = popup.document.createElement('h3'); heading.textContent = 'PAPER HAVEN';
    const code = popup.document.createElement('strong'); code.textContent = `Mã bản sao: ${currentCopyCode}`;
    wrapper.append(heading, popup.document.importNode(svg, true), popup.document.createElement('br'), code);
    popup.document.body.append(wrapper);
    popup.document.title = `Nhãn Paper Haven - ${currentCopyCode}`;
    popup.focus(); popup.print();
  });
})();
