const {chromium} = require('../storage/browser-qa/node_modules/playwright');
const fs = require('fs');
const path = require('path');
const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname,'../storage/http_qa.json'),'utf8'));
const base='http://127.0.0.1:8011';
let checks=0, screens=0;
function assert(value,label) { if(!value) throw new Error('FAIL: '+label); checks++; console.log('PASS: '+label); }
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 const context=await browser.newContext(); const page=await context.newPage();
 const errors=[]; page.on('pageerror',e=>errors.push(e.message));
 const login=async(role)=>{await page.goto(base+'/login');await page.locator('[name=email]').fill(fixtures[role].email);await page.locator('[name=password]').fill(fixtures[role].password);await Promise.all([page.waitForURL(role==='reader'?'**/account':'**/admin'),page.locator('button[type=submit]').click()]);};
 const routes=['/','/login','/register','/catalog','/book/'+fixtures.book_id];
 for(const width of [1440,1366,1024,768,390]) {await page.setViewportSize({width,height:900});for(const route of routes){const res=await page.goto(base+route);assert(res.status()===200,'Public '+route+' at '+width);assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No overflow '+route+' '+width);screens++;}}
 await page.setViewportSize({width:1440,height:900});await login('admin');
 const staff=['/admin','/admin/books','/admin/books/'+fixtures.book_id+'/copies','/admin/readers','/admin/readers/'+fixtures.reader.reader_id,'/admin/authors','/admin/publishers','/admin/categories','/admin/loans','/admin/fines','/admin/statistics','/admin/settings','/notifications','/profile'];
 for(const width of [1440,1366,1024,768,390]) {await page.setViewportSize({width,height:900});for(const route of staff){const res=await page.goto(base+route);assert(res.status()===200,'Admin '+route+' '+width);assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No overflow '+route+' '+width);screens++;}}
 // Native modals must open and have a numeric create id; operate real forms.
 await page.setViewportSize({width:390,height:900});
 for(const [route,id] of [['/admin/books','bookDialog'],['/admin/readers','readerDialog'],['/admin/categories','categoryDialog'],['/admin/loans','loanDialog']]) {
  await page.goto(base+route);await page.locator('[data-dialog-open="'+id+'"]').first().click();
  assert(await page.locator('#'+id).evaluate(el=>el.open),'Modal opens '+id);
  assert(await page.locator('#'+id).evaluate(el=>{let r=el.getBoundingClientRect();return r.x>=0 && r.right<=innerWidth && r.y>=0 && r.bottom<=innerHeight+1;}),'Modal fits mobile '+id);
  if(id!=='loanDialog') assert(await page.locator('#'+id+' [name=id]').inputValue()==='0','New modal has numeric id '+id);
  await page.locator('#'+id+' [data-dialog-close]').first().click();
 }
 await page.goto(base+'/admin');await page.setViewportSize({width:390,height:900});await page.waitForTimeout(300);assert(await page.locator('[data-sidebar]').evaluate(e=>e.getBoundingClientRect().right<0),'Mobile sidebar initially hidden');await page.locator('[data-menu-toggle]').click();await page.waitForTimeout(300);assert(await page.locator('[data-sidebar]').evaluate(e=>e.getBoundingClientRect().x>=0),'Mobile sidebar opens');await page.locator('[data-backdrop]').click({position:{x:380,y:400}});await page.waitForTimeout(300);assert(await page.locator('[data-sidebar]').evaluate(e=>e.getBoundingClientRect().right<0),'Mobile sidebar closes');
 await page.setViewportSize({width:1440,height:900});await page.goto(base+'/admin/books');await page.locator('[data-dialog-open="bookDialog"]').first().click();
 const tag='QABROWSER-'+Date.now(); const form=page.locator('#bookDialog');
 await form.locator('[name=book_code]').fill(tag);await form.locator('[name=title]').fill('QA giao diện tạo sách');await form.locator('[name=author]').fill('QA Browser Author');await form.locator('[name=total_copies]').fill('2');
 await Promise.all([page.waitForURL('**/admin/books'),form.locator('.dialog-actions button').last().click()]);
 assert((await page.locator('.flash-message.success').textContent()).includes('Đã lưu'),'Create book through native form');
 await page.goto(base+'/admin/books?q='+tag); const copyLink=page.locator('a').filter({hasText:'Bản sao'}).first(); await copyLink.click();
 assert(await page.locator('tbody tr').count()===2,'Browser-created book has 2 physical copies');
 const copyForm=page.locator('section').first().locator('form');await copyForm.locator('[name=copy_code]').fill(tag+'-CUSTOM');await copyForm.locator('[name=shelf_location]').fill('QA-KỆ-1');
 await Promise.all([page.waitForNavigation(),copyForm.locator('button').click()]);assert(await page.locator('tbody tr').count()===3,'Add physical copy through web');
 await page.screenshot({path:path.join(__dirname,'../storage/qa-copies-desktop.png'),fullPage:true});
 await page.setViewportSize({width:390,height:900});await page.waitForTimeout(300);await page.screenshot({path:path.join(__dirname,'../storage/qa-copies-mobile.png'),fullPage:true});

 await page.setViewportSize({width:1440,height:900});await page.goto(base+'/admin/readers');await page.locator('[data-dialog-open="readerDialog"]').first().click();
 const readerForm=page.locator('#readerDialog');await readerForm.locator('[name=card_number]').fill(tag);await readerForm.locator('[name=full_name]').fill('QA HTTP lifecycle');
 await Promise.all([page.waitForNavigation(),readerForm.locator('.dialog-actions button').last().click()]);assert(await page.locator('.flash-message.success').count()===1,'Create reader through modal');
 await page.goto(base+'/admin/readers?q='+tag);const rid=await page.locator('tbody [name=id]').first().inputValue();
 await page.goto(base+'/admin/loans');await page.locator('[data-dialog-open="loanDialog"]').click();const lf=page.locator('#loanDialog');
 await lf.locator('[name=reader_id]').selectOption(rid);const optionIds=await lf.locator('select[multiple] option').evaluateAll((els,tag)=>els.filter(e=>e.textContent.includes(tag)).map(e=>e.value),tag);
 await lf.locator('select[multiple]').selectOption(optionIds.slice(0,2));
 await Promise.all([page.waitForNavigation(),lf.locator('.dialog-actions button').last().click()]);assert(await page.locator('.flash-message.success').count()===1,'Checkout two copies through web');
 await page.goto(base+'/admin/loans?q='+tag);let returns=page.locator('form[action$="/return"]');assert(await returns.count()===2,'Two loan items persist after reload');
 const firstItem=await returns.first().locator('[name=item_id]').inputValue();const requestForFlow=context.request;let csrf=await returns.first().locator('[name=_csrf]').inputValue();
 await requestForFlow.post(base+'/admin/loans/return',{form:{_csrf:csrf,item_id:firstItem,condition:'good'}});await page.reload();assert(await page.locator('form[action$="/return"]').count()===1,'Partial return leaves one item open');
 const renewForm=page.locator('form[action$="/renew"]').first();const secondItem=await renewForm.locator('[name=item_id]').inputValue();csrf=await renewForm.locator('[name=_csrf]').inputValue();
 const renewalResponse=await requestForFlow.post(base+'/admin/loans/renew',{form:{_csrf:csrf,item_id:secondItem}});assert((await renewalResponse.text()).includes('Đã gia hạn'),'Renew remaining item through HTTP');await page.reload();
 await requestForFlow.post(base+'/admin/loans/return',{form:{_csrf:csrf,item_id:secondItem,condition:'good'}});await page.reload();assert(await page.locator('form[action$="/return"]').count()===0,'Final return closes both items');
 assert((await page.locator('body').textContent()).includes('Đã hoàn tất'),'Loan header closes after last return');
 const duplicate=await requestForFlow.post(base+'/admin/loans/return',{form:{_csrf:csrf,item_id:secondItem,condition:'good'}});assert((await duplicate.text()).includes('đã được trả'),'Duplicate HTTP return rejected');
 await page.goto(base+'/admin/readers/'+rid);assert(await page.locator('section').first().locator('tbody tr').count()===2,'Staff reader history persists');

 const shiftDate=(days)=>{const d=new Date();d.setDate(d.getDate()+days);return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');};
 const overdueForm=new URLSearchParams({_csrf:csrf,reader_id:rid,borrowed_at:shiftDate(-5),due_at:shiftDate(-2),note:'QA overdue HTTP'});overdueForm.append('copy_ids[]',optionIds[0]);
 const overdueResult=await requestForFlow.post(base+'/admin/loans',{data:overdueForm.toString(),headers:{'Content-Type':'application/x-www-form-urlencoded'}});
 assert((await overdueResult.text()).includes('Đã lập phiếu'),'Create overdue loan through HTTP');
 await page.goto(base+'/admin/fines?reader='+rid);assert((await page.locator('body').textContent()).includes('Phí quá hạn 2 ngày'),'Overdue days computed through HTTP');
 const payForm=page.locator('form[action$="/payments"]');await payForm.locator('[name=reader_id]').selectOption(rid);await payForm.locator('[name=amount]').fill('1000');await payForm.locator('[name=reference_code]').fill(tag+'-PAY');
 await Promise.all([page.waitForNavigation(),payForm.locator('button').click()]);assert((await page.locator('body').textContent()).includes('Đã ghi nhận và phân bổ'),'Partial payment through native form');
 csrf=await page.locator('[name=_csrf]').first().inputValue();const overpay=await requestForFlow.post(base+'/admin/fines/payments',{form:{_csrf:csrf,reader_id:rid,amount:'99999999',method:'cash',reference_code:'',note:''}});assert((await overpay.text()).includes('vượt quá dư nợ'),'HTTP overpayment rejected');
 await page.reload();assert((await page.locator('body').textContent()).includes(tag+'-PAY'),'Payment history persists on reload');
 await page.goto(base+'/admin/loans?q='+tag);const lastReturn=page.locator('form[action$="/return"]').first();const lateItem=await lastReturn.locator('[name=item_id]').inputValue();csrf=await lastReturn.locator('[name=_csrf]').inputValue();await requestForFlow.post(base+'/admin/loans/return',{form:{_csrf:csrf,item_id:lateItem,condition:'good'}});
 await page.goto(base+'/admin/fines?reader='+rid);const waiver=page.locator('form[action$="/waive"]').first();const remaining=await waiver.locator('[name=amount]').getAttribute('max');await requestForFlow.post(base+'/admin/fines/payments',{form:{_csrf:csrf,reader_id:rid,amount:remaining,method:'cash',reference_code:tag+'-FINAL',note:''}});
 const deactivated=await requestForFlow.post(base+'/admin/readers/delete',{form:{_csrf:csrf,id:rid}});assert((await deactivated.text()).includes('Đã ngừng hoạt động'),'Deactivate settled card through HTTP');
 await page.goto(base+'/admin/readers?state=inactive&q='+tag);assert((await page.locator('body').textContent()).includes('QA HTTP lifecycle'),'Inactive reader remains searchable');
 const restored=await requestForFlow.post(base+'/admin/readers',{form:{_csrf:csrf,id:rid,card_number:tag,full_name:'QA HTTP lifecycle',email:'',phone:'',address:'',expires_on:'',is_active:'1'}});assert((await restored.text()).includes('Đã lưu thông tin độc giả'),'Reactivate card through HTTP');
 // HTTP permission and CSRF matrix, no application DB mutation.
 const request=context.request;
 const token=await page.locator('[name=_csrf]').first().inputValue();
 const invalid=await request.post(base+'/admin/authors',{form:{id:'0',name:'BAD-CSRF'}}); assert(invalid.status()===419,'Missing CSRF rejected');
 for(const kind of ['inventory','fines']){const res=await request.get(base+'/admin/statistics/export?kind='+kind);assert(res.status()===200 && (await res.body()).subarray(0,3).equals(Buffer.from([239,187,191])),'UTF8 CSV '+kind);}
 await request.post(base+'/logout',{form:{_csrf:token}});
 await login('librarian');assert((await request.get(base+'/admin/settings')).status()===403,'Librarian settings denied');assert((await request.get(base+'/profile')).status()===200,'Librarian profile available');
 await page.goto(base+'/admin');await request.post(base+'/logout',{form:{_csrf:await page.locator('[name=_csrf]').first().inputValue()}});
 await login('reader');assert(!(await page.locator('body').textContent()).includes(tag),'Reader cannot see another reader lifecycle');
 for(const route of ['/admin','/admin/books','/admin/authors','/admin/publishers','/admin/books/'+fixtures.book_id+'/copies','/admin/statistics/export?kind=fines','/profile'])assert((await request.get(base+route)).status()===403,'Reader forbidden '+route);
 for(const width of [1440,1366,1024,768,390]){await page.setViewportSize({width,height:900});for(const route of ['/account','/account/profile','/notifications']){const res=await page.goto(base+route);assert(res.status()===200,'Reader '+route+' '+width);assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Reader no overflow '+route+' '+width);screens++;}}
 assert((await request.get(base+'/catalog?q%5B%5D=x&page%5B%5D=2')).status()===200,'Array query input handled safely');
 assert((await request.get(base+'/.env')).status()===404,'Environment inaccessible over HTTP');
 assert((await request.get(base+'/database/schema.sql')).status()===404,'SQL source inaccessible over HTTP');

 const reserved=JSON.parse(fs.readFileSync(path.join(__dirname,'../storage/reservation_qa.json'),'utf8'));
 const staffContext=await browser.newContext();const staffPage=await staffContext.newPage();await staffPage.goto(base+'/login');await staffPage.locator('[name=email]').fill(fixtures.librarian.email);await staffPage.locator('[name=password]').fill(fixtures.librarian.password);await staffPage.locator('button[type=submit]').click();await staffPage.goto(base+'/admin/loans');await staffPage.locator('[data-dialog-open="loanDialog"]').click();
 const reservedForm=staffPage.locator('#loanDialog');assert((await reservedForm.locator('select[multiple] option[value="'+reserved.copy_id+'"]').textContent()).includes('Giữ cho'),'Reserved copy labelled for intended reader');
 await reservedForm.locator('[name=reader_id]').selectOption(String(reserved.reader_id));await reservedForm.locator('select[multiple]').selectOption(String(reserved.copy_id));await Promise.all([staffPage.waitForNavigation(),reservedForm.locator('.dialog-actions button').last().click()]);assert((await staffPage.locator('body').textContent()).includes('Đã lập phiếu'),'Librarian fulfills reservation through native checkout');
 await staffPage.goto(base+'/admin/loans?q='+reserved.copy_code);const rf=staffPage.locator('form[action$="/return"]').first();await staffContext.request.post(base+'/admin/loans/return',{form:{_csrf:await rf.locator('[name=_csrf]').inputValue(),item_id:await rf.locator('[name=item_id]').inputValue(),condition:'good'}});await staffContext.close();
 assert(errors.length===0,'No browser JavaScript errors');
 fs.writeFileSync(path.join(__dirname,'../storage/browser_qa_result.json'),JSON.stringify({status:'PASS',checks,screens,errors},null,2));
 await browser.close(); console.log(`Browser E2E: ${checks} checks passed, ${screens} viewport/page renders.`);
})().catch(e=>{console.error(e.message);process.exit(1);});