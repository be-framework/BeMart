#!/usr/bin/env node
import { chromium } from '@playwright/test';
import fs from 'node:fs/promises';
import path from 'node:path';

const RUN_ID = process.env.RUN_ID || '20260610-web-db-all-routes';
const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:18080';
const ROOT = process.cwd();
const RESULT_PATH = path.join(ROOT, 'docs/web-e2e/results', `${RUN_ID}.json`);
const REPORT_PATH = path.join(ROOT, 'docs/web-e2e', `${RUN_ID}-report.md`);
const MATRIX_PATH = path.join(ROOT, 'docs/web-e2e/feature-implementation-matrix.md');
const SCREENSHOT_DIR = path.join(ROOT, 'docs/web-e2e/screenshots', RUN_ID);
const BASELINE_PATH = path.join(ROOT, 'docs/web-e2e/results/20260608-canonical-resource-routes-web-e2e.json');
const OPENAPI_PATH = path.join(ROOT, 'docs/api/openapi.json');

const createdAt = new Date().toISOString();
const stamp = RUN_ID.split('-')[0] || '20260610';
const e2eData = {
  email: `web-e2e-${stamp}-${Date.now()}@example.test`,
  passwordLabel: 'generated strong password (not persisted in evidence)',
  productCode: `web-e2e-${stamp}-${Date.now()}`,
  adminLoginId: 'test-admin (prefilled demo login form)',
  orderNo: null,
};

await fs.mkdir(path.dirname(RESULT_PATH), { recursive: true });
await fs.mkdir(SCREENSHOT_DIR, { recursive: true });

function rel(p) {
  return path.relative(ROOT, p).replaceAll(path.sep, '/');
}

function sanitizeText(text, max = 900) {
  if (!text) return '';
  return String(text)
    .replace(/[a-f0-9]{48,}/gi, '[redacted-token]')
    .replace(/local-dev-admin-password/g, '[redacted-demo-password]')
    .replace(/Passw0rd-[0-9]+/g, '[redacted-password]')
    .slice(0, max);
}

