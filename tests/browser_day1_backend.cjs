const {chromium}=require('../storage/browser-qa/node_modules/playwright');
const fs=require('fs'),path=require('path'),crypto=require('crypto'),cp=require('child_process');
const root=path.resolve(__dirname,'..'),base=process.env.QA_BASE_URL||'http://127.0.0.1:8014';
const f=JSON.parse(fs.readFileSync(path.join(root,'storage/http_qa.json'),'utf8')),data=JSON.parse(fs.readFileSync(path.join(root,'storage/day1_http.json'),'utf8'));
const php=process.env.QA_PHP||'D:/1.Download/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe';
const state=()=>JSON.parse(cp.execFileSync(php,['tests/prepare_day1_http.php','--state'],{cwd:root,env:process.env,encoding:'utf8'}));
let checks=0;const check=(ok,label)=>{if(!ok)throw new Error('FAIL: '+label);checks++;console.log('PASS: '+label);};
const key=()=>crypto.randomBytes(16).toString('hex');
(async()=>{
 const b=await chromium.launch({executablePath:process.env.QA_BROWSER||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 try{
  const ac=await b.newContext(),a=await ac.newPage();
  async function login(page,role){await page.goto(base+'/login');const credentials=role==='reader'?data.reader_credentials:f[role];await page.locator('[name=email]').fill(credentials.email);await page.locator('[name=password]').fill(credentials.password);await Promise.all([page.waitForNavigation(),page.locator('form[action$="/login"] button[type=submit]').click()]);}
  await login(a,'admin');await a.goto(base+'/admin/loans');
  const token=await a.locator('[name=_csrf]').first().inputValue();
  check((await a.locator('#loanDialog [name=_request_key]').inputValue()).length===32,'Native checkout contains server-generated request key');
  const post=async(route,form,context=ac)=>{const r=await context.request.post(base+route,{form:{_csrf:token,...form}});return r.text();};
  const loan={reader_id:String(data.reader_id),'copy_ids[]':String(data.copy_id),borrowed_at:data.borrowed_at,due_at:data.due_at,note:'HTTP Day1'};
  const renew={item_id:String(data.item_id)},payment={reader_id:String(data.reader_id),amount:'1000',method:'cash',reference_code:'HTTP Day1',note:''};
  for(const [route,form] of [['/admin/loans',loan],['/admin/loans/renew',renew],['/admin/fines/payments',payment]]){
   const before=state(),html=await post(route,form);
   check(html.includes('flash-message error'),'Missing request key rejected: '+route);
   check(JSON.stringify(before)===JSON.stringify(state()),'Missing key has no database mutation: '+route);
  }
  const first=state(),checkoutKey=key(),html=await post('/admin/loans',{...loan,_request_key:checkoutKey}),code=html.match(/PM\d{6}[A-F0-9]{6}/)?.[0];
  check(!!code&&html.includes('Đã lập phiếu'),'HTTP checkout commits with request key');
  const checkoutState=state(),again=await post('/admin/loans',{...loan,_request_key:checkoutKey});
  check(again.includes('Đã lập phiếu '+code),'HTTP checkout replay returns original receipt code');
  check(checkoutState.loans===first.loans+1&&JSON.stringify(state())===JSON.stringify(checkoutState),'HTTP checkout replay creates no second header');
  check((await post('/admin/loans',{...loan,note:'Changed',_request_key:checkoutKey})).includes('flash-message error'),'Changed checkout payload rejected');
  const renewKey=key(),beforeRenew=state();
  check((await post('/admin/loans/renew',{...renew,_request_key:renewKey})).includes('Đã gia hạn'),'Staff renewal succeeds');
  const renewed=state();await post('/admin/loans/renew',{...renew,_request_key:renewKey});
  check(renewed.renewals===beforeRenew.renewals+1&&JSON.stringify(renewed)===JSON.stringify(state()),'Staff renewal replay preserves date and ledger');
  const rc=await b.newContext(),r=await rc.newPage();await login(r,'reader');await r.goto(base+'/account');
  const ownForm=r.locator('form[action$="/renew"]').filter({has:r.locator('[name=item_id][value="'+data.item_id+'"]')});
  check(await ownForm.count()===1,'Reader own renewal form is available');
  const readerToken=await ownForm.locator('[name=_csrf]').inputValue(),readerKey=await ownForm.locator('[name=_request_key]').inputValue();
  const ownPost=()=>rc.request.post(base+'/account/loans/renew',{form:{_csrf:readerToken,_request_key:readerKey,...renew}});
  check((await (await ownPost()).text()).includes('Đã gia hạn'),'Reader own renewal succeeds');
  const ownRenewed=state();check((await (await ownPost()).text()).includes('Đã gia hạn'),'Reader renewal replay succeeds at maximum renewal count');
  check(JSON.stringify(ownRenewed)===JSON.stringify(state()),'Reader replay does not extend date or create ledger');
  const payKey=key(),beforePay=state();
  check((await post('/admin/fines/payments',{...payment,_request_key:payKey})).includes('Đã ghi nhận'),'HTTP partial payment succeeds');
  const paid=state();check((await post('/admin/fines/payments',{...payment,_request_key:payKey})).includes('Đã ghi nhận'),'HTTP payment replay acknowledges original result');
  check(paid.fine_payments===beforePay.fine_payments+1&&JSON.stringify(paid)===JSON.stringify(state()),'HTTP partial payment is allocated only once');
  check((await post('/admin/fines/payments',{...payment,amount:'2000',_request_key:payKey})).includes('flash-message error'),'Changed payment amount rejected');
  check((await post('/admin/fines/payments',{...payment,'_request_key[]':'bad'})).includes('flash-message error'),'Array request key rejected');
  check((await post('/admin/fines/payments',{...payment,_request_key:'invalid'})).includes('flash-message error'),'Malformed request key rejected');
  check(JSON.stringify(paid)===JSON.stringify(state()),'Invalid payment requests preserve ledger');
  const zeroBefore=state();
  const retired=await post('/admin/books/'+data.zero_book_id+'/copies',{id:String(data.zero_copy_id),copy_code:data.zero_book_code+'-0001',barcode:'',shelf_location:'',item_condition:'good',status:'retired'});
  check(retired.includes('Đã lưu bản sao'),'HTTP can retire final returned copy');
  await a.goto(base+'/admin/books?q='+encodeURIComponent(data.zero_book_code));
  await a.locator('[data-book]').first().click();
  const bookForm=a.locator('#bookDialog');
  check(await bookForm.locator('[name=total_copies]').inputValue()==='0','Edit modal preserves zero inventory');
  check(await bookForm.locator('[name=total_copies]').evaluate(el=>el.checkValidity()),'Native form accepts zero inventory for existing title');
  check(await bookForm.locator('[name=category_id]').inputValue()==='0','Uncategorized title selects valid default category zero');
  await bookForm.locator('[name=title]').fill('Day1 retired title updated');
  await Promise.all([a.waitForNavigation(),bookForm.locator('.dialog-actions button').last().click()]);
  check((await a.locator('body').textContent()).includes('Đã lưu thông tin sách'),'HTTP can edit title with zero operational inventory');
  await a.goto(base+'/admin/books/'+data.zero_book_id+'/copies');
  check(await a.locator('tbody tr').count()===1&&(await a.locator('tbody').textContent()).includes('Ngừng sử dụng'),'Edit does not recreate retired physical copy');
  check(state().loans===zeroBefore.loans,'Retirement and title edit preserve loan history');
  fs.writeFileSync(path.join(process.env.QA_OUTPUT||path.join(root,'storage/backups/day1-20261011'),'http-regression.json'),JSON.stringify({status:'PASS',database:process.env.DB_NAME,base,checks,date:new Date().toISOString()},null,2));
  console.log('Day1 HTTP backend: '+checks+' checks passed.');
 }finally{await b.close();}
})().catch(e=>{console.error(e.stack);process.exit(1);});
