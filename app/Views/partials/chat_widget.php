<link rel="stylesheet" href="<?= e(asset('css/chat.css')) ?>">
<?php // PAPER_HAVEN_CHAT_UI_V1 ?>
<div class="library-chat" data-library-chat data-endpoint="<?= e(url('/chat')) ?>" data-csrf="<?= e(\Library\Core\Csrf::token()) ?>">
  <button class="chat-launch" type="button" aria-label="Mở Trợ lý Paper Haven" aria-expanded="false" aria-controls="library-chat-panel">
    <span class="chat-launch-mark" aria-hidden="true">PH</span>
    <span class="chat-launch-text">Trợ lý Paper Haven</span>
  </button>
  <section class="chat-panel" id="library-chat-panel" aria-label="Trợ lý Paper Haven" hidden>
    <header class="chat-header">
      <div class="chat-header-main">
        <div class="chat-header-title-wrap">
          <span class="chat-badge-icon" aria-hidden="true">PH</span>
          <div>
            <h2 class="chat-title">Trợ lý Paper Haven</h2>
            <div class="chat-live-status" id="chat-connection-status">
              <span class="status-indicator status-ready" aria-hidden="true"></span>
              <span class="status-label">Sẵn sàng tra cứu</span>
            </div>
          </div>
        </div>
      </div>
      <div class="chat-header-actions">
        <button type="button" data-chat-minimize aria-label="Thu nhỏ trò chuyện" title="Thu nhỏ">−</button>
        <button type="button" data-chat-close aria-label="Đóng trò chuyện" title="Đóng">×</button>
      </div>
    </header>
    <div class="chat-messages" role="log" aria-live="polite" aria-relevant="additions" tabindex="0"></div>
    <div class="chat-typing" id="chat-typing" hidden aria-hidden="true">
      <div class="typing-bubble">
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
        <span class="typing-text">Đang tra cứu từ kho sách…</span>
      </div>
    </div>
    <div class="chat-error-bar" id="chat-error" hidden role="alert">
      <span class="chat-error-msg">Không thể kết nối với máy chủ.</span>
      <button type="button" class="chat-retry-btn" id="chat-retry-btn">Thử lại</button>
    </div>
    <div class="chat-suggestions-container">
      <div class="chat-suggestions-title">Gợi ý tra cứu:</div>
      <div class="chat-suggestions" aria-label="Câu hỏi gợi ý"></div>
    </div>
    <form class="chat-form">
      <label class="sr-only" for="library-chat-input">Câu hỏi cho Trợ lý Paper Haven</label>
      <textarea id="library-chat-input" rows="2" maxlength="1000" placeholder="Tìm tên sách, tác giả, ISBN, quy định mượn..." required></textarea>
      <button type="submit" aria-label="Gửi câu hỏi" title="Gửi (Enter)">
        <span>Gửi</span>
      </button>
    </form>
    <div class="chat-footer">
      <button type="button" data-chat-reset class="chat-reset-action" title="Bắt đầu cuộc trò chuyện mới">
        ↺ Cuộc trò chuyện mới
      </button>
      <span class="chat-key-hint">Enter gửi · Shift+Enter xuống dòng</span>
      <label data-chat-ai hidden class="chat-ai-label"><input type="checkbox" name="chat_ai">Hỗ trợ AI</label>
    </div>
  </section>
</div>
<script src="<?= e(asset('js/chat.js')) ?>" defer></script>
