const {chromium}=require('../storage/browser-qa/node_modules/playwright'),fs=require('fs'),path=require('path');
const fixture=JSON.parse(fs.readFileSync(path.join(__dirname,'../storage/http_qa.json'),'utf8'));
const base=process.env.QA_BASE_URL||'http://127.0.0.1:8011';
(async()=>{
const b=await chromium.launch({executablePath:process.env.QA_BROWSER||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true}),c=await b.newContext(),p=await c.newPage();
await p.goto(base+'/login');await p.locator('[name=email]').fill(fixture.admin.email);await p.locator('[name=password]').fill(fixture.admin.password);await Promise.all([p.waitForNavigation(),p.locator('form[action$="/login"] button[type=submit]').click()]);
let checks=0;
for (const [extension,mime] of [['jpg','image/jpeg'],['webp','image/webp']]) {
 await p.goto(base+'/admin/books');await p.locator('[data-dialog-open=bookDialog]').first().click();const f=p.locator('#bookDialog');
 const tag='FORMAT-'+extension+'-'+Date.now();await f.locator('[name=book_code]').fill(tag);await f.locator('[name=title]').fill(tag);await f.locator('[name=author]').fill('QA Image');await f.locator('[name=total_copies]').fill('1');
 await f.locator('[name=cover]').setInputFiles(path.join(__dirname,'../storage/qa-format.'+extension));
 await Promise.all([p.waitForNavigation(),f.locator('.dialog-actions button').last().click()]);
 if(await p.locator('.flash-message.success').count()!==1)throw Error('Upload failed '+extension);checks++;
 await p.goto(base+'/admin/books?q='+tag);const url=await p.locator('img[src*="/covers/"]').getAttribute('src');
 if(!url.endsWith('.'+extension))throw Error('Extension mismatch');checks++;
 const r=await c.request.get(base+url);if(r.status()!==200||r.headers()['content-type']!==mime)throw Error('MIME mismatch');checks++;
 console.log('PASS: '+extension+' real upload, random extension and HTTP MIME');
}
fs.writeFileSync(path.join(__dirname,'../storage/cover_format_result.json'),JSON.stringify({status:'PASS',checks}));
await b.close();console.log('Cover format E2E: '+checks+' passed.');
})().catch(e=>{console.error(e.message);process.exit(1)});