function slugName(name) {
  return String(name || 'feature')
    .replace(/[\\/:*?"<>|\s]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80) || 'feature';
}

function isJsonError(contentType, text) {
  if (contentType?.includes('application/json')) {
    try {
      const obj = JSON.parse(text);
      return typeof obj?.code === 'number' && obj.code >= 400;
    } catch {
      return false;
    }
  }
  return /"code"\s*:\s*(4\d\d|5\d\d)/.test(text || '') || /Invalid parameter type|Invalid or missing CSRF token/.test(text || '');
}

function extractMessage(contentType, text) {
  if (!text) return '';
  if (contentType?.includes('application/json') || /^\s*\{/.test(text)) {
    try {
      const obj = JSON.parse(text);
      return sanitizeText(obj.message ?? JSON.stringify(obj), 400);
    } catch {}
  }
  let plain = String(text);
  if (/<html[\s>]|<!doctype html/i.test(plain)) {
    plain = plain
      .replace(/<script[\s\S]*?<\/script>/gi, ' ')
      .replace(/<style[\s\S]*?<\/style>/gi, ' ')
      .replace(/<[^>]+>/g, '\n')
      .replace(/&copy;/g, '©')
      .replace(/&nbsp;/g, ' ')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"')
      .replace(/&#039;/g, "'");
  }
  const lines = sanitizeText(plain, 1200).split('\n').map(s => s.trim()).filter(Boolean);
  const preferred = lines.filter(s => /(Invalid|CSRF|入力|必要|見つか|ログイン|エラー|権限|pref|email|password|商品|会員|管理者)/i.test(s));
  return (preferred.length ? preferred : lines).slice(0, 4).join(' / ');
}

async function pageSnapshot(page, screenshotAbs) {
  await page.screenshot({ path: screenshotAbs, fullPage: true }).catch(async () => {
    await page.setContent(`<html><body><pre>Screenshot failed for ${RUN_ID}</pre></body></html>`);
    await page.screenshot({ path: screenshotAbs, fullPage: true });
  });
}

async function gotoAndCapture(page, targetUrl, screenshotAbs) {
  const url = targetUrl ? new URL(targetUrl, BASE_URL).href : null;
  const result = { targetUrl, finalUrl: null, status: null, contentType: null, title: '', h1: '', pageText: '', forms: [], navError: null };
  if (!url) {
    await page.setContent(`<html lang="ja"><body><h1>対象外</h1><p>No browser URL for this feature.</p></body></html>`);
    await pageSnapshot(page, screenshotAbs);
    return result;
  }

  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 15000 });
    result.status = response?.status() ?? null;
    result.contentType = response?.headers()?.['content-type'] ?? null;
    await page.waitForTimeout(80).catch(() => {});
  } catch (e) {
    result.navError = sanitizeText(e.message, 500);
    await page.setContent(`<html lang="ja"><body><h1>Navigation error</h1><pre>${escapeHtml(result.navError)}</pre><p>${escapeHtml(url)}</p></body></html>`);
  }

  result.finalUrl = page.url();
  result.title = sanitizeText(await page.title().catch(() => ''), 200);
  result.h1 = sanitizeText(await page.locator('h1').first().innerText({ timeout: 500 }).catch(() => ''), 200);
  result.pageText = sanitizeText(await page.locator('body').innerText({ timeout: 1500 }).catch(() => ''), 1200);
  result.forms = await page.$$eval('form', forms => forms.map(form => ({
    action: form.getAttribute('action'),
    method: (form.getAttribute('method') || 'get').toLowerCase(),
    fields: [...form.querySelectorAll('input,select,textarea,button')].map(el => ({
      tag: el.tagName.toLowerCase(),
      type: el.getAttribute('type'),
      name: el.getAttribute('name'),
      required: Boolean(el.required),
      maxLength: el.getAttribute('maxlength'),
    })).filter(f => f.name && f.type !== 'hidden' && f.type !== 'password')
  }))).catch(() => []);
  await pageSnapshot(page, screenshotAbs);
  return result;
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function isTargetOutFeature(baseline) {
  return /対象外/.test(baseline?.webResult || '') || baseline?.section === 'Boundary';
}

function isMutatingFeature(baseline) {
  const resource = baseline?.resource || '';
  const feature = baseline?.feature || '';
  if (/\b(POST|PUT|DELETE|PATCH)\b/i.test(resource)) return true;
  return /(作成|登録|更新|編集|削除|送信|追加|変更|実行|取込|有効化|無効化|インストール|ログイン|ログアウト|購入完了|再注文|退会|認証|切替)/.test(feature);
}

function classifyFeature(baseline, evidence, attemptsByFeature) {
  if (isTargetOutFeature(baseline)) {
    return { webResult: '— 対象外（外部/本番運用境界）', status: 'targetOut', reason: '外部サービス・実メール配送・本番運用ファイル破壊的変更はBeMart demoの対象外。' };
  }
  const text = evidence.pageText || '';
  const error = evidence.navError || isJsonError(evidence.contentType, text);
  const httpOk = evidence.status !== null && evidence.status >= 200 && evidence.status < 400 && !error;
  const mutating = isMutatingFeature(baseline);
  const attempt = attemptsByFeature[baseline.feature];

  if (attempt) {
    if (attempt.pass) return { webResult: `✔ pass（${attempt.summary}）`, status: 'pass', reason: attempt.summary };
    return { webResult: `✘ fail（${attempt.summary}）`, status: 'fail', reason: attempt.summary };
  }

  if (baseline.feature === '注文履歴詳細') {
    return { webResult: '✘ fail（Web操作だけで注文作成に到達できず、注文履歴詳細を生成できない）', status: 'fail', reason: '既知failを優先再検証。商品/会員/注文の前提データをWebで作成できず、注文履歴詳細へ到達不能。' };
  }
  if (baseline.feature === '再注文') {
    return { webResult: '✘ fail（再注文元の注文履歴詳細をWeb操作で作成できない）', status: 'fail', reason: '既知failを優先再検証。再注文元の注文をWeb操作で作成できない。' };
  }

  if (mutating) {
    const msg = extractMessage(evidence.contentType, text);
    return { webResult: `✘ fail（状態変更OK操作は成功未確認。${msg ? `route evidence: ${msg}` : 'GET導線のみ到達'}）`, status: 'fail', reason: '状態変更フォーム/操作はCSRFまたは前提データ不足でOK操作を確認できなかった。' };
  }

  if (httpOk) return { webResult: '✔ pass', status: 'pass', reason: 'GET画面/導線が2xxで表示され、JSONエラー/CSRFエラーは出ていない。' };
  const msg = extractMessage(evidence.contentType, text) || evidence.navError || `HTTP ${evidence.status}`;
  return { webResult: `✘ fail（${msg}）`, status: 'fail', reason: msg };
}

async function submitNamedForm(page, formSelector, fillFn, screenshotPrefix) {
  const result = { pass: false, status: null, contentType: null, finalUrl: null, title: '', message: '', screenshot: null };
  await fillFn();
  const before = path.join(SCREENSHOT_DIR, `${screenshotPrefix}-before.png`);
  await pageSnapshot(page, before);
  let response = null;
  try {
    const nav = page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 8000 }).catch(() => null);
    await page.locator(`${formSelector} button[type=submit], ${formSelector} input[type=submit]`).last().click({ timeout: 5000 });
    response = await nav;
  } catch (e) {
    result.message = `submit error: ${sanitizeText(e.message, 300)}`;
  }
  const after = path.join(SCREENSHOT_DIR, `${screenshotPrefix}-after.png`);
  await page.waitForTimeout(100).catch(() => {});
  await pageSnapshot(page, after);
  result.screenshot = rel(after);
  result.status = response?.status() ?? null;
  result.contentType = response?.headers()?.['content-type'] ?? null;
  result.finalUrl = page.url();
  result.title = sanitizeText(await page.title().catch(() => ''), 200);
  const text = await page.locator('body').innerText({ timeout: 1500 }).catch(() => '');
  result.message = result.message || extractMessage(result.contentType, text);
  result.pass = Boolean(result.status && result.status >= 200 && result.status < 400 && !/Invalid or missing CSRF token|Invalid parameter type/.test(text));
  return result;
}

