(function() {
  function initInventoryScanner() {
    const toggleBtn = document.getElementById('btn-toggle-camera');
    const closeBtn = document.getElementById('btn-close-camera');
    const panel = document.getElementById('camera-scanner-panel');
    const video = document.getElementById('camera-stream');
    const feedback = document.getElementById('camera-feedback');
    const codeInput = document.querySelector('input[name="code"]');
    const scanForm = codeInput ? codeInput.closest('form') : null;

    if (!toggleBtn || !panel) return;

    let stream = null;
    let scanningInterval = null;
    let lastScannedCode = '';
    let lastScannedTime = 0;
    let detectionInProgress = false;

    function playBeep() {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
      } catch (e) {}
    }

    async function startCamera() {
      panel.style.display = 'block';

      if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        if (feedback) feedback.innerHTML = '<span style="color: #dc3545;">✕ Camera yêu cầu ngữ cảnh bảo mật (HTTPS hoặc localhost).</span>';
        return;
      }
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        if (feedback) feedback.innerHTML = '<span style="color: #dc3545;">✕ Trình duyệt không hỗ trợ API truy cập camera (navigator.mediaDevices). Vui lòng sử dụng máy quét USB hoặc nhập mã ở ô bên dưới.</span>';
        return;
      }

      if (feedback) feedback.innerHTML = '<span style="color: #c58b54;">⏳ Đang kết nối camera thiết bị...</span>';

      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1280 },
            height: { ideal: 720 }
          }
        });
        if (video) {
          video.srcObject = stream;
          await video.play();
        }
        if (feedback) feedback.innerHTML = '<strong style="color: #28a745;">● Camera đang hoạt động.</strong> Đưa mã vạch Code 128 hoặc QR code vào khung ngắm.';

        if ('BarcodeDetector' in window) {
          const wantedFormats = ['qr_code', 'code_128', 'ean_13', 'code_39', 'upc_a', 'upc_e'];
          const supportedFormats = typeof BarcodeDetector.getSupportedFormats === 'function'
            ? await BarcodeDetector.getSupportedFormats()
            : wantedFormats;
          const formats = wantedFormats.filter(format => supportedFormats.includes(format));
          if (!formats.includes('qr_code') && !formats.includes('code_128')) {
            throw new Error('Trình duyệt không hỗ trợ giải mã QR Code hoặc Code 128.');
          }
          const detector = new BarcodeDetector({ formats });
          scanningInterval = setInterval(async () => {
            if (video && video.readyState >= 2 && !detectionInProgress) {
              detectionInProgress = true;
              try {
                const barcodes = await detector.detect(video);
                if (barcodes.length > 0) {
                  const code = (barcodes[0].rawValue || '').trim();
                  const now = Date.now();
                  // Chống quét trùng liên tiếp cùng một mã trong vòng 3 giây
                  if (code && (code !== lastScannedCode || now - lastScannedTime > 3000)) {
                    lastScannedCode = code;
                    lastScannedTime = now;
                    playBeep();
                    stopCamera();
                    if (codeInput) {
                      codeInput.value = code;
                      if (feedback) {
                        feedback.textContent = '✓ Đã nhận diện thành công: ' + code;
                        feedback.style.color = '#28a745';
                        feedback.style.fontWeight = '700';
                      }
                      if (scanForm) {
                        scanForm.submit();
                      }
                    }
                  }
                }
              } catch (err) {
                // A frame without a readable code is normal while the camera moves.
              } finally {
                detectionInProgress = false;
              }
            }
          }, 250);
        } else {
          if (feedback) feedback.innerHTML = '<span style="color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 4px;">Camera đang bật. Trình duyệt hiện tại chưa hỗ trợ sẵn Web BarcodeDetector; bạn có thể kết hợp máy quét USB hoặc nhập mã ở ô bên dưới.</span>';
        }
      } catch (err) {
        let errMsg = 'Không thể mở camera: ';
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
          errMsg += 'Bạn đã từ chối quyền truy cập camera. Vui lòng cấp quyền trong cài đặt trình duyệt.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
          errMsg += 'Không tìm thấy thiết bị camera trên máy.';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
          errMsg += 'Camera đang được ứng dụng khác sử dụng hoặc bị lỗi phần cứng.';
        } else {
          errMsg += (err.message || 'Lỗi không xác định.');
        }
        if (stream) {
          stream.getTracks().forEach(track => track.stop());
          stream = null;
        }
        if (feedback) {
          feedback.textContent = '✕ ' + errMsg;
          feedback.style.color = '#dc3545';
        }
      }
    }

    function stopCamera() {
      if (scanningInterval) {
        clearInterval(scanningInterval);
        scanningInterval = null;
      }
      detectionInProgress = false;
      if (stream) {
        stream.getTracks().forEach(track => {
          try { track.stop(); } catch(e) {}
        });
        stream = null;
      }
      if (video) {
        try { video.pause(); video.srcObject = null; } catch(e) {}
      }
      if (panel) {
        panel.style.display = 'none';
      }
    }

    toggleBtn.addEventListener('click', startCamera);
    if (closeBtn) closeBtn.addEventListener('click', stopCamera);
    window.addEventListener('beforeunload', stopCamera);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInventoryScanner);
  } else {
    initInventoryScanner();
  }
})();
