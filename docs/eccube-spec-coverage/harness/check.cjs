const puppeteer = require('puppeteer-core');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const B = 'http://127.0.0.1:8081';
const SUB = 'button[type="submit"], input[type="submit"], button';
async function fill(p, s, v) { await p.waitForSelector(s, { timeout: 10000 }); await p.$eval(s, e => { e.value = ''; }); await p.type(s, v); }
async function follow(p, s) {
  const [r] = await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), p.click(s)]);
  let l = r;
  for (let i = 0; i < 5; i++) { const loc = l && l.headers && l.headers()['location']; if (!loc) break; l = await p.goto(loc.startsWith('http') ? loc : B + loc, { waitUntil: 'networkidle2' }); }
  return l && l.status();
}
(async () => {
  const br = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'], defaultViewport: { width: 1280, height: 1100 } });
  const p = await br.newPage(); p.setDefaultTimeout(20000);
  await p.goto(B + '/admin/login', { waitUntil: 'networkidle2' });
  await fill(p, 'input[name="loginId"]', 'test-admin'); await fill(p, 'input[name="password"]', 'admin-test-password-2026'); await follow(p, SUB);
  if (await p.$('input[name="deviceToken"]')) { await fill(p, 'input[name="deviceToken"]', '123456'); await follow(p, SUB); }
  console.log('after login:', p.url());
  await p.goto(B + '/admin/index', { waitUntil: 'networkidle2' });
  await p.screenshot({ path: '/tmp/live-dashboard.png' });
  const dash = await p.evaluate(() => { const t = document.body.innerText; const m = t.match(/取扱商品数[\s\S]{0,30}/); return m ? m[0].replace(/\s+/g, ' ') : '(取扱商品数 not found)'; });
  console.log('DASHBOARD:', dash);
  const r = await p.goto(B + '/admin/product-list', { waitUntil: 'networkidle2' });
  await p.screenshot({ path: '/tmp/live-productlist.png' });
  const info = await p.evaluate(() => { const rows = document.querySelectorAll('tbody tr').length; const t = document.body.innerText; const m = t.match(/検索結果[\s\S]{0,30}|[0-9,]+\s*件/); return { rows, hint: m ? m[0].replace(/\s+/g, ' ') : '(件数表示なし)', codes: (t.match(/CODE0000\d+/g) || []).slice(0, 3) }; });
  console.log('PRODUCT-LIST status:', r && r.status(), ' tbody rows:', info.rows, ' 件数:', info.hint, ' codes:', info.codes.join(','));
  const ro = await p.goto(B + '/admin/order-list', { waitUntil: 'networkidle2' });
  const oi = await p.evaluate(() => ({ rows: document.querySelectorAll('tbody tr').length, hint: (document.body.innerText.match(/検索結果[\s\S]{0,30}|[0-9,]+\s*件/) || ['?'])[0].replace(/\s+/g, ' '), err: (document.body.innerText.match(/Invalid input[^\n]{0,180}|\[orders[^\n]{0,140}/) || ['(no err text)'])[0].replace(/\s+/g, ' ') }));
  console.log('ORDER-LIST status:', ro && ro.status(), ' rows:', oi.rows, ' err:', oi.err);
  const rc = await p.goto(B + '/admin/customer-list', { waitUntil: 'networkidle2' });
  const ci = await p.evaluate(() => ({ rows: document.querySelectorAll('tbody tr').length, hint: (document.body.innerText.match(/検索結果[\s\S]{0,30}|[0-9,]+\s*件/) || ['?'])[0].replace(/\s+/g, ' ') }));
  console.log('CUSTOMER-LIST status:', rc && rc.status(), ' rows:', ci.rows, ' 件数:', ci.hint);
  await br.close();
})().catch(e => console.error('ERR', e.message));