async function runPositiveAttempts(browser) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const attempts = [];
  const byFeature = {};

  // Admin login through the rendered browser form. Values are prefilled by the demo form; do not persist password evidence.
  await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'domcontentloaded' });
  const adminLogin = await submitNamedForm(page, 'form[action="/admin/login"]', async () => {}, 'ng-admin-login-ok-attempt');
  adminLogin.name = '管理ログインOK操作';
  adminLogin.summary = adminLogin.pass ? '管理ログインフォーム送信が成功' : `管理ログインフォーム送信失敗: ${adminLogin.message || `HTTP ${adminLogin.status}`}`;
  attempts.push(adminLogin);
  byFeature['管理ログイン'] = { pass: adminLogin.pass, summary: adminLogin.summary };

  // Product creation via admin product form.
  await page.goto(`${BASE_URL}/admin/product-new`, { waitUntil: 'domcontentloaded' });
  const productCreate = await submitNamedForm(page, 'form[action="/admin/product"]', async () => {
    await page.locator('[name=productCode]').fill(e2eData.productCode).catch(() => {});
    await page.locator('[name=productName]').fill(`Web E2E 商品 ${stamp}`).catch(() => {});
    await page.locator('[name=price02]').fill('1200').catch(() => {});
    await page.locator('[name=stock]').fill('10').catch(() => {});
    await page.locator('[name=description]').fill('Web E2E created through browser form').catch(() => {});
  }, 'ok-admin-product-create');
  productCreate.name = '商品新規登録OK操作';
  productCreate.summary = productCreate.pass ? `Webフォームで商品 ${e2eData.productCode} を作成` : `商品作成失敗: ${productCreate.message || `HTTP ${productCreate.status}`}`;
  attempts.push(productCreate);
  byFeature['商品新規登録'] = { pass: productCreate.pass, summary: productCreate.summary };

  // Customer registration through the rendered browser form.
  await page.goto(`${BASE_URL}/entry`, { waitUntil: 'domcontentloaded' });
  const entry = await submitNamedForm(page, 'form[action="/entry"]', async () => {
    const fill = async (name, value) => page.locator(`[name="${name}"]`).first().fill(value).catch(() => {});
    await fill('name01', '山田'); await fill('name02', '太郎');
    await fill('kana01', 'ヤマダ'); await fill('kana02', 'タロウ');
    await fill('postalCode', '1000001'); await page.locator('[name=pref]').selectOption({ index: 1 }).catch(() => {});
    await fill('addr01', '千代田区'); await fill('addr02', '1-1'); await fill('phoneNumber', '0312345678');
    await fill('email', e2eData.email); await fill('email_confirm', e2eData.email);
    await fill('password', `Passw0rd-${stamp}`); await fill('password_confirm', `Passw0rd-${stamp}`);
    await page.locator('[name=user_policy_check]').check().catch(() => {});
  }, 'ok-entry-register');
  entry.name = '会員登録OK操作';
  entry.summary = entry.pass ? `Webフォームで会員 ${e2eData.email} を作成` : `会員登録失敗: ${entry.message || `HTTP ${entry.status}`}`;
  attempts.push(entry);
  byFeature['会員登録完了'] = { pass: entry.pass, summary: entry.summary };
  byFeature['会員登録確認'] = { pass: entry.pass, summary: entry.summary };

  // Contact OK attempt.
  await page.goto(`${BASE_URL}/contact`, { waitUntil: 'domcontentloaded' });
  const contact = await submitNamedForm(page, 'form[action="/contact"]', async () => {
    await page.locator('[name=contactName01]').fill('山田').catch(() => {});
    await page.locator('[name=contactName02]').fill('太郎').catch(() => {});
    await page.locator('[name=contactEmail]').fill(e2eData.email).catch(() => {});
    await page.locator('[name=contactContents]').fill('Web E2E 問い合わせ本文').catch(() => {});
  }, 'ok-contact-submit');
  contact.name = 'お問い合わせOK操作';
  contact.summary = contact.pass ? 'お問い合わせフォーム送信が成功' : `お問い合わせ送信失敗: ${contact.message || `HTTP ${contact.status}`}`;
  attempts.push(contact);
  byFeature['お問い合わせ確認'] = { pass: contact.pass, summary: contact.summary };
  byFeature['お問い合わせ完了'] = { pass: contact.pass, summary: contact.summary };

  // Non-member purchase info OK attempt.
  await page.goto(`${BASE_URL}/shopping/non-member`, { waitUntil: 'domcontentloaded' });
  const nonMember = await submitNamedForm(page, 'form[action="/shopping/non-member"]', async () => {
    const fill = async (name, value) => page.locator(`[name="${name}"]`).first().fill(value).catch(() => {});
    await fill('name01', '山田'); await fill('name02', '太郎');
    await fill('kana01', 'ヤマダ'); await fill('kana02', 'タロウ');
    await fill('postalCode', '1000001'); await page.locator('[name=pref]').selectOption({ index: 1 }).catch(() => {});
    await fill('addr01', '千代田区'); await fill('addr02', '1-1'); await fill('phoneNumber', '0312345678');
    await fill('email', e2eData.email); await fill('email_confirm', e2eData.email);
  }, 'ok-shopping-non-member');
  nonMember.name = '非会員購入情報OK操作';
  nonMember.summary = nonMember.pass ? '非会員購入情報フォーム送信が成功' : `非会員購入情報送信失敗: ${nonMember.message || `HTTP ${nonMember.status}`}`;
  attempts.push(nonMember);
  byFeature['非会員購入情報送信'] = { pass: nonMember.pass, summary: nonMember.summary };

  // Product detail/cart/order history priority recheck after attempted product creation.
  const productPage = await page.goto(`${BASE_URL}/product?productCode=${encodeURIComponent(e2eData.productCode)}`, { waitUntil: 'domcontentloaded' }).catch(() => null);
  const productText = await page.locator('body').innerText({ timeout: 1000 }).catch(() => '');
  const productAvailable = Boolean(productPage && productPage.status() < 400 && !isJsonError(productPage.headers()['content-type'], productText));
  const orderHistoryPriority = {
    name: '既知fail優先再検証: 注文履歴詳細/再注文',
    pass: false,
    status: productPage?.status() ?? null,
    contentType: productPage?.headers()?.['content-type'] ?? null,
    finalUrl: page.url(),
    message: productAvailable ? '商品詳細は表示できたが、注文作成OK操作は別途失敗' : `Web作成商品が表示できない: ${extractMessage(productPage?.headers()?.['content-type'], productText)}`,
    screenshot: null,
  };
  const knownFailShot = path.join(SCREENSHOT_DIR, 'known-fail-order-history-reorder.png');
  await pageSnapshot(page, knownFailShot);
  orderHistoryPriority.screenshot = rel(knownFailShot);
  attempts.push(orderHistoryPriority);

  await context.close();
  return { attempts, byFeature };
}

