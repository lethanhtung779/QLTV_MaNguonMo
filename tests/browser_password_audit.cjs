const {chromium}=require('../storage/browser-qa/node_modules/playwright');
const fs=require('fs'),path=require('path');
const f=JSON.parse(fs.readFileSync(path.join(__dirname,'../storage/http_qa.json'),'utf8'));
const base=process.env.QA_BASE_URL||'http://127.0.0.1:8013';
const reproduce=process.argv.includes('--reproduce'),results=[];
const check=(v,label)=>{results.push({label,passed:!!v});console.log((v?'PASS: ':'REPRODUCED: ')+label);if(!v&&!reproduce)throw Error(label);};
(async()=>{const b=await chromium.launch({headless:true,executablePath:process.env.QA_BROWSER||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});try{
const ac=await b.newContext(),a=await ac.newPage();
async function login(p,email,password){await p.context().clearCookies();await p.goto(base+'/login');await p.locator('[name=email]').fill(email);await p.locator('[name=password]').fill(password);await Promise.all([p.waitForNavigation(),p.locator('form[action$="/login"] button[type=submit]').click()]);}
async function csrf(p,route){await p.goto(base+route);return p.locator('[name=_csrf]').first().inputValue();}
await login(a,f.admin.email,f.admin.password);
const tag='audit'+Date.now(),email=tag+'@example.test',pw='AuditStrongPassword123',changed='ChangedStrongPassword456';
async function saveStaff(id,password){return ac.request.post(base+'/admin/users',{form:{_csrf:await csrf(a,'/admin/users'),id:String(id),name:tag,email,role:'librarian',is_active:'1',password}});}
await saveStaff(0,pw);await a.goto(base+'/admin/users');const row=a.locator('tbody tr').filter({hasText:email});const id=(await row.locator('[data-dialog-open]').getAttribute('data-dialog-open')).replace('staff','');
const c1=await b.newContext(),p1=await c1.newPage(),c2=await b.newContext(),p2=await c2.newPage();await login(p1,email,pw);await login(p2,email,pw);
await saveStaff(id,changed);
const stale=await c1.request.get(base+'/admin/books',{maxRedirects:0});check(stale.status()===303&&stale.headers().location.endsWith('/login'),'Admin password reset revokes existing staff session');
await login(p1,email,changed);await login(p2,email,changed);
const newpw='PersonalChangedPassword789';const self=await c1.request.post(base+'/profile/password',{form:{_csrf:await csrf(p1,'/profile'),current_password:changed,new_password:newpw,password_confirmation:newpw}});
check((await self.text()).includes('Đã cập nhật'),'Personal password change succeeds');
check((await c1.request.get(base+'/admin/books',{maxRedirects:0})).status()===200,'Current password-change session remains usable');
check((await c2.request.get(base+'/admin/books',{maxRedirects:0})).status()===303,'Personal password change revokes other staff sessions');
const prefix='Aa9'+ 'x'.repeat(69),long=prefix+'one';const longResponse=await saveStaff(id,long);check((await longResponse.text()).includes('flash-message error'),'Staff password exceeding 72 bytes rejected');
const lc=await b.newContext(),lp=await lc.newPage();await login(lp,email,prefix+'two');check(lp.url().includes('/login'),'Different password sharing first 72 bytes cannot authenticate');
await login(p1,email,reproduce?long:newpw);const multi='Aa9'+'ế'.repeat(30);const multiResp=await c1.request.post(base+'/profile/password',{form:{_csrf:await csrf(p1,'/profile'),current_password:reproduce?long:newpw,new_password:multi,password_confirmation:multi}});check((await multiResp.text()).includes('flash-message error'),'Multibyte staff password exceeding 72 bytes rejected');
const readerEmail=tag+'reader@example.test';const linked=await ac.request.post(base+'/admin/readers',{form:{_csrf:await csrf(a,'/admin/readers'),id:'0',card_number:tag,full_name:'Audit Reader',email:readerEmail,account_password:long,expires_on:'2027-12-31',is_active:'1'}});check((await linked.text()).includes('flash-message error'),'Linked reader password exceeding 72 bytes rejected');
const rc=await b.newContext(),rp=await rc.newPage();await login(rp,readerEmail,long);check(rp.url().includes('/login'),'Rejected linked reader creation leaves no account');
// Native confirmation, on a real form, must leave controls usable after cancellation.
await a.goto(base+'/admin/books');const form=a.locator('form[data-confirm]').first(),button=form.locator('button').first();const text=await button.textContent();a.once('dialog',d=>d.dismiss());await button.click();check(await button.isEnabled()&&(await button.textContent())===text,'Cancelling confirmation preserves usable submit button');
// Reader session invalidation via actual profile POST.
const r1=await b.newContext(),r2=await b.newContext(),pr1=await r1.newPage(),pr2=await r2.newPage();await login(pr1,f.reader.email,f.reader.password);await login(pr2,f.reader.email,f.reader.password);
const readerPw='ReaderChangedPassword123';const rr=await r1.request.post(base+'/account/password',{form:{_csrf:await csrf(pr1,'/account/profile'),current_password:f.reader.password,new_password:readerPw,password_confirmation:readerPw}});
check((await rr.text()).includes('Đã đổi mật khẩu'),'Reader password change succeeds');check((await r1.request.get(base+'/account/profile',{maxRedirects:0})).status()===200,'Current reader session remains usable');check((await r2.request.get(base+'/account/profile',{maxRedirects:0})).status()===303,'Reader password change revokes other reader sessions');
// Restore fixture password through current session; isolated DB only.
await r1.request.post(base+'/account/password',{form:{_csrf:await csrf(pr1,'/account/profile'),current_password:readerPw,new_password:f.reader.password,password_confirmation:f.reader.password}});
if(!reproduce){
const bounded=await saveStaff(id,prefix);check((await bounded.text()).includes('flash-message success'),'Exactly 72-byte staff password accepted');
await login(lp,email,prefix);check(!lp.url().includes('/login'),'Exactly 72-byte password authenticates');
await login(lp,email,prefix+'suffix');check(lp.url().includes('/login'),'Login rejects suffix after an exact 72-byte password');
const spaced='  SpacePreservedPassword123  ';await saveStaff(id,spaced);await login(lp,email,spaced);check(!lp.url().includes('/login'),'Staff password preserves leading/trailing spaces');
await login(lp,email,spaced.trim());check(lp.url().includes('/login'),'Trimmed password cannot authenticate');
await login(p1,email,spaced);const suffixCurrent=await c1.request.post(base+'/profile/password',{form:{_csrf:await csrf(p1,'/profile'),current_password:prefix+'suffix',new_password:newpw,password_confirmation:newpw}});check((await suffixCurrent.text()).includes('flash-message error'),'Current password over 72 bytes rejected');
const arrayResp=await ac.request.post(base+'/admin/users',{form:{_csrf:await csrf(a,'/admin/users'),id:String(id),name:tag,email,role:'librarian',is_active:'1','password[]':'Invalid'}});check((await arrayResp.text()).includes('flash-message error'),'Password arrays rejected without changing account');
const nullResp=await saveStaff(id,'ValidPassword12'+String.fromCharCode(0));check((await nullResp.text()).includes('flash-message error'),'Password containing NUL rejected');
const guest=await b.newContext(),gp=await guest.newPage();const regToken=await csrf(gp,'/register');const reg=await guest.request.post(base+'/register',{form:{_csrf:regToken,name:'Boundary Reader',email:tag+'register@example.test',password:long,password_confirmation:long,phone:''}});check((await reg.text()).includes('flash-message error'),'Public registration rejects overlong password');
}
fs.writeFileSync(path.join(process.env.QA_OUTPUT||path.join(__dirname,'../storage/backups/audit-20261011'),'password-'+(reproduce?'before':'after')+'.json'),JSON.stringify({time:new Date().toISOString(),base,results},null,2));
}finally{await b.close();}})().catch(e=>{console.error(e);process.exitCode=1;});