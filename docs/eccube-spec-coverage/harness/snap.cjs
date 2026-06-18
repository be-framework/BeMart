// Re-capture admin screenshots against the DETERMINISTIC dev fixture.
// Only routes that return a real 200 admin screen get a PNG; non-200
// operation endpoints have any stale PNG removed so the status page never
// links a misleading shot. PNG name matches build_status_html.admin_shot:
// slug = route after "/admin/" with \W+ -> "_".
//
//   composer db:reset   (deterministic data)   THEN   serve:page:dev on 8081
//   node snap.cjs       -> admin-sweep/*.png  + /tmp/snap-manifest.json
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const B = 'http://127.0.0.1:8081';
const SUB = 'button[type="submit"], input[type="submit"], button';
const OUT = path.join(__dirname, '..', 'admin-sweep');
const ROUTES = JSON.parse(fs.readFileSync('/tmp/admin_routes.json', 'utf8'));
const slug = (r) => r.slice('/admin/'.length).replace(/\W+/g, '_');

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

  const manifest = [];
  for (const route of ROUTES) {
    const file = path.join(OUT, `${slug(route)}.png`);
    let status = null;
    try { const r = await p.goto(B + route, { waitUntil: 'networkidle2' }); status = r && r.status(); } catch (e) { status = 'ERR'; }
    const rendered = status === 200 && await p.evaluate(() => {
      const t = document.body.innerText || '';
      // Real admin screen = the ported admin frame is present and no PHP
      // error surfaced. innerText length is NOT a signal (the sidebar nav
      // is CSS-collapsed, so a valid page's visible text can be ~100 chars).
      return document.querySelector('.c-container') !== null && !/Whoops|Fatal error|Uncaught|Stack trace|Parse error/.test(t);
    }).catch(() => false);
    if (rendered) {
      await p.screenshot({ path: file, fullPage: true });
      manifest.push({ route, slug: slug(route), status, captured: true });
      console.log(`SHOT  ${status}  ${route}`);
    } else {
      if (fs.existsSync(file)) fs.unlinkSync(file);   // drop stale/misleading PNG
      manifest.push({ route, slug: slug(route), status, captured: false });
      console.log(`skip  ${status}  ${route}  (non-200 / no render -> PNG removed)`);
    }
  }
  fs.writeFileSync('/tmp/snap-manifest.json', JSON.stringify(manifest, null, 2));
  const shot = manifest.filter(m => m.captured).length;
  console.log(`\n=== captured ${shot}/${manifest.length} admin screens ===`);
  await br.close();
})().catch(e => { console.error('FATAL', e.message); process.exit(1); });