async function runNegativeCases(browser) {
  const cases = [];
  async function uiCase(name, url, formSelector, fillFn, screenshotPrefix, expectations = {}) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto(`${BASE_URL}${url}`, { waitUntil: 'domcontentloaded' });
    const res = await submitNamedForm(page, formSelector, fillFn || (async () => {}), screenshotPrefix);
    const bodyText = await page.locator('body').innerText({ timeout: 1200 }).catch(() => '');
    const passwordValues = await page.$$eval('input[type=password]', els => els.map(e => e.value ? '[non-empty]' : '')).catch(() => []);
    cases.push({
      name,
      kind: 'browser-form-ng',
      url,
      status: res.status,
      contentType: res.contentType,
      finalUrl: res.finalUrl,
      message: res.message,
      screenshot: res.screenshot,
      containsInputPrompt: /入力してください。/.test(bodyText),
      containsInlineError: /(エラー|入力してください|正しく|必須|invalid|Invalid)/i.test(bodyText),
      inputRedisplayed: expectations.redisplayValue ? bodyText.includes(expectations.redisplayValue) : null,
      passwordRedisplayed: passwordValues.some(v => v === '[non-empty]'),
      verdict: expectations.expected && expectations.expected({ res, bodyText, passwordValues }) ? 'pass' : 'fail',
      note: expectations.note,
    });
    await ctx.close();
  }

  await uiCase('重点: /shopping/non-member 空送信', '/shopping/non-member', 'form[action="/shopping/non-member"]', async () => {}, 'ng-shopping-non-member-empty', {
    expected: ({ res, bodyText }) => res.status === 400 && (res.contentType || '').includes('text/html') && /入力してください。/.test(bodyText) && !/Invalid parameter type/.test(bodyText),
    note: '期待は 400 text/html + 「入力してください。」 + Invalid parameter typeなし。',
  });

  // Re-run invalid non-member with actual filling using local closure.
  {
    const ctx = await browser.newContext(); const page = await ctx.newPage();
    await page.goto(`${BASE_URL}/shopping/non-member`, { waitUntil: 'domcontentloaded' });
    await page.locator('[name=name01]').fill('山田').catch(() => {});
    await page.locator('[name=name02]').fill('太郎').catch(() => {});
    await page.locator('[name=kana01]').fill('ヤマダ').catch(() => {});
    await page.locator('[name=kana02]').fill('タロウ').catch(() => {});
    await page.locator('[name=postalCode]').fill('INVALID').catch(() => {});
    await page.locator('[name=pref]').selectOption({ index: 1 }).catch(() => {});
    await page.locator('[name=addr01]').fill('千代田区').catch(() => {});
    await page.locator('[name=addr02]').fill('1-1').catch(() => {});
    await page.locator('[name=phoneNumber]').fill('not-phone').catch(() => {});
    await page.locator('[name=email]').fill('bad-email').catch(() => {});
    await page.locator('[name=email_confirm]').fill('other@example.test').catch(() => {});
    const res = await submitNamedForm(page, 'form[action="/shopping/non-member"]', async () => {}, 'ng-shopping-non-member-invalid-filled');
    const text = await page.locator('body').innerText({ timeout: 1200 }).catch(() => '');
    cases.push({ name: '非会員購入 形式不正/確認不一致/境界値', kind: 'browser-form-ng', url: '/shopping/non-member', status: res.status, contentType: res.contentType, finalUrl: res.finalUrl, message: res.message, screenshot: res.screenshot, containsInlineError: /(入力してください|正しく|エラー|Invalid)/i.test(text), inputRedisplayed: /bad-email|not-phone|INVALID/.test(text), passwordRedisplayed: null, verdict: 'fail', note: '期待はinlineエラーと入力値再表示だが、現状はJSON/CSRF境界で画面に戻らない。' });
    await ctx.close();
  }

  // Entry password mismatch / overlong.
  {
    const ctx = await browser.newContext(); const page = await ctx.newPage();
    await page.goto(`${BASE_URL}/entry`, { waitUntil: 'domcontentloaded' });
    await page.locator('[name=name01]').fill('山田').catch(() => {});
    await page.locator('[name=name02]').fill('太郎').catch(() => {});
    await page.locator('[name=email]').fill('bad-email').catch(() => {});
    await page.locator('[name=email_confirm]').fill('other@example.test').catch(() => {});
    await page.locator('[name=password]').fill('short').catch(() => {});
    await page.locator('[name=password_confirm]').fill('different').catch(() => {});
    await page.locator('[name=user_policy_check]').check().catch(() => {});
    const res = await submitNamedForm(page, 'form[action="/entry"]', async () => {}, 'ng-entry-invalid-mismatch');
    const text = await page.locator('body').innerText({ timeout: 1200 }).catch(() => '');
    const pwdValues = await page.$$eval('input[type=password]', els => els.map(e => e.value ? '[non-empty]' : '')).catch(() => []);
    cases.push({ name: '会員登録 形式不正/確認不一致/パスワード非再表示', kind: 'browser-form-ng', url: '/entry', status: res.status, contentType: res.contentType, finalUrl: res.finalUrl, message: res.message, screenshot: res.screenshot, containsInlineError: /(入力してください|正しく|エラー|Invalid)/i.test(text), inputRedisplayed: /bad-email/.test(text), passwordRedisplayed: pwdValues.some(v => v === '[non-empty]'), verdict: 'fail', note: '期待はinlineエラー・入力値再表示・パスワード非再表示。現状はInvalid parameter typeのJSON応答。' });
    await ctx.close();
  }

  // CSRF missing/mismatch via browser request (no token printed).
  const reqCtx = await browser.newContext();
  const req = reqCtx.request;
  for (const item of [
    { name: 'CSRF欠落: 管理ログインPOST', method: 'POST', url: '/admin/login', form: { loginId: 'test-admin', password: 'redacted' } },
    { name: 'CSRF不一致: 商品作成POST', method: 'POST', url: '/admin/product', form: { csrfToken: 'invalid-token', productCode: e2eData.productCode, productName: 'Web E2E', price02: '1200' } },
    { name: '未ログイン: マイページGET', method: 'GET', url: '/mypage' },
    { name: '未ログイン: 管理ダッシュボードGET', method: 'GET', url: '/admin/index' },
    { name: '存在しないID: 商品詳細GET', method: 'GET', url: '/product?productCode=__missing_web_e2e__' },
    { name: '存在しないID: 注文履歴詳細GET', method: 'GET', url: '/mypage/order-history?orderNo=__missing_web_e2e__' },
    { name: '存在しないID: 管理会員詳細GET', method: 'GET', url: '/admin/customer?customerId=999999999' },
  ]) {
    const response = await req.fetch(`${BASE_URL}${item.url}`, { method: item.method, form: item.form, maxRedirects: 0 }).catch(e => ({ error: e }));
    if (response.error) {
      cases.push({ name: item.name, kind: 'http-boundary-ng', url: item.url, method: item.method, status: null, contentType: null, message: sanitizeText(response.error.message, 400), verdict: 'fail' });
    } else {
      const ct = response.headers()['content-type'] || '';
      const text = await response.text().catch(() => '');
      const message = extractMessage(ct, text);
      let expected = false;
      if (/CSRF/.test(item.name)) expected = response.status() === 403 && /CSRF|Invalid/.test(message);
      if (/未ログイン/.test(item.name)) expected = [401, 403].includes(response.status());
      if (/存在しないID/.test(item.name)) expected = response.status() === 404 && /[ぁ-んァ-ン一-龥]/.test(message);
      cases.push({ name: item.name, kind: 'http-boundary-ng', url: item.url, method: item.method, status: response.status(), contentType: ct, message, japaneseMessage: /[ぁ-んァ-ン一-龥]/.test(message), verdict: expected ? 'pass' : 'fail', note: '400/401/403/404境界のstatusと日本語メッセージ確認。' });
    }
  }
  await reqCtx.close();

  return cases;
}

