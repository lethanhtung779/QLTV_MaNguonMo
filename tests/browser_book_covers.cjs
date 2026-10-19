const {chromium}=require('../storage/browser-qa/node_modules/playwright');
const fs=require('fs');
const out='storage/backups/covers-20261011';
const books=JSON.parse(fs.readFileSync(out+'/books-before.json','utf8'));
const checks=[];const pages=[];
function check(ok,label){checks.push({passed:!!ok,label});if(!ok)throw Error(label);}
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});try{
const c=await browser.newContext({deviceScaleFactor:1,locale:'vi-VN'}),p=await c.newPage();const errors=[],failed=[];
p.on('pageerror',e=>errors.push(e.message));p.on('response',r=>{if(r.request().resourceType()==='image'&&r.status()>=400)failed.push({url:r.url(),status:r.status()});});
for(const width of [1440,1024,768,390]){
 await p.setViewportSize({width,height:900});
 for(const route of ['/','/catalog','/catalog?page=2','/catalog?page=3',...books.map(b=>'/book/'+b.id)]){
  const r=await p.goto('http://127.0.0.1:8010'+route);check(r.status()===200,width+' '+route+' HTTP200');
  await p.locator('.book-cover-wrap, .book-detail-cover').last().scrollIntoViewIfNeeded();
  check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),width+' '+route+' no page overflow');
  check(await p.evaluate(()=>devicePixelRatio===1&&visualViewport.scale===1),width+' '+route+' zoom100%');
  const covers=await p.locator('[data-book-cover-placeholder]:visible').allTextContents();check(covers.length>0&&covers.every(s=>s.includes('Chưa có bìa xác minh')&&s.trim().length>20),width+' '+route+' named honest fallback');
  if(route.startsWith('/book/')){const b=books.find(b=>route==='/book/'+b.id);check((await p.locator('h1').innerText())===b.title,width+' '+route+' title matches MySQL');check((await p.locator('.book-author').innerText()).includes(b.author),width+' '+route+' author matches MySQL');check((await p.locator('.book-metadata').innerText()).includes(String(b.publication_year)),width+' '+route+' edition metadata matches MySQL');}
  pages.push({width,route,passed:true});
  if(['/','/catalog','/book/1'].includes(route)){await p.evaluate(()=>scrollTo(0,0));await p.screenshot({path:out+'/main-'+width+'-'+(route==='/'?'home':route.replaceAll('/','-'))+'.png',fullPage:true});}
 }
 await p.goto('http://127.0.0.1:8010/catalog');const link=p.locator('.book-card .text-link').first();const expected=await link.getAttribute('href');await Promise.all([p.waitForNavigation(),link.click()]);check(p.url().endsWith(expected),width+' detail button navigates');
 await p.goto('http://127.0.0.1:8010/');await p.locator('.featured-section').screenshot({path:out+'/featured-'+width+'.png'});
 await c.route('https://**/*',route=>route.abort());await p.reload();check((await p.locator('.featured-section [data-book-cover-placeholder]:visible').count())>0,width+' fallback remains with internet blocked');await c.unroute('https://**/*');
}
check(errors.length===0,'No JavaScript errors');check(failed.length===0,'No cover404 responses');await c.close();
// Authenticated screens and local-file/offline handling use the independent test DB.
const f=JSON.parse(fs.readFileSync('storage/http_qa.json','utf8'));
for(const role of ['admin','librarian','reader']){const ctx=await browser.newContext({deviceScaleFactor:1,locale:'vi-VN'}),page=await ctx.newPage();await page.goto('http://127.0.0.1:8015/login');await page.locator('[name=email]').fill(f[role].email);await page.locator('[name=password]').fill(f[role].password);await Promise.all([page.waitForNavigation(),page.locator('form[action$="/login"] button[type=submit]').click()]);
 for(const width of [1440,1024,768,390]){await page.setViewportSize({width,height:900});for(const route of role==='reader'?['/account']:['/admin','/admin/books','/admin/loans','/admin/books/'+f.book_id+'/copies','/admin/readers/'+f.reader.reader_id]){const r=await page.goto('http://127.0.0.1:8015'+route);check(r.status()===200,role+width+route+' HTTP200');check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),role+width+route+' no page overflow');check(await page.locator('[data-book-cover-placeholder]').count()>0,role+width+route+' shared cover component');if(width===390)await page.screenshot({path:out+'/'+role+'-'+route.replaceAll('/','-')+'-390.png',fullPage:true});}}
 await ctx.close();}
const ctx=await browser.newContext(),page=await ctx.newPage();await page.goto('http://127.0.0.1:8015/catalog');const img=page.locator('img[data-cover-fallback]').first();check(await img.count()===1,'QA existing local upload available (technical fixture, not a verified production cover)');await img.scrollIntoViewIfNeeded();await img.evaluate(i=>i.decode());const src=await img.getAttribute('src');check(new URL(src,'http://127.0.0.1:8015').origin==='http://127.0.0.1:8015','Uploaded cover same origin');check(await img.evaluate(i=>getComputedStyle(i).objectFit==='contain'),'Uploaded cover contain without crop');await ctx.setOffline(true);check(await img.evaluate(i=>i.complete&&i.naturalWidth>0),'Loaded local image remains offline');await ctx.setOffline(false);await ctx.route('https://**/*',r=>r.abort());await page.reload();await page.locator('img[data-cover-fallback]').first().scrollIntoViewIfNeeded();check(await page.locator('img[data-cover-fallback]').first().evaluate(i=>i.decode().then(()=>i.naturalWidth>0)), 'Local upload reload without Internet');await ctx.unroute('https://**/*');
await page.route('**/covers/**',r=>r.abort());await page.reload();const broken=page.locator('img[data-cover-fallback]').first();await broken.scrollIntoViewIfNeeded().catch(()=>{});await page.waitForTimeout(300);check(await broken.isHidden(),'Broken local image hidden');check(await broken.evaluate(i=>!i.nextElementSibling.hidden&&i.nextElementSibling.textContent.includes('Chưa có bìa xác minh')),'Broken image reveals named fallback');await ctx.close();
fs.writeFileSync(out+'/browser-result.json',JSON.stringify({time:new Date().toISOString(),browser:browser.version(),pages,checks,productionVerifiedCovers:0,productionMissingCovers:books.length,main:'paper_page_library',writeTests:'isolated QA8015 only'},null,2));console.log('Cover browser regression: '+checks.length+' checks PASS; '+pages.length+' public renders; real verified covers 0/'+books.length+'.');
}finally{await browser.close();}})().catch(e=>{fs.writeFileSync(out+'/browser-failure.json',JSON.stringify({error:e.stack,checks,pages},null,2));console.error(e);process.exitCode=1;});