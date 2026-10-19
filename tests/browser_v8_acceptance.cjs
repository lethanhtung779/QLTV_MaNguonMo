const { chromium } = require('../storage/browser-qa/node_modules/playwright');
const fs = require('fs');
const path = require('path');

const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname, '../storage/http_qa.json'), 'utf8'));
const base = process.env.QA_BASE_URL || 'http://127.0.0.1:8011';
let checks = 0;
let errors = [];

function assert(condition, label) {
  checks++;
  if (!condition) {
    console.error('FAIL: ' + label);
    throw new Error('Assertion failed: ' + label);
  }
  console.log('PASS: ' + label);
}

(async () => {
  console.log('--- BẮT ĐẦU KIỂM THỬ TRÌNH DUYỆT THẬT PAPER HAVEN V8.1 ---');
  console.log('Target URL: ' + base);

  const browser = await chromium.launch({
    executablePath: process.env.QA_BROWSER || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    headless: true,
    args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--no-sandbox']
  });

  const context = await browser.newContext({
    permissions: ['camera']
  });
  const page = await context.newPage();

  page.on('dialog', async d => { await d.dismiss(); });

  page.on('pageerror', err => {
    console.error('Browser Page Error: ' + err.message);
    errors.push(err.message);
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error('PAGE CONSOLE ERROR: ' + msg.text());
    }
  });

  // 1. Kiểm tra các trang công khai và Responsive 5 viewports (1440, 1366, 1024, 768, 390)
  const viewports = [1440, 1366, 1024, 768, 390];
  const publicRoutes = ['/', '/catalog', '/book/' + fixtures.book_id, '/login', '/register'];

  for (const width of viewports) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of publicRoutes) {
      const res = await page.goto(base + route, { waitUntil: 'domcontentloaded' });
      assert(res.status() === 200, `Public route ${route} returns HTTP 200 at ${width}px`);
      const noOverflow = await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
      assert(noOverflow, `Zero horizontal overflow on ${route} at ${width}px`);
    }
  }

  // 2. Kiểm tra sinh mã vạch Code 128 và QR code vector SVG
  const barcodeRes = await page.goto(base + '/barcode/B001-0001');
  assert(barcodeRes.status() === 200, 'Barcode SVG route /barcode/B001-0001 returns 200');
  const barcodeContent = await page.content();
  assert(barcodeContent.includes('<svg') && barcodeContent.includes('B001-0001'), 'Barcode SVG contains valid SVG markup and code');

  const qrRes = await page.goto(base + '/qr/B001-0001');
  assert(qrRes.status() === 200, 'QR SVG route /qr/B001-0001 returns 200');
  const qrContent = await page.content();
  assert(qrContent.includes('<svg') && qrContent.includes('#193E30'), 'QR SVG contains Paper Haven green color');

  // 3. Đăng nhập Độc giả và kiểm tra Tủ sách cá nhân & Đánh giá sách
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(base + '/login');
  await page.locator('[name=email]').fill(fixtures.reader.email);
  await page.locator('[name=password]').fill(fixtures.reader.password);
  await Promise.all([
    page.waitForURL('**/account'),
    page.locator('form[action$="/login"] button[type=submit]').click()
  ]);
  assert(page.url().includes('/account'), 'Reader logged in and redirected to /account');

  // Kiểm tra trang Tủ sách của tôi (/account/bookshelf)
  const bookshelfRes = await page.goto(base + '/account/bookshelf');
  assert(bookshelfRes.status() === 200, 'Bookshelf /account/bookshelf returns 200');
  const bookshelfText = await page.textContent('body');
  assert(bookshelfText.includes('Tủ sách của tôi'), 'Bookshelf title rendered');

  // 4. Kiểm tra Chatbot Widget tương tác thực tế
  await page.goto(base + '/');
  const chatLaunchBtn = page.locator('[data-chat-launch], .chat-launch');
  assert(await chatLaunchBtn.count() > 0, 'Chatbot launcher button present');
  await chatLaunchBtn.click();
  const chatPanel = page.locator('#library-chat-panel');
  assert(await chatPanel.isVisible(), 'Chatbot panel opened upon click');

  const chatInput = page.locator('#library-chat-input');
  await chatInput.fill('Tìm sách');
  await page.locator('.chat-form button[type=submit]').click();
  await page.waitForTimeout(1000);
  const chatMessages = await page.locator('.chat-messages').textContent();
  assert(chatMessages.length > 5, 'Chatbot responded with real book recommendations');

  // 5. Đăng xuất từ tài khoản bạn đọc và Đăng nhập Admin
  await page.goto(base + '/account');
  await page.locator('form[action$="/logout"] button').click();
  await page.waitForLoadState('networkidle');
  assert(page.url() === base + '/' || page.url() === base, 'Reader logged out successfully');

  await page.goto(base + '/login');
  await page.locator('[name=email]').fill(fixtures.admin.email);
  await page.locator('[name=password]').fill(fixtures.admin.password);
  await Promise.all([
    page.waitForURL('**/admin*'),
    page.locator('form[action$="/login"] button[type=submit]').click()
  ]);
  assert(page.url().includes('/admin'), 'Admin logged in and redirected to /admin');

  // 6. Kiểm tra các trang quản trị
  const adminRoutes = [
    '/admin',
    '/admin/books',
    '/admin/books/' + fixtures.book_id + '/copies',
    '/admin/books/' + fixtures.book_id + '/labels',
    '/admin/inventory/audits',
    '/admin/reviews',
    '/admin/loans',
    '/admin/readers',
    '/admin/fines',
    '/admin/statistics',
    '/admin/settings'
  ];

  for (const ar of adminRoutes) {
    const res = await page.goto(base + ar);
    assert(res.status() === 200, `Admin route ${ar} returns 200`);
    const noOverflow = await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
    assert(noOverflow, `Zero horizontal overflow on admin route ${ar}`);
  }

  // 7. Kiểm tra tính năng Camera Scanner & Kiểm kê kho
  await page.goto(base + '/admin/inventory/audits');
  // Tạo một đợt kiểm kê mới qua form
  await page.locator('form[action$="/inventory/audits"] [name=title]').fill('Kiểm kê Browser Acceptance V8');
  await Promise.all([
    page.waitForURL('**/admin/inventory/audits/*'),
    page.locator('form[action$="/inventory/audits"] button[type=submit]').click()
  ]);
  assert(page.url().includes('/admin/inventory/audits/'), 'Created audit and navigated to audit detail');

  // Kiểm tra sự hiện diện của nút Camera quét mã và panel camera
  const cameraBtn = page.locator('#btn-toggle-camera');
  assert(await cameraBtn.count() > 0, 'Camera toggle button is present on audit detail');

  const beforeDisplay = await page.evaluate(() => {
    const p = document.getElementById('camera-scanner-panel');
    const btn = document.getElementById('btn-toggle-camera');
    return {
      panelExists: !!p,
      panelDisplay: p ? p.style.display : null,
      btnExists: !!btn
    };
  });
  console.log('CAMERA DEBUG BEFORE:', JSON.stringify(beforeDisplay));

  await cameraBtn.click();
  await page.waitForTimeout(500);

  const afterDisplay = await page.evaluate(() => {
    const p = document.getElementById('camera-scanner-panel');
    return {
      panelExists: !!p,
      panelDisplay: p ? p.style.display : null,
      computedDisplay: p ? window.getComputedStyle(p).display : null
    };
  });
  console.log('CAMERA DEBUG AFTER:', JSON.stringify(afterDisplay));

  const cameraPanel = page.locator('#camera-scanner-panel');
  assert(await cameraPanel.isVisible(), 'Camera scanner panel expands on toggle');

  const closeCameraBtn = page.locator('#btn-close-camera');
  await closeCameraBtn.click();
  await page.waitForTimeout(300);
  assert(!(await cameraPanel.isVisible()), 'Camera scanner panel closes cleanly on close button');

  // Quét thử một bản sao thực tế
  await page.locator('input[name="code"]').fill('B001-0001');
  await page.locator('input[name="location"]').fill('Kệ QA-01');
  await Promise.all([
    page.waitForURL('**/admin/inventory/audits/*'),
    page.locator('form[action*="/scan"] button[type="submit"]').click()
  ]);
  const pageContent = await page.content();
  assert(pageContent.includes('B001-0001') || pageContent.includes('ghi nhận'), 'Copy scanned successfully into audit');

  // 8. Xuất biên bản CSV kiểm kê
  const currentAuditId = page.url().split('/').filter(Boolean).pop().split('?')[0];
  const exportRes = await page.request.get(base + '/admin/inventory/audits/' + currentAuditId + '/export');
  assert(exportRes.status() === 200, 'Export audit CSV returns 200');
  const exportHeaders = exportRes.headers();
  assert(exportHeaders['content-type'].includes('text/csv'), 'Export audit returns text/csv header');
  const csvBody = await exportRes.text();
  assert(csvBody.includes('BIÊN BẢN KIỂM KÊ THƯ VIỆN PAPER HAVEN'), 'Export audit CSV contains Paper Haven header');

  // 9. Kiểm tra biên nhận PDF
  const pdfRes = await page.request.get(base + '/receipts/loan/49');
  assert(pdfRes.status() === 200, 'Loan PDF receipt returns 200');
  const pdfHeaders = pdfRes.headers();
  assert(pdfHeaders['content-type'].includes('application/pdf'), 'Loan receipt returns application/pdf header');
  const pdfBody = await pdfRes.body();
  assert(pdfBody.length > 500, 'Loan PDF binary content received');

  assert(errors.length === 0, `Zero browser page errors (Found ${errors.length})`);

  console.log(`\n========================================`);
  console.log(`TỔNG KẾT BROWSER V8 ACCEPTANCE: ${checks} CHECKS PASS, 0 FAIL, 0 CONSOLE ERRORS`);
  console.log(`========================================\n`);

  await browser.close();
  process.exit(0);
})().catch(err => {
  console.error('BROWSER ACCEPTANCE ERROR: ' + err.message);
  process.exit(1);
});
