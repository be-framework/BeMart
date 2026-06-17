#!/usr/bin/env node
// BeMart spec-coverage browser harness.
//
// Drives the real BeMart HTML app with a real browser (puppeteer-core +
// system Chrome), PERFORMS each scenario's operations, and captures
// machine- AND human-verifiable evidence per item:
//   - <id>.png   full-page screenshot   (human judgement)
//   - <id>.html  rendered DOM           (machine grep/assert)
//   - one JSONL record {http_status, url, verifier_status:null, ...}
//
// The executor only records evidence (verifier_status stays null); a
// separate stronger model judges evidence vs the 期待結果 later.
//
// Usage:
//   node run.cjs [scenarios.json]
// Env (all optional, defaults target `composer serve:page:dev`):
//   BEMART_BASE  http://127.0.0.1:8081
//   CHROME_BIN   /Applications/Google Chrome.app/Contents/MacOS/Google Chrome
//   ADMIN_ID     test-admin
//   ADMIN_PW     admin-test-password-2026
//   DEV_2FA      123456   (BEMART_DEV_LOGIN magic code)
//   OUT          ../evidence-browser
//
// PREREQUISITES (see README.md): serve:page:dev up; test-admin seeded in
// the server's DATABASE_URL db (sql/seed/dtb-system-master.sql).
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const HERE = __dirname;
const BASE = process.env.BEMART_BASE || 'http://127.0.0.1:8081';
const CHROME = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ADMIN_ID = process.env.ADMIN_ID || 'test-admin';
const ADMIN_PW = process.env.ADMIN_PW || 'admin-test-password-2026';
const DEV_2FA = process.env.DEV_2FA || '123456';
const OUT = process.env.OUT || path.join(HERE, '..', 'evidence-browser');
const SCN = process.argv[2] || path.join(HERE, 'scenarios.json');
const SUBMIT = 'button[type="submit"], input[type="submit"], button';

const abs = (u) => (u.startsWith('http') ? u : BASE + u);
fs.mkdirSync(OUT, { recursive: true });

// Clear any server-prefilled value, then type (the admin login PoC prefills
// loginId/password — typing must REPLACE, not append).
async function fill(page, sel, val) {
  await page.waitForSelector(sel, { timeout: 10000 });
  await page.$eval(sel, (el) => { el.value = ''; });
  await page.type(sel, val);
}

// Submit + follow BeMart's PRG: state transitions return 200 + Location
// (NOT 302), which browsers do not auto-follow. Chase Location manually.
async function submitFollow(page, sel) {
  const [resp] = await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null),
    page.click(sel || SUBMIT),
  ]);
  let last = resp;
  for (let i = 0; i < 5; i++) {
    const loc = last && last.headers && last.headers()['location'];
    if (!loc) break;
    last = await page.goto(abs(loc), { waitUntil: 'networkidle2' });
  }
  return last && last.status();
}

// Click a button/link by its visible TEXT (EC-CUBE flow buttons like
// レジに進む / ゲスト購入 / 次へ / 注文する have no stable name attr), then
// follow PRG. Use for multi-step flows where CSS selectors are brittle.
async function clickByText(page, reStr) {
  const ok = await page.evaluate((s) => {
    const rx = new RegExp(s);
    const el = [...document.querySelectorAll('button, a.ec-blockBtn--action, a.ec-blockBtn--cancel, a.ec-blockBtn, input[type=submit]')]
      .find((e) => rx.test((e.textContent || e.value || '').trim()));
    if (!el) return false;
    el.setAttribute('data-ct', '1');
    return true;
  }, reStr);
  if (!ok) throw new Error('clickText: no element matching /' + reStr + '/');
  return submitFollow(page, '[data-ct="1"]');
}

async function adminLogin(page) {
  await page.goto(abs('/admin/login'), { waitUntil: 'networkidle2' });
  await fill(page, 'input[name="loginId"]', ADMIN_ID);
  await fill(page, 'input[name="password"]', ADMIN_PW);
  await submitFollow(page, SUBMIT); // -> /admin/two-factor-auth
  if (await page.$('input[name="deviceToken"]')) {
    await fill(page, 'input[name="deviceToken"]', DEV_2FA);
    await submitFollow(page, SUBMIT); // -> /admin/index
  }
  return page.url();
}

async function runStep(page, step) {
  switch (step.action) {
    case 'goto': {
      const r = await page.goto(abs(step.url), { waitUntil: 'networkidle2' });
      return r && r.status();
    }
    case 'fill': await fill(page, step.sel, step.value); return null;
    case 'select': await page.select(step.sel, step.value); return null;
    case 'selectFirst': await page.evaluate((sel) => { const s = document.querySelector(sel); const o = [...s.options].find((x) => x.value); if (o) s.value = o.value; }, step.sel); return null;
    case 'clickText': return clickByText(page, step.text);
    case 'check': await page.$eval(step.sel, (el) => { el.checked = true; }); return null;
    case 'click': {
      const [r] = await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null),
        page.click(step.sel),
      ]);
      return r && r.status();
    }
    case 'submitFollow': return submitFollow(page, step.sel);
    case 'wait': await page.waitForSelector(step.sel, { timeout: step.timeout || 10000 }); return null;
    default: throw new Error('unknown action: ' + step.action);
  }
}

async function cap(page, id, label, status, records, extra) {
  const png = `${id}.png`;
  const html = `${id}.html`;
  await page.screenshot({ path: path.join(OUT, png), fullPage: true });
  fs.writeFileSync(path.join(OUT, html), await page.content());
  records.push({
    id, label, url: page.url(), http_status: status ?? null,
    png, html, verifier_status: null, ...(extra || {}),
  });
  console.log(`[cap] ${id} | ${label} | ${page.url()} | status=${status ?? '—'}`);
}

(async () => {
  const scenarios = JSON.parse(fs.readFileSync(SCN, 'utf8'));
  const browser = await puppeteer.launch({
    executablePath: CHROME, headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--window-size=1280,1000'],
    defaultViewport: { width: 1280, height: 1000 },
  });
  const records = [];
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(20000);
    for (const s of scenarios) {
      const id = `${s.area}-${s.item_id}`;
      try {
        // Isolate each spec item: clear cookies so cart/login state never leaks
        // between items (every 結合試験 item is an independent test). Admin items
        // re-login per item via adminLogin below.
        try { const cdp = await page.target().createCDPSession(); await cdp.send('Network.clearBrowserCookies'); } catch (_) { /* best effort */ }
        if (s.auth === 'admin') { await adminLogin(page); }
        let lastStatus = null;
        for (const step of (s.steps || [])) {
          const st = await runStep(page, step);
          if (st != null) lastStatus = st;
        }
        await cap(page, id, s.title, lastStatus, records);
      } catch (e) {
        console.error(`[ERR] ${id}: ${e.message}`);
        await cap(page, id, s.title + ' [ERROR]', null, records, { error: e.message }).catch(() => {});
      }
    }
    fs.writeFileSync(path.join(OUT, 'browser-run.jsonl'), records.map((r) => JSON.stringify(r)).join('\n') + '\n');
    console.log(`\nDONE. ${records.length} records -> ${OUT}`);
  } finally {
    await browser.close();
  }
})();
