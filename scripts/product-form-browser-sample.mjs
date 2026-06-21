// Real-browser sample of the admin product editor against the running app.
// Proves a browser can submit the rendered form with an EMPTY stock field and
// get a success redirect (not the 400 the old transport schema produced).
//   BASE_URL=http://localhost:8081 node scripts/product-form-browser-sample.mjs
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:8081';
const out = [];
const browser = await chromium.launch();
const page = await browser.newPage();
try {
  await page.goto(`${BASE}/admin/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="loginId"]', 'test-admin');
  await page.fill('input[name="password"]', 'local-dev-admin-password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  if (page.url().includes('two-factor')) {
    await page.fill('input[name="deviceToken"]', '123456');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  }
  await page.goto(`${BASE}/admin/product/edit?productCode=sample-002`, { waitUntil: 'networkidle' });
  out.push(['logged in + product editor reachable', (await page.locator('form.doUpdateProduct').count()) > 0, page.url()]);

  await page.fill('input[name="stock"]', '');                       // empty = "unlimited"
  await page.fill('input[name="productName"]', 'Browser Sample Product');
  const [resp] = await Promise.all([
    page.waitForResponse(r => r.request().method() === 'POST').catch(() => null),
    page.click('form.doUpdateProduct button[type="submit"]'),
  ]);
  await page.waitForLoadState('networkidle');
  const status = resp ? resp.status() : 'n/a';
  const onErrorPage = (await page.locator('.error__message').count()) > 0;
  out.push(['empty-stock submit', `POST ${status}`, `errorPage=${onErrorPage}`, `=> ${page.url()}`]);
  await page.screenshot({ path: '/tmp/bsample-result.png' });
} catch (e) {
  out.push(['ERROR', String(e).split('\n')[0]]);
} finally {
  await browser.close();
}
console.log(JSON.stringify(out, null, 2));
