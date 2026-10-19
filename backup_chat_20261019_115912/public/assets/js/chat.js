(() => {
  'use strict';
  const root = document.querySelector('[data-library-chat]');
  if (!root) return;

  const panel = root.querySelector('#library-chat-panel');
  const launch = root.querySelector('.chat-launch');
  const log = root.querySelector('.chat-messages');
  const form = root.querySelector('.chat-form');
  const input = form.querySelector('textarea');
  const sendBtn = form.querySelector('button[type="submit"]');
  const typing = root.querySelector('#chat-typing');
  const errorBar = root.querySelector('#chat-error');
  const errorMsg = errorBar.querySelector('.chat-error-msg');
  const retryBtn = root.querySelector('#chat-retry-btn');
  const suggestionsBox = root.querySelector('.chat-suggestions');
  const statusIndicator = root.querySelector('.status-indicator');
  const statusLabel = root.querySelector('.status-label');

  let busy = false;
  let initialized = false;
  let controller = null;
  let requestVersion = 0;
  let lastScope = null;
  let lastFailedMessage = '';

  const el = (tag, text, cls) => {
    const e = document.createElement(tag);
    if (text !== undefined) e.textContent = text;
    if (cls) e.className = cls;
    return e;
  };

  function formatTime(date = new Date()) {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  }

  function setStatus(state, labelText) {
    if (!statusIndicator || !statusLabel) return;
    statusIndicator.className = 'status-indicator status-' + state;
    statusLabel.textContent = labelText;
  }

  function safeUrl(value, image = false) {
    try {
      const u = new URL(value, location.origin);
      if (u.origin === location.origin && u.pathname.startsWith('/')) return u.href;
      if (image && u.protocol === 'https:' && (u.hostname === 'covers.openlibrary.org' || u.hostname === 'bizweb.dktcdn.net' || u.hostname.endsWith('imgix.net'))) {
        return u.href;
      }
    } catch (_) {}
    return null;
  }

  function addMessage(text, isUser = false, data = {}) {
    const row = el('div', undefined, 'chat-message-row ' + (isUser ? 'is-user' : 'is-assistant'));
    row.setAttribute('aria-label', isUser ? 'Câu hỏi của bạn' : 'Trả lời của Trợ lý Paper Haven');

    const bubble = el('div', text, 'chat-message-content');
    row.append(bubble);

    // If assistant returned books
    if (!isUser && Array.isArray(data.books) && data.books.length > 0) {
      for (const book of data.books) {
        const item = el('div', undefined, 'chat-book-item');

        const fallback = el('span', book.title, 'chat-book-fallback');
        const imageUrl = book.cover_url ? safeUrl(book.cover_url, true) : null;
        if (imageUrl) {
          const img = el('img', undefined, 'chat-book-thumb');
          img.src = imageUrl;
          img.alt = 'Bìa ' + book.title;
          img.loading = 'lazy';
          img.addEventListener('error', () => img.replaceWith(fallback), { once: true });
          item.append(img);
        } else {
          item.append(fallback);
        }

        const details = el('div', undefined, 'chat-book-details');
        details.append(el('span', book.title, 'chat-book-title'));

        const metaParts = [];
        if (book.book_code) metaParts.push('[' + book.book_code + ']');
        if (book.author) metaParts.push(book.author);
        if (book.category) metaParts.push(book.category);
        if (metaParts.length > 0) {
          details.append(el('span', metaParts.join(' · '), 'chat-book-meta'));
        }

        const avail = Number(book.available) || 0;
        const stockEl = el(
          'span',
          avail > 0 ? '✓ Còn ' + avail + ' bản khả dụng' : '✗ Chưa có bản khả dụng',
          'chat-book-stock ' + (avail > 0 ? 'is-available' : 'is-empty')
        );
        details.append(stockEl);

        const href = safeUrl(book.url);
        if (href) {
          const actionWrap = el('div', undefined, 'chat-book-action');
          const a = el('a', 'Xem chi tiết sách →', 'chat-book-link');
          a.href = href;
          actionWrap.append(a);
          details.append(actionWrap);
        }

        item.append(details);
        bubble.append(item);
      }
    }

    // Action links
    if (!isUser && Array.isArray(data.links) && data.links.length > 0) {
      const linksContainer = el('div', undefined, 'chat-action-links');
      for (const item of data.links) {
        const href = safeUrl(item.url);
        if (href) {
          const a = el('a', item.label + ' →', 'chat-action-pill');
          a.href = href;
          linksContainer.append(a);
        }
      }
      bubble.append(linksContainer);
    }

    // Timestamp
    const timeEl = el('span', formatTime(), 'chat-message-time');
    row.append(timeEl);

    log.append(row);

    // Keep log clean if it exceeds 50 messages
    while (log.children.length > 50) {
      log.firstElementChild.remove();
    }

    // Smooth scroll inside messages container
    log.scrollTop = log.scrollHeight;
    return row;
  }

  function setSuggestions(items) {
    suggestionsBox.replaceChildren();
    const list = Array.isArray(items) && items.length > 0
      ? items.slice(0, 5)
      : ['Hoàng tử bé', 'Thời hạn mượn sách bao lâu?', 'Tôi đang mượn sách nào?', 'Gợi ý sách lập trình web'];

    for (const text of list) {
      const btn = el('button', text);
      btn.type = 'button';
      btn.disabled = busy;
      btn.addEventListener('click', () => submitQuestion(text));
      suggestionsBox.append(btn);
    }
  }

  async function apiCall(path, body) {
    const c = new AbortController();
    controller = c;
    const timer = setTimeout(() => c.abort(), 9000);
    try {
      const r = await fetch(root.dataset.endpoint + path, {
        method: body ? 'POST' : 'GET',
        credentials: 'same-origin',
        headers: body ? { 'Content-Type': 'application/json' } : {},
        body: body ? JSON.stringify({ ...body, _csrf: root.dataset.csrf }) : undefined,
        signal: c.signal
      });

      let data;
      try {
        data = await r.json();
      } catch (_) {
        throw Error('Máy chủ chưa phản hồi đúng định dạng. Vui lòng thử lại.');
      }

      if (!r.ok) {
        throw Error(data.error || 'Chưa thể xử lý câu hỏi lúc này.');
      }

      if (data.csrf) root.dataset.csrf = data.csrf;
      if (data.scope) {
        if (lastScope && lastScope !== data.scope) {
          log.replaceChildren();
          addMessage('Phiên làm việc đã thay đổi. Lịch sử trò chuyện trước đã được làm mới.');
        }
        lastScope = data.scope;
      }

      return data;
    } finally {
      clearTimeout(timer);
      if (controller === c) controller = null;
    }
  }

  function setBusy(isBusy) {
    busy = isBusy;
    sendBtn.disabled = isBusy;
    input.disabled = isBusy;
    form.setAttribute('aria-busy', String(isBusy));
    suggestionsBox.querySelectorAll('button').forEach(b => b.disabled = isBusy);

    if (typing) {
      typing.hidden = !isBusy;
      if (isBusy) {
        log.scrollTop = log.scrollHeight;
      }
    }

    if (isBusy) {
      setStatus('busy', 'Đang tra cứu từ kho sách…');
    } else {
      setStatus('ready', 'Sẵn sàng tra cứu');
    }
  }

  function showError(msg, canRetry = true) {
    if (errorBar) {
      errorMsg.textContent = msg;
      errorBar.hidden = false;
      if (retryBtn) retryBtn.hidden = !canRetry;
      setStatus('error', 'Lỗi kết nối máy chủ');
    }
  }

  function hideError() {
    if (errorBar) errorBar.hidden = true;
  }

  async function submitQuestion(text) {
    if (busy) return;
    text = (text || '').trim();
    if (!text) return;

    if ([...text].length > 1000) {
      showError('Câu hỏi tối đa 1000 ký tự.', false);
      return;
    }

    hideError();
    lastFailedMessage = '';
    input.value = '';
    addMessage(text, true);
    setBusy(true);

    const version = ++requestVersion;
    try {
      const data = await apiCall('/message', {
        message: text,
        use_ai: !!root.querySelector('[name="chat_ai"]')?.checked
      });

      if (version === requestVersion) {
        addMessage(data.message, false, data);
        setSuggestions(data.suggestions);
      }
    } catch (e) {
      if (version === requestVersion) {
        lastFailedMessage = text;
        const isAbort = e.name === 'AbortError';
        const errText = isAbort ? 'Kết nối mất quá lâu. Vui lòng thử lại.' : e.message;
        showError(errText, true);
      }
    } finally {
      if (version === requestVersion) {
        setBusy(false);
        input.focus();
      }
    }
  }

  async function openChat() {
    panel.hidden = false;
    root.classList.add('is-open');
    launch.setAttribute('aria-expanded', 'true');
    input.focus();

    if (!initialized) {
      setBusy(true);
      try {
        const state = await apiCall('/state');
        addMessage(state.greeting, false);
        setSuggestions(state.suggestions);
        const aiBox = root.querySelector('[data-chat-ai]');
        if (aiBox) aiBox.hidden = !state.ai_available;
        initialized = true;
      } catch (e) {
        const errText = e.name === 'AbortError' ? 'Không thể kết nối với Trợ lý. Vui lòng thử lại.' : e.message;
        showError(errText, false);
      } finally {
        setBusy(false);
      }
    }
  }

  function closeChat() {
    panel.hidden = true;
    root.classList.remove('is-open');
    launch.setAttribute('aria-expanded', 'false');
    launch.focus();
  }

  // Event Listeners
  launch.addEventListener('click', () => panel.hidden ? openChat() : closeChat());

  const closeBtn = root.querySelector('[data-chat-close]');
  if (closeBtn) closeBtn.addEventListener('click', closeChat);

  const minimizeBtn = root.querySelector('[data-chat-minimize]');
  if (minimizeBtn) minimizeBtn.addEventListener('click', closeChat);

  root.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !panel.hidden) {
      e.preventDefault();
      closeChat();
    }
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    submitQuestion(input.value);
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
      e.preventDefault();
      submitQuestion(input.value);
    }
  });

  if (retryBtn) {
    retryBtn.addEventListener('click', () => {
      if (lastFailedMessage) {
        const msg = lastFailedMessage;
        submitQuestion(msg);
      } else {
        hideError();
        input.focus();
      }
    });
  }

  const resetBtn = root.querySelector('[data-chat-reset]');
  if (resetBtn) {
    resetBtn.addEventListener('click', async () => {
      if (busy) return;
      hideError();
      setBusy(true);
      try {
        const data = await apiCall('/reset', {});
        log.replaceChildren();
        addMessage(data.greeting, false);
        setSuggestions(data.suggestions);
      } catch (e) {
        showError(e.message, false);
      } finally {
        setBusy(false);
        input.focus();
      }
    });
  }

  window.addEventListener('focus', () => {
    if (initialized && !busy) {
      apiCall('/state').catch(() => {});
    }
  });
})();