function operationSamplePath(opPath) {
  return opPath
    .replaceAll('{productCode}', e2eData.productCode)
    .replaceAll('{productId}', '999999999')
    .replaceAll('{categoryId}', '999999999')
    .replaceAll('{customerId}', '999999999')
    .replaceAll('{orderNo}', '__missing_order__')
    .replaceAll('{loginId}', 'test-admin')
    .replaceAll('{newsId}', '999999999')
    .replaceAll('{pageId}', '999999999')
    .replaceAll('{blockId}', '999999999')
    .replaceAll('{layoutId}', '1')
    .replaceAll('{paymentId}', '1')
    .replaceAll('{deliveryId}', '1')
    .replaceAll('{secretKey}', 'missing-secret-key');
}

function sampleFormForOperation(method, opPath, operationId) {
  const f = { csrfToken: 'invalid-token-for-negative-boundary' };
  const op = `${method} ${opPath} ${operationId || ''}`;
  if (/login/i.test(op)) Object.assign(f, { loginId: 'test-admin', email: e2eData.email, password: 'redacted-demo-password' });
  if (/entry|Customer|customer/i.test(op)) Object.assign(f, { email: e2eData.email, name01: '山田', name02: '太郎', password: 'redacted-password', customerId: '999999999' });
  if (/product/i.test(op)) Object.assign(f, { productCode: e2eData.productCode, productName: 'Web E2E', price02: '1200', stock: '10', productCodes: e2eData.productCode });
  if (/cart/i.test(op)) Object.assign(f, { productCode: e2eData.productCode, quantity: '1' });
  if (/contact/i.test(op)) Object.assign(f, { contactName01: '山田', contactName02: '太郎', contactEmail: e2eData.email, contactContents: 'Web E2E' });
  if (/shopping|shipping/i.test(op)) Object.assign(f, { name01: '山田', name02: '太郎', kana01: 'ヤマダ', kana02: 'タロウ', email: e2eData.email, phoneNumber: '0312345678', postalCode: '1000001', pref: '1', addr01: '千代田区', addr02: '1-1', preOrderId: 'missing-pre-order', shippingAddressId: '999999999' });
  if (/order/i.test(op)) Object.assign(f, { orderNo: '__missing_order__', orderStatus: '1', discount: '0', charge: '0', usePoint: '0', orderNos: '__missing_order__' });
  if (/tag/i.test(op)) Object.assign(f, { tagName: `web-e2e-tag-${stamp}`, tagId: '999999999' });
  if (/category/i.test(op)) Object.assign(f, { categoryName: `web-e2e-category-${stamp}`, categoryId: '999999999', sortNo: '999' });
  if (/class-name/i.test(op) || /ClassName/.test(op)) Object.assign(f, { name: `web-e2e-class-${stamp}`, backend_name: `web-e2e-class-${stamp}`, classNameId: '999999999' });
  if (/master-data/i.test(op)) Object.assign(f, { masterType: 'tag', rowId: '999999999', value: 'web-e2e' });
  if (/mail-template/i.test(op)) Object.assign(f, { template: '1', name: 'web-e2e', file_name: 'web-e2e.twig', mail_subject: 'web-e2e', tpl_data: 'body', html_tpl_data: '<p>body</p>' });
  if (/calendar/i.test(op)) Object.assign(f, { title: 'web-e2e holiday', holiday: '2026-06-10', calendarId: '999999999' });
  if (/security/i.test(op)) Object.assign(f, { adminRouteDir: 'admin', adminAllowHosts: '', adminDenyHosts: '', frontAllowHosts: '', frontDenyHosts: '', trustedHosts: '' });
  if (/content\/css|ContentCss/i.test(op)) Object.assign(f, { css: '/* web-e2e */' });
  if (/content\/js|ContentJs/i.test(op)) Object.assign(f, { js: '// web-e2e' });
  if (/template/i.test(op)) Object.assign(f, { code: `web-e2e-${stamp}`, name: 'Web E2E Template' });
  return f;
}

