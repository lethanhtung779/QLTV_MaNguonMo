const {chromium}=require('../storage/browser-qa/node_modules/playwright');
const fs=require('fs'),path=require('path');
const base=process.env.QA_BASE_URL||'http://127.0.0.1:8015';
if(new URL(base).port!=='8015')throw Error('Day 2 requires isolated QA server 8015');
const f=JSON.parse(fs.readFileSync('storage/http_qa.json','utf8'));
const out=process.env.QA_OUTPUT||'storage/backups/day2-20261011/before';fs.mkdirSync(out,{recursive:true});
let checks=[],pages=[],errors=[],dialogs=[];
function check(ok,label,details={}){checks.push({status:ok?'PASS':'FAIL',label,...details});console.log((ok?'PASS':'FAIL')+': '+label);}
const routes={guest:['/','/catalog','/book/'+f.book_id,'/login','/register','/catalog?q=NO_RESULT_DAY2_987654','/missing-day2'],admin:['/admin','/admin/books','/admin/books/'+f.book_id+'/copies','/admin/readers','/admin/readers/'+f.reader.reader_id,'/admin/authors','/admin/publishers','/admin/categories','/admin/loans','/admin/loans?status=overdue','/admin/fines','/admin/statistics','/admin/users','/admin/permissions','/admin/activity','/admin/settings','/notifications','/profile'],librarian:['/admin','/admin/books','/admin/books/'+f.book_id+'/copies','/admin/readers','/admin/loans','/admin/loans?status=overdue','/admin/fines','/admin/authors','/admin/publishers','/admin/categories','/admin/statistics','/notifications','/profile'],reader:['/account','/account/profile','/notifications','/catalog','/book/'+f.book_id]};
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.QA_BROWSER||'C:/Program Files/Google/Chrome/Application/chrome.exe'});let version=browser.version();try{
for(const [role,paths] of Object.entries(routes)){
 const ctx=await browser.newContext({deviceScaleFactor:1,locale:'vi-VN'}),p=await ctx.newPage();p.on('pageerror',e=>errors.push({role,error:e.message}));
 if(role!=='guest'){await p.goto(base+'/login');await p.locator('[name=email]').fill(f[role].email);await p.locator('[name=password]').fill(f[role].password);await Promise.all([p.waitForNavigation(),p.locator('form[action$="/login"] button[type=submit]').click()]);check(p.url().endsWith(role==='reader'?'/account':'/admin'),role+' login');}
 for(const width of [1440,1366,1024,768,390]){await p.setViewportSize({width,height:900});for(const route of paths){
 const started=Date.now(),response=await p.goto(base+route,{waitUntil:'networkidle'});await p.evaluate(()=>document.fonts.ready);await p.evaluate(async()=>{
 for(const image of [...document.images]){
  if(!image.complete || image.naturalWidth===0){image.scrollIntoView({behavior:'instant',block:'center'});await new Promise(resolve=>{if(image.complete){resolve();return;}image.addEventListener('load',resolve,{once:true});image.addEventListener('error',resolve,{once:true});setTimeout(resolve,5000);});}
 }
 window.scrollTo({top:0,behavior:'instant'});
});const data=await p.evaluate(()=>({scroll:document.documentElement.scrollWidth,width:innerWidth,scale:visualViewport.scale,dpr:devicePixelRatio,lang:document.documentElement.lang,brokenImages:[...document.images].filter(x=>!x.complete||x.naturalWidth===0).map(x=>x.src),fonts:[...document.fonts].map(x=>({family:x.family,status:x.status})),controls:document.querySelectorAll('button,a,input,select,textarea').length,bodyFont:getComputedStyle(document.body).fontFamily,h1Font:document.querySelector('h1')?getComputedStyle(document.querySelector('h1')).fontFamily:null}));
 const expected=route==='/missing-day2'?404:200;const ok=response.status()===expected&&data.scroll<=width+1&&data.scale===1&&data.dpr===1&&data.brokenImages.length===0;
 const name=role+'-'+route.replace(/[^a-z0-9]+/gi,'_')+'-'+width;await p.screenshot({path:path.join(out,name+'.png'),fullPage:true});
 pages.push({role,route,width,status:ok?'PASS':'FAIL',http:response.status(),duration_ms:Date.now()-started,screenshot:path.join(out,name+'.png'),...data});check(ok,role+' '+route+' '+width+'px', {http:response.status(),scroll:data.scroll});
 }}
 await p.setViewportSize({width:390,height:900});
 if(role==='guest'){
 await p.goto(base+'/');await p.locator('[data-menu-toggle]').click();check(await p.locator('[data-mobile-menu]').evaluate(e=>e.classList.contains('is-open')),'Public mobile menu opens');await p.locator('[data-menu-toggle]').click();check(!(await p.locator('[data-mobile-menu]').evaluate(e=>e.classList.contains('is-open'))),'Public mobile menu closes');
 await p.goto(base+'/login');await p.locator('form[action$="/login"] button[type=submit]').click();check(await p.locator('[name=email]').evaluate(e=>!e.validity.valid),'Empty login validates before submit');await p.locator('[name=password]').fill('ExamplePassword123');await p.locator('[data-password-toggle]').click();check(await p.locator('[name=password]').getAttribute('type')==='text','Show password');await p.locator('[data-password-toggle]').click();check(await p.locator('[name=password]').getAttribute('type')==='password','Hide password');
 await p.goto(base+'/catalog');const filter=p.locator('.filter-bar');await filter.locator('[name=q]').fill('NO_RESULT_DAY2_987654');await Promise.all([p.waitForNavigation(),filter.locator('button').click()]);check(await p.locator('.empty-state').isVisible(),'Search empty state');await p.locator('.empty-state a').click();check(await p.locator('.book-card').count()>0,'Clear filters restores books');
 const pagination=p.locator('.pagination a').first();if(await pagination.count()){await pagination.click();check(new URL(p.url()).searchParams.has('page'),'Catalog pagination uses server URL');}
 await p.goto(base+'/register');await p.locator('[name=password]').fill('short');check(!(await p.locator('[name=password]').evaluate(e=>e.checkValidity())),'Registration password policy message');
 }else{
 await p.goto(base+(role==='reader'?'/account':'/admin'));await p.locator('[data-menu-toggle]').click();check(await p.locator('[data-sidebar]').evaluate(e=>e.classList.contains('is-open')),role+' sidebar opens');await p.locator('[data-backdrop]').click({position:{x:380,y:450}});check(!(await p.locator('[data-sidebar]').evaluate(e=>e.classList.contains('is-open'))),role+' sidebar closes');
 }
 if(role==='admin'){
 for(const route of ['/admin/books','/admin/readers','/admin/categories','/admin/authors','/admin/publishers','/admin/loans','/admin/users']){
 await p.goto(base+route);const ids=await p.locator('[data-dialog-open]').evaluateAll(es=>[...new Set(es.map(e=>e.dataset.dialogOpen))]);
 for(const id of ids.slice(0,3)){await p.locator('[data-dialog-open="'+id+'"]').first().click();const d=p.locator('#'+id);const rect=await d.evaluate(e=>{const r=e.getBoundingClientRect();return{x:r.x,y:r.y,right:r.right,bottom:r.bottom};});check(await d.evaluate(e=>e.open)&&rect.x>=0&&rect.right<=390&&rect.y>=0&&rect.bottom<=901,'Dialog mobile bounds '+id);await p.screenshot({path:path.join(out,'dialog-'+id+'.png')});dialogs.push({route,id,rect});await p.keyboard.press('Escape');check(!(await d.evaluate(e=>e.open)),'Dialog Escape '+id);}
 }
 await p.goto(base+'/admin/books');await p.locator('[data-dialog-open=bookDialog]').first().click();const d=p.locator('#bookDialog'),r=await d.boundingBox();await p.mouse.click(r.x+4,r.y+4);const open=await d.evaluate(e=>e.open);check(open,'Click inside dialog padding preserves draft');await p.screenshot({path:path.join(out,'dialog-padding.png')});if(open)await p.keyboard.press('Escape');
 await p.goto(base+'/admin/loans');await p.locator('[data-dialog-open=loanDialog]').click();const search=p.getByRole('searchbox',{name:'Tìm bản sao'});await search.fill('NO_MATCH_DAY2_987654');await p.locator('#loanDialog [data-dialog-close]').first().click();await p.locator('[data-dialog-open=loanDialog]').click();check(await search.inputValue()===''&&await p.locator('.copy-picker label:visible').count()>0,'Reopening checkout clears picker filter');await p.screenshot({path:path.join(out,'picker-reopen.png')});
 await p.locator('#loanDialog [name=reader_id]').selectOption(String(f.reader.reader_id));const previous=p.url();await p.locator('#loanDialog .dialog-actions button').last().click();await p.waitForTimeout(700);check(await p.locator('#loanDialog').evaluate(e=>e.open),'Checkout requires at least one copy before navigation');check(p.url()===previous,'Invalid copy selection does not navigate');await p.screenshot({path:path.join(out,'picker-empty-submit.png')});
 }
 await ctx.close();
}
check(errors.length===0,'No uncaught JavaScript exceptions',{errors});
const report={time:new Date().toISOString(),base,browser:'Google Chrome',version,zoom:'100%: deviceScaleFactor=1 and visualViewport.scale=1 verified on each render',pages,dialogs,checks,errors,summary:{pages:pages.length,checks:checks.length,pass:checks.filter(x=>x.status==='PASS').length,fail:checks.filter(x=>x.status==='FAIL').length}};fs.writeFileSync(path.join(out,'result.json'),JSON.stringify(report,null,2));console.log(JSON.stringify(report.summary));if(report.summary.fail)process.exitCode=1;
}finally{await browser.close();}})().catch(e=>{console.error(e);fs.writeFileSync(path.join(out,'interrupted.json'),JSON.stringify({checks,pages,errors,error:e.stack},null,2));process.exitCode=1;});