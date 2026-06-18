// Data-driven admin audit: log in once, GET every real admin route against the
// deterministic dev fixture, and machine-detect whether the screen actually
// RENDERS the seeded data (vs an empty/stub screen). No subjective judgement —
// the signals (seeded markers, 件数, empty markers, form fields) are captured raw.
//
//   node audit.cjs   ->  /tmp/admin-audit.jsonl  + a printed summary
// Prereq: composer db:reset (deterministic fixture) + serve:page:dev on 8081.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const B = 'http://127.0.0.1:8081';
const SUB = 'button[type="submit"], input[type="submit"], button';
const ROUTES = fs.readFileSync('/tmp/admin_routes.txt', 'utf8').split('\n').map(s => s.trim()).filter(Boolean);
// seeded-data markers (presence ⇒ the screen pulled real fixture data)
const MARK = ['サンプル商品', 'Sample Product', '管理画面用', '雑貨', 'alice@example.com', 'bob@example.com', '山田', '鈴木', '佐藤'];
const EMPTY = ['該当するデータがありません', '該当する商品がありません', '登録されていません', 'データが存在しません', '0件'];

async function fill(p, s, v) { await p.waitForSelector(s, { timeout: 10000 }); await p.$eval(s, e => { e.value = ''; }); await p.type(s, v); }
async function follow(p, s) {
  const [r] = await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), p.click(s)]);
  let l = r; for (let i = 0; i < 5; i++) { const loc = l && l.headers && l.headers()['location']; if (!loc) break; l = await p.goto(loc.startsWith('http') ? loc : B + loc, { waitUntil: 'networkidle2' }); }
  return l && l.status();
}

(async () => {
  const br = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'], defaultViewport: { width: 1280, height: 1000 } });
  const p = await br.newPage(); p.setDefaultTimeout(20000);
  await p.goto(B + '/admin/login', { waitUntil: 'networkidle2' });
  await fill(p, 'input[name="loginId"]', 'test-admin'); await fill(p, 'input[name="password"]', 'admin-test-password-2026'); await follow(p, SUB);
  if (await p.$('input[name="deviceToken"]')) { await fill(p, 'input[name="deviceToken"]', '123456'); await follow(p, SUB); }
  const out = [];
  for (const route of ROUTES) {
    let status = null;
    try { const r = await p.goto(B + route, { waitUntil: 'networkidle2' }); status = r && r.status(); } catch (e) { out.push({ route, status: 'ERR', error: e.message }); continue; }
    const sig = await p.evaluate((MARK, EMPTY) => {
      const t = document.body.innerText;
      const cm = t.match(/検索結果[：:]\s*([0-9,]+)\s*件/) || t.match(/([0-9,]+)\s*件が該当/) || t.match(/全\s*([0-9,]+)\s*件/);
      return {
        title: document.title,
        count: cm ? parseInt(cm[1].replace(/,/g, ''), 10) : null,
        marks: MARK.filter(m => t.includes(m)),
        empty: EMPTY.filter(m => t.includes(m)),
        formFields: document.querySelectorAll('form input:not([type=hidden]):not([type=submit]), form select, form textarea').length,
        tbodyRows: document.querySelectorAll('tbody tr').length,
        len: t.length,
      };
    }, MARK, EMPTY);
    // classify
    let cls;
    if (status !== 200) cls = `non-200(${status})`;           // POST/param/download/error endpoint
    else if (sig.count > 0 || sig.marks.length) cls = 'DATA';   // renders seeded data
    else if (sig.formFields >= 3) cls = 'FORM';                 // input form (no list expected)
    else if (sig.count === 0 || sig.empty.length) cls = 'EMPTY'; // list rendered but empty ⇒ stub candidate
    else cls = 'RENDER';                                        // renders, no list/data signal (static/info)
    out.push({ route, status, cls, count: sig.count, marks: sig.marks.length, rows: sig.tbodyRows, forms: sig.formFields, empty: sig.empty.length > 0, title: sig.title });
    console.log(`${cls}\t${status}\t${route}\tcount=${sig.count} marks=${sig.marks.length} rows=${sig.tbodyRows} forms=${sig.formFields}`);
  }
  fs.writeFileSync('/tmp/admin-audit.jsonl', out.map(x => JSON.stringify(x)).join('\n') + '\n');
  const by = {}; for (const o of out) by[o.cls] = (by[o.cls] || 0) + 1;
  console.log('\n=== classification ===', JSON.stringify(by));
  await br.close();
})().catch(e => console.error('FATAL', e.message));