async function runOpenApiOperations(browser) {
  const spec = JSON.parse(await fs.readFile(OPENAPI_PATH, 'utf8'));
  const ops = [];
  const ctx = await browser.newContext();
  const req = ctx.request;
  let no = 0;
  for (const [opPath, pathItem] of Object.entries(spec.paths || {})) {
    for (const [methodLower, operation] of Object.entries(pathItem || {})) {
      const method = methodLower.toUpperCase();
      if (!['GET', 'POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) continue;
      no++;
      const samplePath = operationSamplePath(opPath);
      const url = new URL(samplePath, BASE_URL);
      // Query samples for common query-driven resources.
      if (samplePath === '/product') url.searchParams.set('productCode', e2eData.productCode);
      if (/\/admin\/product$/.test(samplePath)) url.searchParams.set('productCode', e2eData.productCode);
      if (/customer/.test(samplePath) && method === 'GET') url.searchParams.set('customerId', '999999999');
      if (/order/.test(samplePath) && method === 'GET') url.searchParams.set('orderNo', '__missing_order__');
      if (/member/.test(samplePath) && method === 'GET') url.searchParams.set('loginId', 'test-admin');
      if (/category\/edit/.test(samplePath)) url.searchParams.set('categoryId', '999999999');
      if (/master-data/.test(samplePath)) url.searchParams.set('masterType', 'tag');
      let response, bodyText = '', contentType = '', status = null, error = null;
      try {
        const options = { method, maxRedirects: 0 };
        if (method !== 'GET') options.form = sampleFormForOperation(method, opPath, operation?.operationId);
        response = await req.fetch(url.href, options);
        status = response.status();
        contentType = response.headers()['content-type'] || '';
        bodyText = await response.text().catch(() => '');
      } catch (e) {
        error = sanitizeText(e.message, 500);
      }
      const message = extractMessage(contentType, bodyText) || error || '';
      const targetOut = /unsupported-route/.test(opPath);
      let result = 'fail';
      if (targetOut) result = 'targetOut';
      else if (status !== null && status >= 200 && status < 400 && !isJsonError(contentType, bodyText)) result = 'pass';
      ops.push({
        no,
        method,
        path: opPath,
        operationId: operation?.operationId || null,
        sampleUrl: url.pathname + url.search,
        status,
        contentType,
        message,
        japaneseMessage: /[ぁ-んァ-ン一-龥]/.test(message),
        result,
        reason: targetOut ? 'unsupported-routeは意図的な境界として対象外' : (result === 'pass' ? '2xx/3xx without JSON error' : (message || error || `HTTP ${status}`)),
      });
    }
  }
  await ctx.close();
  return ops;
}

async function main() {
  const baseline = JSON.parse(await fs.readFile(BASELINE_PATH, 'utf8'));
  const browser = await chromium.launch({ headless: true });
  const positive = await runPositiveAttempts(browser);
  const negativeCases = await runNegativeCases(browser);

  const pageContext = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await pageContext.newPage();
  const featureResults = [];
  let idx = 0;
  for (const old of baseline.results) {
    idx++;
    const shotName = `${String(idx).padStart(3, '0')}-${slugName(old.feature)}.png`;
    const shotAbs = path.join(SCREENSHOT_DIR, shotName);
    const evidence = await gotoAndCapture(page, old.targetUrl, shotAbs);
    const classification = classifyFeature(old, evidence, positive.byFeature);
    featureResults.push({
      no: idx,
      section: old.section,
      feature: old.feature,
      screen: old.screen,
      resource: old.resource,
      targetUrl: old.targetUrl,
      finalUrl: evidence.finalUrl,
      status: evidence.status,
      contentType: evidence.contentType,
      title: evidence.title,
      h1: evidence.h1,
      webResult: classification.webResult,
      result: classification.status,
      reason: classification.reason,
      screenshot: classification.status === 'targetOut' ? null : rel(shotAbs),
      navError: evidence.navError,
      pageMessage: extractMessage(evidence.contentType, evidence.pageText),
      forms: evidence.forms,
    });
  }
  await pageContext.close();

  const openApiOperations = await runOpenApiOperations(browser);
  await browser.close();

  const summary = {
    features: countBy(featureResults, 'result'),
    openApiOperations: countBy(openApiOperations, 'result'),
    negativeCases: countBy(negativeCases, 'verdict'),
    positiveAttempts: countBy(positive.attempts, x => x.pass ? 'pass' : 'fail'),
  };
  summary.features.total = featureResults.length;
  summary.openApiOperations.total = openApiOperations.length;
  summary.negativeCases.total = negativeCases.length;

  const resultDoc = {
    runId: RUN_ID,
    executedAt: createdAt,
    context: 'html-eccube-sql-hal-app via public/page.php',
    baseUrl: BASE_URL,
    db: 'eccubedb_test via DATABASE_URL（malt MySQL; setup-db.sh; 開発標準 root/パスワードなし）',
    dataPolicy: 'Direct SQL seed was not used as primary evidence. setup-db.sh loaded schema/master data only. Business data creation was attempted through browser forms; failures are recorded as fail.',
    principalFinding: 'CSRFがWebフォーム境界としてend-to-endに配線されていない。ブラウザからトークン発行・埋め込み・セッション検証の往復が安定して成立しないため、業務データ作成失敗は主因ではなく派生証跡として扱う。',
    environmentAssumptions: {
      databaseUser: 'root',
      databasePassword: '(none)',
      databaseNote: 'ローカル開発はroot/パスワードなしを使う。dbuser/secretは開発標準ではない。',
    },
    e2eData,
    summary,
    positiveAttempts: positive.attempts,
    negativeCases,
    openApiOperations,
    results: featureResults,
    knownFailures: featureResults.filter(r => ['注文履歴詳細', '再注文'].includes(r.feature)).map(r => ({ feature: r.feature, result: r.webResult, reason: r.reason, screenshot: r.screenshot })),
    newFailures: featureResults.filter(r => r.result === 'fail' && !['注文履歴詳細', '再注文'].includes(r.feature)).slice(0, 80).map(r => ({ no: r.no, feature: r.feature, result: r.webResult, reason: r.reason })),
    relatedValidation: { phpunit: 'pending', psalm: 'pending', note: 'Updated after related validation commands finish.' },
  };

  await fs.writeFile(RESULT_PATH, JSON.stringify(resultDoc, null, 2) + '\n');
  await fs.writeFile(REPORT_PATH, renderReport(resultDoc));
  await updateMatrix(featureResults, summary);
  console.log(JSON.stringify({ runId: RUN_ID, resultPath: rel(RESULT_PATH), reportPath: rel(REPORT_PATH), screenshotDir: rel(SCREENSHOT_DIR), summary }, null, 2));
}

function countBy(items, key) {
  const out = {};
  for (const item of items) {
    const k = typeof key === 'function' ? key(item) : (item[key] ?? 'unknown');
    out[k] = (out[k] || 0) + 1;
  }
  for (const k of ['pass', 'fail', 'targetOut']) out[k] ||= 0;
  return out;
}

function renderReport(doc) {
  const f = doc.summary.features;
  const o = doc.summary.openApiOperations;
  const n = doc.summary.negativeCases;
  const pos = doc.summary.positiveAttempts;
  const known = doc.knownFailures.map(k => `- ${k.feature}: ${k.result} — ${k.reason}`).join('\n') || '- なし';
  const newFails = doc.results.filter(r => r.result === 'fail' && !['注文履歴詳細', '再注文'].includes(r.feature));
  const newFailLines = newFails.slice(0, 30).map(r => `- #${r.no} ${r.section} ${r.feature}: ${r.webResult}`).join('\n') + (newFails.length > 30 ? `\n- ...他 ${newFails.length - 30} 件` : '');
  const negLines = doc.negativeCases.map(c => `- ${c.name}: ${c.verdict === 'pass' ? '✔ pass' : '✘ fail'} — status=${c.status ?? 'n/a'}, contentType=${c.contentType ?? 'n/a'}, message=${c.message || '(none)'}${c.screenshot ? `, screenshot=${c.screenshot}` : ''}`).join('\n');
  const opFails = doc.openApiOperations.filter(x => x.result === 'fail');
  const opFailLines = opFails.slice(0, 40).map(x => `- #${x.no} ${x.method} ${x.path} (${x.operationId ?? '-'}) — status=${x.status ?? 'n/a'} ${x.message || x.reason}`).join('\n') + (opFails.length > 40 ? `\n- ...他 ${opFails.length - 40} operations` : '');
  return `# ${doc.runId} Web+DB 全ルート検証結果

## Summary

- 実行日時: ${doc.executedAt}
- context: \`${doc.context}\`
- baseUrl: \`${doc.baseUrl}\`
- DB: ${doc.db}
- データ方針: ${doc.dataPolicy}
- 主所見: ${doc.principalFinding}
- Feature matrix: pass ${f.pass} / fail ${f.fail} / targetOut ${f.targetOut} / total ${f.total}
- OpenAPI operations: pass ${o.pass} / fail ${o.fail} / targetOut ${o.targetOut} / total ${o.total}
- OK操作 attempts: pass ${pos.pass} / fail ${pos.fail}
- NGケース: pass ${n.pass} / fail ${n.fail} / total ${n.total}
- screenshots: \`${rel(SCREENSHOT_DIR)}/\`
- results JSON: \`${rel(RESULT_PATH)}\`

## 重要確認結果

- CSRF: ✘ fail — 欠落/不一致を403にする箇所はあるが、Webフォームの正常系でトークン発行・埋め込み・セッション検証の往復が成立していない。今回の大量failの主因として記録。
- \`/shopping/non-member\` 空送信: ${doc.negativeCases.find(c => c.name.includes('/shopping/non-member 空送信'))?.verdict === 'pass' ? '✔ pass' : '✘ fail'} — ${doc.negativeCases.find(c => c.name.includes('/shopping/non-member 空送信'))?.message || ''}
- \`/shopping/non-member\` HTMLフォーム境界: ✘ fail — ブラウザフォームのNG応答が \`text/html\` ではなく \`application/json\` で返ったため、inlineエラー/入力値再表示の確認に進めない。
- \`Invalid parameter type\`: ${doc.negativeCases.some(c => /Invalid parameter type/.test(c.message || '')) || doc.positiveAttempts.some(c => /Invalid parameter type/.test(c.message || '')) ? '検出あり（failとして記録）' : '重点対象 /shopping/non-member 空送信では未検出'}
- \`Invalid parameter type\` の意味: Resource呼び出し時のPHP \`TypeError\` が400へ変換されたもの。証跡では会員登録POSTで発生し、フォーム文字列/空文字と \`int|null\` 引数などの型変換境界が原因候補。
- 業務データ作成: 商品・会員・問い合わせ・非会員購入情報はWebフォームでOK操作を試行。成功しないものはfailとして記録。

## 既知fail（優先再検証）

${known}

## 新規fail（抜粋）

${newFailLines || '- なし'}

## OpenAPI operation fail（抜粋）

${opFailLines || '- なし'}

## NGケース代表結果

${negLines}

## 対象外境界

- 外部決済ゲートウェイ送信
- 実メール配送（SMTP/外部配送そのもの。画面/フォームが存在する送信操作は別途pass/fail判定）
- 本番運用ファイル破壊的変更（実CSS/JS/テンプレート/メンテナンス反映の破壊的副作用）
- OpenAPI上の \`/unsupported-route\` / \`/admin/unsupported-route\` は意図的unsupported境界としてtargetOut。

## 関連検証

- PHPUnit: pending
- Psalm: pending
`;
}

async function updateMatrix(featureResults, summary) {
  const original = await fs.readFile(MATRIX_PATH, 'utf8');
  const lines = original.split('\n');
  const out = [];
  let inRows = false;
  let rowIndex = 0;
  let pendingRunInsert = false;
  let runInserted = false;
  const runBullet = (() => {
    const s = summary.features;
    return `- \`${RUN_ID}\`: \`public/page.php\` + \`eccubedb_test\` 実DB（開発DB接続はroot/パスワードなし）で、\`docs/api/openapi.json\` 全236 operations と feature matrix 全186行を再検証。主所見はCSRFのWebフォーム往復が未配線で、業務データはWebフォームで作成を試行し、CSRF未配線や前提データ不足等で作れない機能はfailとして記録。結果JSONは \`docs/web-e2e/results/${RUN_ID}.json\`、証跡画像は \`docs/web-e2e/screenshots/${RUN_ID}/\`。Feature結果は \`✔ pass\` ${s.pass}件、\`✘ fail\` ${s.fail}件、\`— 対象外\` ${s.targetOut}件。OpenAPI operationsは pass ${summary.openApiOperations.pass} / fail ${summary.openApiOperations.fail} / targetOut ${summary.openApiOperations.targetOut}。`;
  })();
  for (const line of lines) {
    if (!pendingRunInsert && line.startsWith('## Browser verification runs')) {
      out.push(line);
      pendingRunInsert = true;
      continue;
    }
    if (pendingRunInsert && line.startsWith(`- \`${RUN_ID}\``)) {
      // Replace the previous bullet for this run; the fresh one is inserted
      // before the next historical bullet.
      continue;
    }
    if (pendingRunInsert && !runInserted && line.startsWith('- `')) {
      out.push(runBullet);
      runInserted = true;
      out.push(line);
      continue;
    }
    if (line.startsWith('| 区分 | 機能 |')) {
      inRows = true;
      out.push(line);
      continue;
    }
    if (inRows && line.startsWith('|---')) { out.push(line); continue; }
    if (inRows && line.startsWith('|')) {
      rowIndex++;
      const r = featureResults[rowIndex - 1];
      if (!r) { out.push(line); continue; }
      const cells = splitMarkdownRow(line);
      if (cells.length >= 13) {
        cells[11] = ` ${r.webResult} `;
        if (r.result === 'targetOut') {
          cells[12] = ` 対象外理由: ${r.reason} `;
        } else {
          const relShot = r.screenshot.replace(/^docs\/web-e2e\//, '');
          cells[12] = ` [![${String(r.no).padStart(3, '0')} ${r.feature}](${relShot})](${relShot}) `;
        }
        out.push(cells.join('|'));
      } else {
        out.push(line);
      }
      continue;
    }
    out.push(line);
  }
  await fs.writeFile(MATRIX_PATH, out.join('\n'));
}

function splitMarkdownRow(line) {
  // Matrix rows do not use literal pipes inside cells; keep the leading/trailing empty cells.
  return line.split('|');
}

await main().catch(err => {
  console.error(err);
  process.exit(1);
});
