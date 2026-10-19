const {chromium}=require('../storage/browser-qa/node_modules/playwright');
const fs=require('fs'),path=require('path'),crypto=require('crypto');
const fixtures=JSON.parse(fs.readFileSync(path.join(__dirname,'../storage/http_qa.json'),'utf8'));
const base=process.env.QA_BASE_URL||'http://127.0.0.1:8011';
let checks=0; const results=[];
const assert=(value,label)=>{if(!value)throw Error(label);checks++;results.push(label);console.log('PASS: '+label);};
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.QA_BROWSER||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 const ac=await browser.newContext(),page=await ac.newPage(),errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 async function login(p,email,password) {await p.goto(base+'/login');await p.locator('[name=email]').fill(email);await p.locator('[name=password]').fill(password);await Promise.all([p.waitForNavigation(),p.locator('form[action$="/login"] button[type=submit]').click()]);}
 const csrf=async(p)=>p.locator('[name=_csrf]').first().inputValue();
 const tag='SPRINT-'+Date.now(),password='Demo'+crypto.randomBytes(12).toString('hex')+'Aa9',staffEmail=tag.toLowerCase()+'-staff@example.test',readerEmail=tag.toLowerCase()+'-reader@example.test';
 await login(page,fixtures.admin.email,fixtures.admin.password);
 for (const width of [1440,390]) for (const route of ['/admin/users','/admin/permissions','/admin/activity']) {
   await page.setViewportSize({width,height:900});const response=await page.goto(base+route);
   assert(response.status()===200,'New admin route '+route+' '+width);
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'New route fits '+width+' '+route);
 }
 await page.setViewportSize({width:1440,height:900});await page.goto(base+'/admin/users');
 await page.locator('[data-dialog-open=staffNew]').click();const sf=page.locator('#staffNew');
 assert(await sf.evaluate(e=>e.open),'Staff creation dialog opens');
 await sf.locator('[name=name]').fill('Thủ thư nghiệm thu '+tag);await sf.locator('[name=email]').fill(staffEmail);await sf.locator('[name=password]').fill(password);
 await Promise.all([page.waitForNavigation(),sf.locator('button').last().click()]);
 assert(await page.locator('.flash-message.success').count()===1,'Admin creates staff through browser form');
 const staffRow=page.locator('tbody tr').filter({hasText:staffEmail}); const staffId=(await staffRow.locator('[data-dialog-open]').getAttribute('data-dialog-open')).replace('staff','');
 await staffRow.locator('[data-dialog-open]').click();assert(await page.locator('#staff'+staffId+' [name=id]').inputValue()===staffId,'Staff edit preserves identity');
 await page.locator('#staff'+staffId+' [data-dialog-close]').click();
 const lc=await browser.newContext(),lp=await lc.newPage();lp.on('pageerror',e=>errors.push(e.message));
 await login(lp,staffEmail,password);assert(lp.url().endsWith('/admin'),'New librarian logs in');
 assert((await lc.request.get(base+'/admin/users')).status()===403,'Librarian cannot manage staff');
 await page.goto(base+'/admin/permissions?user='+staffId);
 const enabled=await page.locator('input[name="permissions[]"]:checked').evaluateAll(els=>els.map(e=>e.value));
 for (const code of enabled) await page.locator('input[value="'+code+'"]').uncheck();
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Lưu quyền'}).click()]);
 const endpoints=[
 ['GET','/admin'],['GET','/admin/books'],['POST','/admin/books'],['POST','/admin/books/delete'],
 ['GET','/admin/readers'],['POST','/admin/readers'],['POST','/admin/readers/delete'],
 ['GET','/admin/loans'],['POST','/admin/loans'],['POST','/admin/loans/return'],['POST','/admin/loans/return-all'],['POST','/admin/loans/renew'],
 ['POST','/admin/reservations'],['GET','/admin/fines'],['POST','/admin/fines/payments'],['POST','/admin/fines/waive'],
 ['GET','/admin/statistics'],['GET','/admin/statistics/export?kind=books'],['GET','/admin/authors'],
 ['POST','/admin/books/'+fixtures.book_id+'/copies'],['GET','/admin/settings'],['GET','/admin/permissions'],['GET','/admin/activity']
 ];
 for (const [method,url] of endpoints) {
   const response=await lc.request.fetch(base+url,{method,data:method==='POST'?'id=1':undefined,headers:method==='POST'?{'Content-Type':'application/x-www-form-urlencoded'}:undefined});
   assert(response.status()===403,'Revoked backend '+method+' '+url);
 }
 assert((await lc.request.post(base+'/admin/books',{form:{id:'0',title:'Unauthorized'}})).status()===403,'Create explicitly denied');
 assert((await lc.request.post(base+'/admin/books',{form:{id:'1',title:'Unauthorized'}})).status()===403,'Update explicitly denied');
 await lp.goto(base+'/profile');assert(await lp.locator('a[href$="/admin/books"]').count()===0,'Revoked navigation hidden during existing session');
 for(const code of enabled)await page.locator('input[value="'+code+'"]').check();
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Lưu quyền'}).click()]);
 assert((await lc.request.get(base+'/admin/books')).status()===200,'Grant takes effect without login again');
 const ownSelf=await ac.request.post(base+'/admin/users',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(page),id:String(fixtures.admin.id),name:'QA admin',email:fixtures.admin.email,role:'librarian',is_active:'0',password:''}});
 assert((await ownSelf.text()).includes('Không thể khóa hoặc hạ quyền'),'Admin self lock/demotion rejected');
 assert((await ac.request.post(base+'/admin/users',{form:{name:'No CSRF'}})).status()===419,'Staff creation requires CSRF');
 // Create a reader with real login in the same transaction.
 await page.goto(base+'/admin/readers');await page.locator('[data-dialog-open=readerDialog]').first().click();const rf=page.locator('#readerDialog');
 await rf.locator('[name=card_number]').fill(tag);await rf.locator('[name=full_name]').fill('Độc giả Nguyễn Thị Hồng '+tag);await rf.locator('[name=email]').fill(readerEmail);await rf.locator('[name=account_password]').fill(password);
 await Promise.all([page.waitForNavigation(),rf.locator('.dialog-actions button').last().click()]);
 assert(await page.locator('.flash-message.success').count()===1,'Admin creates linked reader card/account atomically');
 await page.goto(base+'/admin/readers?q='+tag);const readerId=await page.locator('tbody input[name=id]').first().inputValue();
 // Actual PNG from browser, named with an unsafe input filename: saved as a re-encoded random name.
 await page.goto(base+'/admin/books');const cover=await page.screenshot();
 async function newBook(title,file) {
   await page.goto(base+'/admin/books');await page.locator('[data-dialog-open=bookDialog]').first().click();const f=page.locator('#bookDialog');
   await f.locator('[name=book_code]').fill(title);await f.locator('[name=title]').fill('Sách Tiếng Việt – Đường đến thư viện '+title);await f.locator('[name=author]').fill('Nguyễn Nhật Ánh');await f.locator('[name=total_copies]').fill('3');
   if(file)await f.locator('[name=cover]').setInputFiles(file);
   await Promise.all([page.waitForNavigation(),f.locator('.dialog-actions button').last().click()]);
 }
 await newBook(tag,{name:'../../cover.php.png',mimeType:'image/png',buffer:cover});
 assert(await page.locator('.flash-message.success').count()===1,'Real bitmap upload creates book');
 await page.goto(base+'/admin/books?q='+tag);const img=page.locator('img[src*="/covers/"]').first();const coverUrl=await img.getAttribute('src');
 assert(/^\/covers\/[a-f0-9]{32}\.png$/.test(coverUrl),'Uploaded filename replaced by random safe relative URL');
 const imageResponse=await ac.request.get(base+coverUrl);assert(imageResponse.status()===200 && imageResponse.headers()['content-type']==='image/png','Uploaded cover served with fixed MIME');
 assert(imageResponse.headers()['x-content-type-options']==='nosniff','Cover response blocks MIME sniffing');
 assert((await ac.request.get(base+'/storage/uploads/covers/'+path.basename(coverUrl))).status()===404,'Raw upload storage inaccessible');
 await page.reload();assert(await page.locator('img[src="'+coverUrl+'"]').count()===1,'Uploaded cover persists after reload');
 await page.locator('a').filter({hasText:'Bản sao'}).first().click();assert(await page.locator('tbody tr').count()===3,'Three real copies created');
 const bookId=page.url().match(/books\/(\d+)/)[1];
 await newBook(tag+'-PHP',{name:'fake.png',mimeType:'image/png',buffer:Buffer.from('<?php echo "bad"; ?>')});
 assert(await page.locator('.flash-message.error').count()===1,'Spoofed image MIME rejected');
 await newBook(tag+'-BIG',{name:'large.png',mimeType:'image/png',buffer:Buffer.alloc(5*1024*1024+1,0)});
 assert(await page.locator('.flash-message.error').count()===1,'Upload size limit enforced');
 await newBook(tag+'-WIDE',{name:'wide.png',mimeType:'image/png',buffer:fs.readFileSync(path.join(__dirname,'../storage/qa-wide.png'))});
 assert(await page.locator('.flash-message.error').count()===1,'Image dimension limit enforced');
 await page.goto(base+'/admin/books?q='+tag+'-PHP');assert(await page.locator('.admin-book-card').count()===0,'Rejected upload creates no partial book');
 // Use librarian workflow with checkbox copy selection and own reader.
 await lp.goto(base+'/admin/loans');await lp.locator('[data-dialog-open=loanDialog]').click();const lf=lp.locator('#loanDialog');
 await lf.locator('[name=reader_id]').selectOption(readerId);await lf.getByRole('searchbox',{name:'Tìm bản sao'}).fill(tag);
 const boxes=lf.locator('.copy-picker label:visible input');assert(await boxes.count()===3,'Searchable mobile-friendly copy picker finds three copies');
 await boxes.nth(0).check();await boxes.nth(1).check();await Promise.all([lp.waitForNavigation(),lf.locator('.dialog-actions button').last().click()]);
 assert(await lp.locator('.flash-message.success').count()===1,'Created librarian checks out two copies with real UI');
 await lp.goto(base+'/admin/loans?q='+tag);const loanUrl=await lp.locator('a[href*="/receipts/loan/"]').first().getAttribute('href');
 await page.goto(base+'/admin/books?q='+tag);assert((await page.locator('.availability').textContent()).includes('1 / 3'),'Inventory falls from 3 to 1 available');
 const returns=lp.locator('form[action$="/return"]');const item1=await returns.nth(0).locator('[name=item_id]').inputValue(),item2=await returns.nth(1).locator('[name=item_id]').inputValue();
 await lc.request.post(base+'/admin/loans/return',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(lp),item_id:item1,condition:'good'}});await lp.reload();
 assert(await lp.locator('form[action$="/return"]').count()===1,'Partial return persists one active item');
 await lc.request.post(base+'/admin/loans/renew',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(lp),item_id:item2}});await lp.reload();
 assert((await lp.locator('body').textContent()).includes('Gia hạn 1/'),'Renewal count persists');
 await lc.request.post(base+'/admin/loans/return',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(lp),item_id:item2,condition:'good'}});await lp.reload();
 const returnUrl=await lp.locator('a[href*="/receipts/return/"]').first().getAttribute('href');
 const pdfDir=path.join(__dirname,'../storage/receipts-qa');fs.mkdirSync(pdfDir,{recursive:true});
 async function checkPdf(ctx,url,name) {const response=await ctx.request.get(base+url);const bytes=await response.body();assert(response.status()===200 && bytes.subarray(0,5).toString()==='%PDF-','Valid '+name+' PDF response');assert(response.headers()['content-type'].includes('application/pdf'),'PDF MIME '+name);fs.writeFileSync(path.join(pdfDir,name+'.pdf'),bytes);}
 await checkPdf(ac,loanUrl,'loan');await checkPdf(ac,returnUrl,'return');
 const rc=await browser.newContext(),rp=await rc.newPage();rp.on('pageerror',e=>errors.push(e.message));await login(rp,readerEmail,password);
 assert(rp.url().endsWith('/account'),'New linked reader can log in');
 assert((await rp.locator('body').textContent()).includes('Nguyễn Thị Hồng'),'Reader sees own persisted identity');
 assert((await rp.locator('body').textContent()).includes(tag),'Reader sees own loan history after reload');
 await checkPdf(rc,loanUrl,'reader-loan');await checkPdf(rc,returnUrl,'reader-return');
 const other=await browser.newContext(),op=await other.newPage();await login(op,fixtures.reader.email,fixtures.reader.password);
 assert((await other.request.get(base+loanUrl)).status()===403,'Other reader cannot download loan PDF (IDOR)');
 assert((await other.request.get(base+returnUrl)).status()===403,'Other reader cannot download return PDF (IDOR)');
 assert((await rc.request.get(base+'/admin/users')).status()===403,'Reader staff endpoint denied');
 // Receipt of a real partial payment created by the lifecycle suite.
 await page.goto(base+'/admin/fines');const paymentUrl=await page.locator('a[href*="/receipts/payment/"]').first().getAttribute('href');
 assert(Boolean(paymentUrl),'Real payment has downloadable receipt');
 await checkPdf(ac,paymentUrl,'payment');
 assert((await rc.request.get(base+paymentUrl)).status()===403,'Unrelated payment PDF denied');
 for(const kind of ['books','readers','monthly','inventory','fines']){
   const response=await ac.request.get(base+'/admin/statistics/export?kind='+kind);const bytes=await response.body();
   assert(response.status()===200 && bytes.subarray(0,3).equals(Buffer.from([239,187,191])),'Real UTF-8 CSV '+kind);
   fs.writeFileSync(path.join(pdfDir,kind+'.csv'),bytes);
 }
 const from=new Date().toISOString().slice(0,10);
 await page.goto(base+'/admin/statistics?from='+from+'&to='+from);assert((await page.locator('nav[aria-label="Phân trang page"]').textContent()).includes('kết quả'),'Date-filtered report paginated');
 await page.goto(base+'/admin/statistics?page=2');assert((await page.locator('nav[aria-label="Phân trang page"]').textContent()).includes('Trang 2/'),'Report second page responds to pagination');

 await lp.goto(base+'/admin/loans');await lp.locator('[data-dialog-open=loanDialog]').click();
 const whole=lp.locator('#loanDialog');await whole.locator('[name=reader_id]').selectOption(readerId);await whole.getByRole('searchbox',{name:'Tìm bản sao'}).fill(tag);
 const wholeCopies=whole.locator('.copy-picker label:visible input');await wholeCopies.nth(0).check();await wholeCopies.nth(1).check();
 await Promise.all([lp.waitForNavigation(),whole.locator('.dialog-actions button').last().click()]);
 await lp.goto(base+'/admin/loans?q='+tag);const allForm=lp.locator('form[action$="/return-all"]').first();const wholeId=await allForm.locator('[name=loan_id]').inputValue();
 lp.once('dialog',d=>d.accept());await Promise.all([lp.waitForNavigation(),allForm.locator('button').click()]);
 assert((await lp.locator('body').textContent()).includes('Đã nhận trả toàn bộ'),'Return all through native confirmed UI');
 await lp.goto(base+'/admin/loans?q='+tag);assert(await lp.locator('form[action$="/return"]').count()===0,'Return all closes each active item');
 const duplicateAll=await lc.request.post(base+'/admin/loans/return-all',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(lp),loan_id:wholeId,condition:'good'}});
 assert((await duplicateAll.text()).includes('không còn bản sách chưa trả'),'Duplicate return all rejected');
 await page.goto(base+'/admin/books?q='+tag);assert((await page.locator('.availability').textContent()).includes('3 / 3'),'Inventory restored after complete return');
 await page.goto(base+'/admin/statistics');

 await lp.goto(base+'/admin/loans');await lp.locator('[data-dialog-open=loanDialog]').click();
 const late=lp.locator('#loanDialog');await late.locator('[name=reader_id]').selectOption(readerId);await late.getByRole('searchbox',{name:'Tìm bản sao'}).fill(tag);await late.locator('.copy-picker label:visible input').first().check();
 const shift=days=>{const d=new Date();d.setDate(d.getDate()+days);return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');};
 await late.locator('[name=borrowed_at]').fill(shift(-5));await late.locator('[name=due_at]').fill(shift(-2));
 await Promise.all([lp.waitForNavigation(),late.locator('.dialog-actions button').last().click()]);
 assert(await lp.locator('.flash-message.success').count()===1,'Linked reader overdue scenario created through UI on test DB');
 await lp.goto(base+'/admin/loans?q='+tag);const lateForm=lp.locator('form[action$="/return"]').first();
 lp.once('dialog',d=>d.accept());await Promise.all([lp.waitForNavigation(),lateForm.locator('button').click()]);
 await lp.goto(base+'/admin/fines?reader='+readerId);const due=Number(await lp.locator('section').filter({has:lp.getByRole('heading',{name:'Khoản phí theo độc giả'})}).locator('tbody tr').first().locator('td').nth(5).textContent().then(t=>t.replace(/[^\d]/g,'')));
 assert(due>0,'Returned overdue copy generates real unpaid charge');
 async function pay(amount,ref){const f=lp.locator('form[action$="/payments"]');await f.locator('[name=reader_id]').selectOption(readerId);await f.locator('[name=amount]').fill(String(amount));await f.locator('[name=reference_code]').fill(ref);await Promise.all([lp.waitForNavigation(),f.locator('button').click()]);assert(await lp.locator('.flash-message.success').count()===1,'Native payment '+ref);}
 const half=Math.floor(due/2);await pay(half,tag+'-HALF');await lp.goto(base+'/admin/fines?reader='+readerId);await pay(due-half,tag+'-FINAL');
 await rp.goto(base+'/account');assert((await rp.locator('body').textContent()).includes(tag+'-FINAL'),'Reader sees own actual payment history');
 assert(await rp.locator('a[href*="/receipts/payment/"]').count()>=2,'Reader discovers own payment receipts from history');
 assert(await rp.locator('a[href*="/receipts/return/"]').count()>=1,'Reader discovers own return receipts');
 const ownPaymentUrl=await lp.locator('tbody tr').filter({hasText:tag+'-FINAL'}).locator('a[href*="/receipts/payment/"]').getAttribute('href');
 await checkPdf(rc,ownPaymentUrl,'reader-payment');
 assert((await other.request.get(base+ownPaymentUrl)).status()===403,'Other reader denied new payment receipt');
 await rp.goto(base+'/notifications');assert((await rp.locator('body').textContent()).includes('Đã ghi nhận trả sách'),'Own return notification exists');
 await page.goto(base+'/admin/statistics');
 const forged=await ac.request.post(base+'/admin/permissions',{form:{_request_key:crypto.randomBytes(16).toString('hex'),_csrf:await csrf(page),user_id:staffId,'permissions[]':'users.manage'}});
 assert((await forged.text()).includes('Quyền quản trị chỉ dành cho Admin'),'Forged admin permission grant rejected');
 for(const [p,route,name] of [[page,'/admin/users','admin'],[lp,'/admin/loans','librarian'],[rp,'/account','reader']]) {
   await p.setViewportSize({width:390,height:900});await p.goto(base+route);assert(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Final mobile '+name);
   await p.screenshot({path:path.join(__dirname,'../storage/qa-sprint-'+name+'-mobile.png'),fullPage:true});
   await p.setViewportSize({width:1440,height:900});await p.screenshot({path:path.join(__dirname,'../storage/qa-sprint-'+name+'-desktop.png'),fullPage:true});
 }
 assert(errors.length===0,'No JavaScript errors in new workflows');
 fs.writeFileSync(path.join(__dirname,'../storage/sprint_qa_result.json'),JSON.stringify({status:'PASS',date:new Date().toISOString(),checks,results,bookId,readerId,staffId,loanUrl,returnUrl,coverUrl},null,2));
 fs.writeFileSync(path.join(__dirname,'../storage/sprint_demo_credentials.json'),JSON.stringify({database:process.env.DB_NAME,staff:{email:staffEmail,password},reader:{email:readerEmail,password}},null,2));
 await browser.close();console.log('Sprint E2E: '+checks+' checks passed.');
})().catch(e=>{console.error(e.stack);process.exit(1)});
