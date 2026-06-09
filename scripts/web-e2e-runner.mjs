#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const METHODS = new Set(['get', 'post', 'put', 'patch', 'delete', 'head', 'options']);
const DEFAULT_BASELINE = 'docs/web-e2e/results/20260608-canonical-resource-routes-web-e2e.json';
const DEFAULT_MATRIX = 'docs/web-e2e/feature-implementation-matrix.md';
const DEFAULT_OPENAPI = 'docs/api/openapi.json';
const NAV_TIMEOUT_MS = 15000;

function parseArgs(argv) {
  const args = {};
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (!arg.startsWith('--')) {
      continue;
    }

    const raw = arg.slice(2);
    if (raw.includes('=')) {
      const [key, ...rest] = raw.split('=');
      args[key] = rest.join('=');
      continue;
    }

    const next = argv[i + 1];
    if (next !== undefined && !next.startsWith('--')) {
      args[raw] = next;
      i += 1;
    } else {
      args[raw] = true;
    }
  }

  return args;
}

function jstDate() {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Tokyo',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date());

  const lookup = Object.fromEntries(parts.map((part) => [part.type, part.value]));
  return `${lookup.year}${lookup.month}${lookup.day}`;
}

function nowIsoJst() {
  const now = new Date();
  const parts = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Tokyo',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).format(now).replace(' ', 'T');

  return `${parts}+09:00`;
}

function abs(file) {
  return path.isAbsolute(file) ? file : path.join(rootDir, file);
}

async function readJson(file) {
  return JSON.parse(await fs.readFile(abs(file), 'utf8'));
}

async function writeJson(file, value) {
  await fs.mkdir(path.dirname(abs(file)), { recursive: true });
  await fs.writeFile(abs(file), `${JSON.stringify(value, null, 2)}\n`);
}

function normalizeBaseUrl(baseUrl) {
  return baseUrl.replace(/\/+$/, '');
}

function makeUrl(baseUrl, target) {
  if (/^https?:\/\//.test(target)) {
    return target;
  }

  return `${normalizeBaseUrl(baseUrl)}${target.startsWith('/') ? target : `/${target}`}`;
}

function relativeFromWebE2e(file) {
  return path.relative(abs('docs/web-e2e'), abs(file)).replaceAll(path.sep, '/');
}

function sanitizeFileName(value) {
  return value
    .replace(/[\\/:*?"<>|#%&{}$!`'@+=\s]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 120);
}

function truncate(value, max = 1200) {
  if (value === null || value === undefined) {
    return '';
  }

  const text = String(value).replace(/\s+/g, ' ').trim();
  return text.length > max ? `${text.slice(0, max)}...` : text;
}

function statusLabel(status) {
  if (status === 'pass') {
    return '✔ pass';
  }

  if (status === 'targetOut') {
    return '— 対象外';
  }

  return '✘ fail';
}

function extractOperations(openapi) {
  const operations = [];
  for (const [route, pathItem] of Object.entries(openapi.paths ?? {})) {
    for (const [method, operation] of Object.entries(pathItem ?? {})) {
      if (!METHODS.has(method)) {
        continue;
      }

      operations.push({
        operationId: operation.operationId ?? '',
        method: method.toUpperCase(),
        path: route,
        summary: operation.summary ?? '',
        tags: operation.tags ?? [],
        hasRequestBody: Boolean(operation.requestBody),
        parameters: operation.parameters ?? [],
      });
    }
  }

  return operations;
}

function parseMatrix(markdown) {
  const lines = markdown.split(/\r?\n/);
  const rows = [];
  let inTable = false;
  let no = 0;

  for (const line of lines) {
    if (line.startsWith('| 区分 | 機能 |')) {
      inTable = true;
      continue;
    }

    if (!inTable || !line.startsWith('|')) {
      continue;
    }

    if (line.startsWith('|---')) {
      continue;
    }

    const columns = splitTableRow(line);
    if (columns.length < 12) {
      continue;
    }

    no += 1;
    rows.push({
      no,
      section: columns[0],
      feature: stripMarkdown(columns[1]),
      screen: stripMarkdown(columns[2]),
      resource: stripMarkdown(columns[3]),
      implementation: columns.slice(4, 9),
      evidence: stripMarkdown(columns[9]),
      matrixWebResult: stripMarkdown(columns[10]),
      matrixScreenshot: columns[11],
      rawColumns: columns,
    });
  }

  return rows;
}

function splitTableRow(line) {
  const body = line.trim().replace(/^\|/, '').replace(/\|$/, '');
  return body.split('|').map((column) => column.trim());
}

function stripMarkdown(value) {
  return value
    .replace(/<br\s*\/?>/gi, ' ')
    .replace(/!\[[^\]]*]\([^)]*\)/g, '')
    .replace(/\[([^\]]+)]\([^)]*\)/g, '$1')
    .replace(/`([^`]+)`/g, '$1')
    .trim();
}

function parseResourceOperations(resource) {
  const matches = [];
  const regex = /\b(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+(\/[^\s;、,]+)/g;
  let match;
  while ((match = regex.exec(resource)) !== null) {
    matches.push(`${match[1].toUpperCase()} ${normalizeOpenApiPath(match[2])}`);
  }

  return matches;
}

function normalizeOpenApiPath(route) {
  if (route === '/' || route === '/index') {
    return '/index';
  }

  return route.replace(/\/$/, '');
}

function deriveTargetUrl(row, baselineRow) {
  if (baselineRow?.targetUrl) {
    return baselineRow.targetUrl;
  }

  const screen = row.screen;
  if (screen.startsWith('/') && !/[{}…]|\.{3}|→|\s/.test(screen)) {
    return screen.replace('/products/list', '/products');
  }

  const ops = parseResourceOperations(row.resource);
  const get = ops.find((op) => op.startsWith('GET '));
  if (get) {
    const route = get.slice(4);
    return route === '/index' ? '/' : route;
  }

  return null;
}

function isTargetOut(row, baselineRow) {
  return row.implementation[2] === '✔'
    || row.matrixWebResult.includes('対象外')
    || baselineRow?.webResult?.includes('対象外');
}

function isLikelyAuthFailure(targetUrl, finalUrl, status, pageText) {
  const final = safePath(finalUrl);
  const target = safePath(targetUrl);
  const text = pageText ?? '';

  if (status === 401 || status === 403) {
    return true;
  }

  if (target.startsWith('/admin') && target !== '/admin/login') {
    return final === '/admin/login' || text.includes('管理者ログインが必要です');
  }

  if (target.startsWith('/mypage') && !target.startsWith('/mypage/login')) {
    return final === '/login' || text.includes('ログインしてください') || text.includes('ログインが必要');
  }

  return false;
}

function isRuntimeErrorPage(text) {
  const body = text ?? '';
  return body.includes('Fatal error')
    || body.includes('Uncaught Error')
    || body.includes('Uncaught Exception')
    || body.includes('Failed opening required')
    || body.includes('vendor/autoload.php')
    || body.includes('xdebug-error');
}

function safePath(urlOrPath) {
  try {
    if (/^https?:\/\//.test(urlOrPath)) {
      return new URL(urlOrPath).pathname;
    }

    return new URL(urlOrPath, 'http://localhost').pathname;
  } catch {
    return urlOrPath;
  }
}

async function snapshotPage(page) {
  return await page.evaluate(() => {
    const normalize = (value) => (value || '').replace(/\s+/g, ' ').trim();
    const h1 = document.querySelector('h1')?.textContent ?? '';
    const errorSelectors = [
      '.ec-errorMessage',
      '.text-danger',
      '.invalid-feedback',
      '.error',
      '[class*="error"]',
      '[class*="Error"]',
      '.alert-danger',
    ];
    const errors = [...document.querySelectorAll(errorSelectors.join(','))]
      .map((element) => normalize(element.textContent))
      .filter(Boolean);
    const forms = [...document.forms].map((form) => ({
      action: form.getAttribute('action') || '',
      method: (form.getAttribute('method') || 'get').toLowerCase(),
      names: [...form.elements]
        .map((element) => element.getAttribute('name'))
        .filter((name) => Boolean(name)),
    }));

    return {
      title: document.title,
      h1: normalize(h1),
      pageText: normalize(document.body?.innerText ?? ''),
      errorText: [...new Set(errors)].join(' / '),
      forms,
    };
  });
}

async function screenshot(page, file) {
  await fs.mkdir(path.dirname(file), { recursive: true });
  await page.screenshot({ path: file, fullPage: true });
}

async function visitFeature(page, baseUrl, row, baselineRow, screenshotDir) {
  const targetUrl = deriveTargetUrl(row, baselineRow);
  if (isTargetOut(row, baselineRow)) {
    return {
      ...row,
      status: 'targetOut',
      webResult: '— 対象外',
      targetUrl,
      finalUrl: null,
      httpStatus: null,
      title: '',
      h1: '',
      pageText: baselineRow?.pageText ?? row.evidence,
      errorText: '',
      forms: [],
      screenshot: null,
      reason: baselineRow?.webResult || row.evidence || '意図的未実装',
      operations: parseResourceOperations(row.resource),
    };
  }

  if (!targetUrl) {
    return {
      ...row,
      status: 'fail',
      webResult: '✘ fail（target URL を導出できない）',
      targetUrl: null,
      finalUrl: null,
      httpStatus: null,
      title: '',
      h1: '',
      pageText: '',
      errorText: 'target URL を導出できない',
      forms: [],
      screenshot: null,
      reason: 'target URL を導出できない',
      operations: parseResourceOperations(row.resource),
    };
  }

  const url = makeUrl(baseUrl, targetUrl);
  const operations = parseResourceOperations(row.resource);
  const unsafeOperations = operations.filter((operation) => !operation.startsWith('GET '));
  const screenshotFile = path.join(
    screenshotDir,
    `${String(row.no).padStart(3, '0')}-${sanitizeFileName(row.feature)}.png`,
  );

  try {
    const response = await page.goto(url, {
      waitUntil: 'domcontentloaded',
      timeout: NAV_TIMEOUT_MS,
    });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    await screenshot(page, screenshotFile);

    const httpStatus = response?.status() ?? null;
    const failedByStatus = httpStatus !== null && httpStatus >= 400;
    const authFailure = isLikelyAuthFailure(targetUrl, page.url(), httpStatus, snap.pageText);
    const runtimeError = isRuntimeErrorPage(snap.pageText);
    const unsafeNotExecuted = unsafeOperations.length > 0 && !failedByStatus && !authFailure && !runtimeError;
    const status = failedByStatus || authFailure || runtimeError || unsafeNotExecuted ? 'fail' : 'pass';
    const detail = status === 'pass'
      ? '✔ pass'
      : unsafeNotExecuted
        ? `✘ fail（unsafe operation not executed: ${unsafeOperations.join(', ')}）`
        : `✘ fail（status=${httpStatus ?? 'unknown'} final=${safePath(page.url())}${runtimeError ? ' runtime-error' : ''}）`;

    return {
      ...row,
      status,
      webResult: detail,
      targetUrl,
      finalUrl: page.url(),
      httpStatus,
      title: snap.title,
      h1: snap.h1,
      pageText: truncate(snap.pageText, 1800),
      errorText: truncate(snap.errorText, 800),
      forms: snap.forms,
      screenshot: relativeFromWebE2e(screenshotFile),
      reason: status === 'pass'
        ? null
        : unsafeNotExecuted
          ? `Browser navigation reached the page, but ${unsafeOperations.join(', ')} was not executed as an OK scenario.`
          : (snap.errorText || truncate(snap.pageText, 500)),
      operations,
    };
  } catch (error) {
    const snap = await snapshotPage(page).catch(() => ({
      title: '',
      h1: '',
      pageText: '',
      errorText: '',
      forms: [],
    }));
    await screenshot(page, screenshotFile).catch(() => {});

    return {
      ...row,
      status: 'fail',
      webResult: `✘ fail（${error.name}: ${error.message.split('\n')[0]}）`,
      targetUrl,
      finalUrl: page.url(),
      httpStatus: null,
      title: snap.title,
      h1: snap.h1,
      pageText: truncate(snap.pageText, 1800),
      errorText: truncate(snap.errorText || error.message, 800),
      forms: snap.forms,
      screenshot: relativeFromWebE2e(screenshotFile),
      reason: error.message,
      operations: parseResourceOperations(row.resource),
    };
  }
}

async function getCsrf(page, url) {
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

  return await page.evaluate(() => {
    const input = document.querySelector('input[name="csrfToken"], input[name="_csrf_token"], input[name="_token"]');
    return input?.getAttribute('value') ?? '';
  });
}

async function submitBrowserForm(page, baseUrl, testCase, screenshotDir) {
  const setupUrl = makeUrl(baseUrl, testCase.setupPath ?? testCase.path);
  await page.goto(setupUrl, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

  const form = await formFor(page, testCase.path);
  for (const [name, value] of Object.entries(testCase.data ?? {})) {
    await setFormValue(form, name, value);
  }

  const csrf = form.locator('[name="csrfToken"], [name="_csrf_token"], [name="_token"]').first();
  if (await csrf.count()) {
    if (testCase.csrfMode === 'omit') {
      await csrf.evaluate((element) => element.remove());
    } else if (testCase.csrfMode === 'invalid') {
      await csrf.evaluate((element) => {
        element.value = 'invalid-csrf-token';
      });
    }
  }

  const submit = form.locator('button[type="submit"], input[type="submit"], button:not([type])').last();
  const navigation = await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS }).catch(() => null),
    (await submit.count())
      ? submit.click({ timeout: 5000 })
      : form.evaluate((element) => element.requestSubmit()),
  ]).then(([response]) => response);
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

  const snap = await snapshotPage(page);
  const screenshotFile = path.join(screenshotDir, `${sanitizeFileName(testCase.id)}.png`);
  await screenshot(page, screenshotFile);

  return {
    finalUrl: page.url(),
    httpStatus: navigation?.status() ?? null,
    title: snap.title,
    h1: snap.h1,
    pageText: truncate(snap.pageText, 1800),
    errorText: truncate(snap.errorText, 800),
    forms: snap.forms,
    screenshot: relativeFromWebE2e(screenshotFile),
  };
}

async function formFor(page, actionPath) {
  let forms = page.locator(`form[action="${escapeCssAttr(actionPath)}"]`);
  if (await forms.count()) {
    return forms.last();
  }

  forms = page.locator('form[method="post"], form[method="POST"]');
  if (await forms.count()) {
    return forms.last();
  }

  forms = page.locator('form');
  if (await forms.count()) {
    return forms.last();
  }

  throw new Error(`form not found for ${actionPath}`);
}

async function setFormValue(form, name, value) {
  const selector = `[name="${escapeCssAttr(name)}"]`;
  let field = form.locator(selector).first();
  if (!await field.count()) {
    await form.evaluate(({ element, fieldName, fieldValue }) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = fieldName;
      input.value = String(fieldValue);
      element.appendChild(input);
    }, { fieldName: name, fieldValue: value });
    return;
  }

  const meta = await field.evaluate((element) => ({
    tag: element.tagName.toLowerCase(),
    type: element.getAttribute('type') ?? '',
  }));

  if (meta.tag === 'select') {
    await field.selectOption(String(value)).catch(async () => {
      await field.evaluate((element, fieldValue) => {
        element.value = String(fieldValue);
      }, value);
    });
    return;
  }

  if (meta.type === 'checkbox') {
    if (value) {
      await field.check({ force: true }).catch(async () => {
        await field.evaluate((element, fieldValue) => {
          element.checked = true;
          element.value = String(fieldValue);
        }, value);
      });
    } else {
      await field.uncheck({ force: true }).catch(() => {});
    }
    return;
  }

  if (meta.type === 'radio') {
    const option = form.locator(`${selector}[value="${escapeCssAttr(String(value))}"]`).first();
    field = await option.count() ? option : field;
    await field.check({ force: true }).catch(async () => {
      await field.evaluate((element) => {
        element.checked = true;
      });
    });
    return;
  }

  await field.fill(String(value));
}

async function submitHttpRequest(page, context, baseUrl, testCase, screenshotDir) {
  const csrf = testCase.csrfMode === 'normal'
    ? await getCsrf(page, makeUrl(baseUrl, testCase.setupPath ?? testCase.path))
    : '';
  const form = { ...(testCase.data ?? {}) };
  if (testCase.csrfMode === 'normal') {
    form.csrfToken = csrf;
  } else if (testCase.csrfMode === 'invalid') {
    form.csrfToken = 'invalid-csrf-token';
  }

  const response = await context.request.fetch(makeUrl(baseUrl, testCase.path), {
    method: testCase.method,
    form,
    maxRedirects: 0,
    timeout: NAV_TIMEOUT_MS,
  });
  const body = await response.text().catch(() => '');
  const screenshotFile = path.join(screenshotDir, `${sanitizeFileName(testCase.id)}.png`);

  await page.setContent(`
    <!doctype html>
    <meta charset="utf-8">
    <title>${escapeHtml(testCase.id)}</title>
    <body>
      <h1>${escapeHtml(testCase.name)}</h1>
      <dl>
        <dt>method</dt><dd>${escapeHtml(testCase.method)}</dd>
        <dt>path</dt><dd>${escapeHtml(testCase.path)}</dd>
        <dt>status</dt><dd>${response.status()}</dd>
      </dl>
      <pre>${escapeHtml(truncate(body, 4000))}</pre>
    </body>
  `);
  await screenshot(page, screenshotFile);

  return {
    finalUrl: makeUrl(baseUrl, testCase.path),
    httpStatus: response.status(),
    title: testCase.id,
    h1: testCase.name,
    pageText: truncate(body, 1800),
    errorText: truncate(extractErrorFromText(body), 800),
    forms: [],
    screenshot: relativeFromWebE2e(screenshotFile),
  };
}

function extractErrorFromText(body) {
  return body
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function escapeCssAttr(value) {
  return String(value).replaceAll('\\', '\\\\').replaceAll('"', '\\"');
}

function negativeCaseDefinitions(runId) {
  const email = `web-e2e-ng-${runId}@example.test`;
  return [
    {
      id: 'ng-entry-required-missing',
      name: '会員登録 必須欠落',
      mode: 'browserForm',
      method: 'POST',
      path: '/entry',
      setupPath: '/entry',
      data: { email: '', password: '', name01: '', name02: '' },
      expectedStatuses: [400, 403],
      expectedText: ['必須', 'Invalid input', 'Validation error', 'Invalid parameter type'],
    },
    {
      id: 'ng-entry-invalid-email-mismatch',
      name: '会員登録 メール形式不正/確認不一致',
      mode: 'browserForm',
      method: 'POST',
      path: '/entry',
      setupPath: '/entry',
      data: {
        name01: '山田',
        name02: '太郎',
        kana01: 'ヤマダ',
        kana02: 'タロウ',
        postalCode: '1000001',
        pref: '13',
        addr01: '千代田区',
        addr02: '1-1',
        phoneNumber: '0312345678',
        email: 'not-an-email',
        email_confirm: 'different@example.test',
        password: 'short',
        password_confirm: 'different-password',
        user_policy_check: '1',
      },
      expectedStatuses: [400, 409],
      expectedText: ['メール', 'パスワード', 'Invalid input', 'Invalid parameter type'],
    },
    {
      id: 'ng-entry-csrf-missing',
      name: '会員登録 CSRF欠落',
      mode: 'http',
      method: 'POST',
      path: '/entry',
      setupPath: '/entry',
      csrfMode: 'omit',
      data: { email, password: 'valid-password-2026', name01: '山田', name02: '太郎' },
      expectedStatuses: [403],
      expectedText: ['CSRF'],
    },
    {
      id: 'ng-login-wrong-credential',
      name: 'ログイン 認証失敗',
      mode: 'browserForm',
      method: 'POST',
      path: '/login',
      setupPath: '/login',
      data: { email: 'missing@example.test', password: 'wrong-password-2026' },
      expectedStatuses: [401],
      expectedText: ['ログイン', '認証', 'メールアドレス'],
    },
    {
      id: 'ng-login-invalid-email',
      name: 'ログイン 形式不正',
      mode: 'http',
      method: 'POST',
      path: '/login',
      setupPath: '/login',
      data: { email: 'broken', password: 'x' },
      expectedStatuses: [400, 401],
      expectedText: ['メール', 'Invalid input'],
    },
    {
      id: 'ng-forgot-password-invalid-email',
      name: 'パスワード再発行 メール形式不正',
      mode: 'http',
      method: 'POST',
      path: '/forgot-password',
      setupPath: '/forgot-password',
      data: { email: 'broken' },
      expectedStatuses: [400, 403],
      expectedText: ['メール', 'Invalid input'],
    },
    {
      id: 'ng-reset-invalid-key',
      name: 'パスワードリセット 不正キー',
      mode: 'http',
      method: 'POST',
      path: '/reset',
      setupPath: '/reset',
      data: { resetKey: 'missing-key', password: 'valid-password-2026' },
      expectedStatuses: [400, 403, 404],
      expectedText: ['リセット', 'キー', 'Invalid input'],
    },
    {
      id: 'ng-contact-required-missing',
      name: 'お問い合わせ 必須欠落',
      mode: 'browserForm',
      method: 'POST',
      path: '/contact',
      setupPath: '/contact',
      data: { contactName01: '', contactName02: '', contactEmail: '', contactContents: '' },
      expectedStatuses: [400, 403],
      expectedText: ['必須', 'Invalid input'],
    },
    {
      id: 'ng-contact-invalid-email-long-body',
      name: 'お問い合わせ 形式不正/境界超過',
      mode: 'http',
      method: 'POST',
      path: '/contact',
      setupPath: '/contact',
      data: {
        contactName01: '山田',
        contactName02: '太郎',
        contactEmail: 'broken',
        contactContents: 'x'.repeat(5000),
      },
      expectedStatuses: [400, 403],
      expectedText: ['メール', 'Invalid input'],
    },
    {
      id: 'ng-cart-item-invalid-quantity',
      name: 'カート投入 数量境界不正',
      mode: 'http',
      method: 'POST',
      path: '/cart/item',
      setupPath: '/products',
      data: { productCode: 'missing-product', quantity: '0' },
      expectedStatuses: [400, 403, 404],
      expectedText: ['数量', '商品', 'Invalid input'],
    },
    {
      id: 'ng-shopping-non-member-required-missing',
      name: '非会員購入 必須欠落',
      mode: 'browserForm',
      method: 'POST',
      path: '/shopping/non-member',
      setupPath: '/shopping/non-member',
      data: { name01: '', name02: '', kana01: '', kana02: '', companyName: '', email: '', email_confirm: '', phoneNumber: '', postalCode: '', pref: '', addr01: '', addr02: '' },
      expectedStatuses: [400],
      expectedText: ['入力してください。', 'お客様情報の入力'],
    },
    {
      id: 'ng-shopping-checkout-nonexistent-preorder',
      name: '購入確定 存在しない preOrderId',
      mode: 'http',
      method: 'POST',
      path: '/shopping/checkout',
      setupPath: '/shopping',
      data: { preOrderId: 'missing-preorder' },
      expectedStatuses: [400, 403, 404],
      expectedText: ['preOrderId', '注文', 'Invalid input'],
    },
    {
      id: 'ng-mypage-change-unauthenticated',
      name: '会員情報変更 未ログイン',
      mode: 'http',
      method: 'POST',
      path: '/mypage/change',
      setupPath: '/mypage/change',
      data: { email: 'change@example.test', name01: '山田', name02: '太郎' },
      expectedStatuses: [401, 403],
      expectedText: ['ログイン', '認証'],
    },
    {
      id: 'ng-mypage-address-nonexistent-id',
      name: 'お届け先編集 存在しないID/未ログイン',
      mode: 'http',
      method: 'PUT',
      path: '/mypage/address',
      setupPath: '/mypage/address',
      data: { addressId: '99999999', name01: '山田', name02: '太郎' },
      expectedStatuses: [400, 401, 403, 404],
      expectedText: ['ログイン', '住所', 'Invalid input', 'addressId'],
    },
    {
      id: 'ng-admin-login-wrong-credential',
      name: '管理ログイン 認証失敗',
      mode: 'browserForm',
      method: 'POST',
      path: '/admin/login',
      setupPath: '/admin/login',
      data: { loginId: 'missing-admin', password: 'wrong-password-2026' },
      expectedStatuses: [401],
      expectedText: ['ログイン', '認証'],
    },
    {
      id: 'ng-admin-login-csrf-invalid',
      name: '管理ログイン CSRF不一致',
      mode: 'http',
      method: 'POST',
      path: '/admin/login',
      setupPath: '/admin/login',
      csrfMode: 'invalid',
      data: { loginId: 'test-admin', password: 'admin-test-password-2026' },
      expectedStatuses: [403],
      expectedText: ['CSRF'],
    },
    {
      id: 'ng-admin-two-factor-no-challenge',
      name: '管理2FA チャレンジなし',
      mode: 'http',
      method: 'POST',
      path: '/admin/two-factor-auth',
      setupPath: '/admin/two-factor-auth',
      data: { deviceToken: '000000' },
      expectedStatuses: [400, 403],
      expectedText: ['二要素', 'チャレンジ'],
    },
    {
      id: 'ng-admin-product-unauthenticated',
      name: '管理商品 未ログインPOST',
      mode: 'http',
      method: 'POST',
      path: '/admin/product',
      setupPath: '/admin/product',
      data: { productName: '', productCode: '', price02: '' },
      expectedStatuses: [401, 403, 400],
      expectedText: ['管理者', 'ログイン', 'Invalid input', 'Invalid parameter type'],
    },
    {
      id: 'ng-admin-csv-upload-unauthenticated',
      name: '管理CSVアップロード 未ログイン',
      mode: 'http',
      method: 'POST',
      path: '/admin/product/csv-product',
      setupPath: '/admin/product/csv-product',
      data: {},
      expectedStatuses: [401, 403, 400, 404],
      expectedText: ['管理者', 'ログイン', 'CSV', 'Invalid parameter type'],
    },
  ];
}

async function runNegativeCases(page, context, baseUrl, runId, screenshotDir, skipNegative) {
  if (skipNegative) {
    return [];
  }

  const cases = negativeCaseDefinitions(runId);
  const results = [];
  for (const testCase of cases) {
    try {
      const observation = testCase.mode === 'browserForm'
        ? await submitBrowserForm(page, baseUrl, testCase, screenshotDir)
        : await submitHttpRequest(page, context, baseUrl, testCase, screenshotDir);

      const observedStatus = observation.httpStatus;
      const statusOk = observedStatus === null
        ? true
        : testCase.expectedStatuses.includes(observedStatus);
      const text = `${observation.errorText} ${observation.pageText}`;
      const textOk = testCase.expectedText.some((expected) => text.includes(expected));
      const finalStatus = !isRuntimeErrorPage(text) && statusOk && (textOk || observedStatus === 403 || observedStatus === 401)
        ? 'pass'
        : 'fail';

      results.push({
        id: testCase.id,
        name: testCase.name,
        method: testCase.method,
        path: testCase.path,
        mode: testCase.mode,
        expectedStatuses: testCase.expectedStatuses,
        expectedText: testCase.expectedText,
        status: finalStatus,
        ...observation,
      });
    } catch (error) {
      results.push({
        id: testCase.id,
        name: testCase.name,
        method: testCase.method,
        path: testCase.path,
        mode: testCase.mode,
        expectedStatuses: testCase.expectedStatuses,
        expectedText: testCase.expectedText,
        status: 'fail',
        finalUrl: null,
        httpStatus: null,
        title: '',
        h1: '',
        pageText: '',
        errorText: `${error.name}: ${error.message}`,
        forms: [],
        screenshot: null,
      });
    }
  }

  return results;
}

function operationKey(method, route) {
  return `${method.toUpperCase()} ${normalizeOpenApiPath(route)}`;
}

function samplePath(openApiPath) {
  const sampled = openApiPath.replace(/\{[^}]+}/g, 'missing-id');
  return sampled === '/index' ? '/' : sampled;
}

async function probeUncoveredOperation(context, baseUrl, operation) {
  const url = makeUrl(baseUrl, samplePath(operation.path));
  try {
    const response = await context.request.fetch(url, {
      method: operation.method,
      maxRedirects: 0,
      timeout: NAV_TIMEOUT_MS,
    });
    const body = await response.text().catch(() => '');

    return {
      method: operation.method,
      path: operation.path,
      operationId: operation.operationId,
      status: response.status() < 500 && !isRuntimeErrorPage(body) ? 'pass' : 'fail',
      coverage: operation.method === 'GET' ? 'direct-http-get' : 'boundary-http-without-form-data',
      httpStatus: response.status(),
      url,
      reason: operation.method === 'GET'
        ? 'OpenAPI GET operation was probed directly.'
        : 'Unsafe operation was boundary-probed without form data/CSRF; full OK flow must be covered by a feature row.',
      responseText: truncate(extractErrorFromText(body), 800),
      featureNos: [],
    };
  } catch (error) {
    return {
      method: operation.method,
      path: operation.path,
      operationId: operation.operationId,
      status: 'fail',
      coverage: 'probe-error',
      httpStatus: null,
      url,
      reason: `${error.name}: ${error.message}`,
      responseText: '',
      featureNos: [],
    };
  }
}

async function buildOperationCoverage(context, baseUrl, operations, featureResults, probeUncovered) {
  const featuresByOperation = new Map();
  for (const feature of featureResults) {
    for (const op of feature.operations ?? []) {
      if (!featuresByOperation.has(op)) {
        featuresByOperation.set(op, []);
      }
      featuresByOperation.get(op).push(feature);
    }
  }

  const coverage = [];
  for (const operation of operations) {
    const key = operationKey(operation.method, operation.path);
    const features = featuresByOperation.get(key) ?? [];
    if (features.length > 0) {
      const anyFail = features.some((feature) => feature.status === 'fail');
      const allTargetOut = features.every((feature) => feature.status === 'targetOut');
      coverage.push({
        ...operation,
        status: allTargetOut ? 'targetOut' : (anyFail ? 'fail' : 'pass'),
        coverage: allTargetOut ? 'feature-target-out' : 'feature-matrix',
        httpStatus: null,
        url: null,
        reason: allTargetOut
          ? 'Feature matrix marks this operation out of scope.'
          : 'Covered by feature matrix browser navigation or operation evidence.',
        responseText: '',
        featureNos: features.map((feature) => feature.no),
        featureNames: features.map((feature) => feature.feature),
      });
      continue;
    }

    if (probeUncovered) {
      coverage.push(await probeUncoveredOperation(context, baseUrl, operation));
    } else {
      coverage.push({
        ...operation,
        status: 'fail',
        coverage: 'no-feature-row',
        httpStatus: null,
        url: null,
        reason: 'No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.',
        responseText: '',
        featureNos: [],
        featureNames: [],
      });
    }
  }

  return coverage;
}

function countByStatus(items) {
  return items.reduce((acc, item) => {
    acc[item.status] = (acc[item.status] ?? 0) + 1;
    return acc;
  }, { pass: 0, fail: 0, targetOut: 0 });
}

function changedFailures(current, baselineResults) {
  const baselineByNo = new Map(baselineResults.map((result) => [result.no, result]));
  return current
    .filter((result) => result.status === 'fail')
    .filter((result) => !(baselineByNo.get(result.no)?.webResult ?? '').includes('fail'));
}

function knownFailures(current, baselineResults) {
  const baselineByNo = new Map(baselineResults.map((result) => [result.no, result]));
  return current
    .filter((result) => result.status === 'fail')
    .filter((result) => (baselineByNo.get(result.no)?.webResult ?? '').includes('fail'));
}

function markdownReport(run) {
  const featureSummary = run.summary.features;
  const opSummary = run.summary.operations;
  const ngSummary = run.summary.negativeCases;
  const known = knownFailures(run.results, run.baseline.results ?? []);
  const fresh = changedFailures(run.results, run.baseline.results ?? []);
  const opFailures = run.operationCoverage.filter((op) => op.status === 'fail');
  const ngFailures = run.negativeCases.filter((testCase) => testCase.status === 'fail');

  return `# ${run.runId} Web+DB 全ルート検証結果

## Summary

- context: \`${run.context.appContext}\`
- baseUrl: \`${run.baseUrl}\`
- DB: \`${run.db.name}\` (\`DATABASE_URL\`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass ${featureSummary.pass} / fail ${featureSummary.fail} / 対象外 ${featureSummary.targetOut}
- OpenAPI operations: pass ${opSummary.pass} / fail ${opSummary.fail} / 対象外 ${opSummary.targetOut} / total ${run.operationCoverage.length}
- NG cases: pass ${ngSummary.pass} / fail ${ngSummary.fail} / total ${run.negativeCases.length}
- screenshots: \`docs/web-e2e/screenshots/${run.runId}/\`
- results JSON: \`docs/web-e2e/results/${run.runId}.json\`

## Scope

- 母集団は \`docs/api/openapi.json\` の ${run.operationCoverage.length} operations と \`docs/web-e2e/feature-implementation-matrix.md\` の ${run.results.length} features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは \`fail\` として記録した。

## Known Failures

${known.length === 0 ? '- なし' : known.map((item) => `- ${item.section} ${item.feature}: ${item.webResult} final=\`${item.finalUrl ?? ''}\` screenshot=\`${item.screenshot ?? ''}\``).join('\n')}

## New Failures

${fresh.length === 0 ? '- なし' : fresh.slice(0, 80).map((item) => `- ${String(item.no).padStart(3, '0')} ${item.section} ${item.feature}: ${item.webResult} final=\`${item.finalUrl ?? ''}\` reason=${truncate(item.reason ?? item.errorText, 220)}`).join('\n')}
${fresh.length > 80 ? `\n- ... ${fresh.length - 80} more failures are in the JSON.` : ''}

## OpenAPI Operation Failures

${opFailures.length === 0 ? '- なし' : opFailures.slice(0, 80).map((item) => `- ${item.method} ${item.path}: coverage=${item.coverage}, status=${item.httpStatus ?? 'n/a'}, reason=${truncate(item.reason, 220)}`).join('\n')}
${opFailures.length > 80 ? `\n- ... ${opFailures.length - 80} more operation failures are in the JSON.` : ''}

## Negative Case Failures

${ngFailures.length === 0 ? '- なし' : ngFailures.map((item) => `- ${item.name}: status=${item.httpStatus ?? 'browser'}, final=\`${item.finalUrl ?? ''}\`, error=${truncate(item.errorText || item.pageText, 220)}, screenshot=\`${item.screenshot ?? ''}\``).join('\n')}

## Negative Cases

${run.negativeCases.map((item) => `- ${statusLabel(item.status)} ${item.name}: ${item.method} ${item.path}, status=${item.httpStatus ?? 'browser'}, final=\`${item.finalUrl ?? ''}\`, screenshot=\`${item.screenshot ?? ''}\``).join('\n')}

## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- \`注文履歴詳細\` / \`再注文\` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
`;
}

async function writeReport(run) {
  const file = abs(`docs/web-e2e/${run.runId}-report.md`);
  await fs.writeFile(file, markdownReport(run));
}

async function updateMatrixFile(matrixPath, results, runId, enabled) {
  if (!enabled) {
    return;
  }

  const file = abs(matrixPath);
  const lines = (await fs.readFile(file, 'utf8')).split(/\r?\n/);
  const next = [];
  let inTable = false;
  let rowIndex = 0;

  for (const line of lines) {
    if (line.startsWith('| 区分 | 機能 |')) {
      inTable = true;
      next.push(line);
      continue;
    }

    if (!inTable || !line.startsWith('|') || line.startsWith('|---')) {
      next.push(line);
      continue;
    }

    const columns = splitTableRow(line);
    if (columns.length < 12 || rowIndex >= results.length) {
      next.push(line);
      continue;
    }

    const result = results[rowIndex];
    columns[10] = result.webResult;
    columns[11] = result.screenshot
      ? `[![${String(result.no).padStart(3, '0')} ${result.feature}](screenshots/${runId}/${path.basename(result.screenshot)})](screenshots/${runId}/${path.basename(result.screenshot)})`
      : (result.status === 'targetOut' ? `— ${result.reason ?? '対象外'}` : '');
    next.push(`| ${columns.join(' | ')} |`);
    rowIndex += 1;
  }

  await fs.writeFile(file, `${next.join('\n')}\n`);
}

async function updateEvidenceRetention(runId, enabled) {
  if (!enabled) {
    return;
  }

  const file = abs('docs/web-e2e/evidence-retention.md');
  const text = await fs.readFile(file, 'utf8');
  const addendum = `

## 最新run

- 最新の全件run: \`${runId}\`
- 結果JSON: \`docs/web-e2e/results/${runId}.json\`
- 結果レポート: \`docs/web-e2e/${runId}-report.md\`
- スクリーンショット: \`docs/web-e2e/screenshots/${runId}/\`
- \`20260608-canonical-resource-routes-web-e2e\` は比較用ベースラインとして参照する。
`;
  const cleaned = text.replace(/\n## 最新run\n[\s\S]*$/u, '').trimEnd();
  await fs.writeFile(file, `${cleaned}${addendum}`);
}

async function maybeRegisterCustomer(page, baseUrl, runId) {
  const email = `web-e2e-${runId}-${Date.now()}@example.test`;
  try {
    await page.goto(makeUrl(baseUrl, '/entry'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.fill('[name="name01"]', '山田');
    await page.fill('[name="name02"]', '太郎');
    await page.fill('[name="kana01"]', 'ヤマダ');
    await page.fill('[name="kana02"]', 'タロウ');
    await page.fill('[name="postalCode"]', '1000001');
    await page.selectOption('[name="pref"]', '13').catch(() => {});
    await page.fill('[name="addr01"]', '千代田区');
    await page.fill('[name="addr02"]', '1-1');
    await page.fill('[name="phoneNumber"]', '0312345678');
    await page.fill('[name="email"]', email);
    await page.fill('[name="email_confirm"]', email);
    await page.fill('[name="password"]', 'web-e2e-password-2026');
    await page.fill('[name="password_confirm"]', 'web-e2e-password-2026');
    await page.check('[name="user_policy_check"]').catch(() => {});
    const form = await formFor(page, '/entry');
    const submit = form.locator('button[type="submit"], input[type="submit"], button:not([type])').last();
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS }).catch(() => null),
      (await submit.count()) ? submit.click({ timeout: 5000 }) : form.evaluate((element) => element.requestSubmit()),
    ]);

    return {
      attempted: true,
      email,
      finalUrl: page.url(),
      pageText: truncate((await snapshotPage(page)).pageText, 800),
      success: page.url().includes('/entry/complete') || page.url().includes('/mypage'),
    };
  } catch (error) {
    return {
      attempted: true,
      email,
      finalUrl: page.url(),
      success: false,
      error: `${error.name}: ${error.message}`,
    };
  }
}

async function maybeAdminLogin(page, baseUrl) {
  try {
    await page.goto(makeUrl(baseUrl, '/admin/login'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.fill('[name="loginId"]', 'test-admin');
    await page.fill('[name="password"]', 'admin-test-password-2026');
    const form = await formFor(page, '/admin/login');
    const submit = form.locator('button[type="submit"], input[type="submit"], button:not([type])').last();
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS }).catch(() => null),
      (await submit.count()) ? submit.click({ timeout: 5000 }) : form.evaluate((element) => element.requestSubmit()),
    ]);
    const snap = await snapshotPage(page);
    return {
      attempted: true,
      finalUrl: page.url(),
      success: page.url().includes('/admin/two-factor-auth') || page.url().includes('/admin/index'),
      pageText: truncate(snap.pageText, 800),
      errorText: snap.errorText,
    };
  } catch (error) {
    return {
      attempted: true,
      finalUrl: page.url(),
      success: false,
      error: `${error.name}: ${error.message}`,
    };
  }
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  const runId = args['run-id'] || process.env.RUN_ID || `${jstDate()}-web-db-all-routes`;
  const baseUrl = normalizeBaseUrl(args['base-url'] || process.env.BASE_URL || 'http://127.0.0.1:18080');
  const baselinePath = args.baseline || DEFAULT_BASELINE;
  const matrixPath = args.matrix || DEFAULT_MATRIX;
  const openapiPath = args.openapi || DEFAULT_OPENAPI;
  const updateMatrix = args['update-matrix'] !== 'false' && args['no-update-matrix'] !== true;
  const probeUncovered = args['no-probe-uncovered'] !== true;
  const skipNegative = args['skip-negative'] === true;
  const limit = args.limit ? Number(args.limit) : null;
  const headless = args.headed === true ? false : true;

  const [baseline, openapi, matrixMarkdown] = await Promise.all([
    readJson(baselinePath),
    readJson(openapiPath),
    fs.readFile(abs(matrixPath), 'utf8'),
  ]);

  const operations = extractOperations(openapi);
  const matrixRows = parseMatrix(matrixMarkdown);
  const baselineByNo = new Map((baseline.results ?? []).map((result) => [result.no, result]));
  const screenshotDir = abs(`docs/web-e2e/screenshots/${runId}`);
  await fs.mkdir(screenshotDir, { recursive: true });

  const browser = await chromium.launch({ headless });
  const context = await browser.newContext({
    viewport: { width: 1365, height: 900 },
    ignoreHTTPSErrors: true,
    locale: 'ja-JP',
  });
  const page = await context.newPage();

  const setup = {
    customerRegistration: await maybeRegisterCustomer(page, baseUrl, runId),
    adminLogin: await maybeAdminLogin(page, baseUrl),
  };

  const featureRows = limit ? matrixRows.slice(0, limit) : matrixRows;
  const results = [];
  for (const row of featureRows) {
    const result = await visitFeature(page, baseUrl, row, baselineByNo.get(row.no), screenshotDir);
    results.push(result);
    process.stdout.write(`${String(row.no).padStart(3, '0')} ${result.webResult} ${row.section} ${row.feature}\n`);
  }

  if (limit && matrixRows.length > limit) {
    for (const row of matrixRows.slice(limit)) {
      results.push({
        ...row,
        status: 'fail',
        webResult: '✘ fail（--limit により未実行）',
        targetUrl: deriveTargetUrl(row, baselineByNo.get(row.no)),
        finalUrl: null,
        httpStatus: null,
        title: '',
        h1: '',
        pageText: '',
        errorText: '',
        forms: [],
        screenshot: null,
        reason: '--limit により未実行',
        operations: parseResourceOperations(row.resource),
      });
    }
  }

  const negativeCases = await runNegativeCases(
    page,
    context,
    baseUrl,
    runId,
    path.join(screenshotDir, 'negative'),
    skipNegative,
  );

  const operationCoverage = await buildOperationCoverage(
    context,
    baseUrl,
    operations,
    results,
    probeUncovered,
  );

  await browser.close();

  const featureSummary = countByStatus(results);
  const operationSummary = countByStatus(operationCoverage);
  const negativeSummary = countByStatus(negativeCases);
  const run = {
    runId,
    executedAt: nowIsoJst(),
    context: {
      appContext: process.env.APP_CONTEXT || 'html-eccube-sql-hal-app',
      phpServer: 'public/page.php',
      runner: 'scripts/web-e2e-runner.mjs',
      node: process.version,
    },
    baseUrl,
    db: {
      databaseUrl: process.env.DATABASE_URL || '',
      name: databaseName(process.env.DATABASE_URL || ''),
      setup: 'sql/setup-db.sh',
      dataPolicy: 'Web operation first. No direct SQL fixture/seed in runner.',
    },
    baseline: {
      runId: baseline.runId,
      path: baselinePath,
      summary: baseline.summary,
      results: baseline.results ?? [],
    },
    setup,
    summary: {
      features: featureSummary,
      operations: operationSummary,
      negativeCases: negativeSummary,
      totalFeatures: results.length,
      totalOperations: operationCoverage.length,
    },
    results,
    operationCoverage,
    negativeCases,
  };

  await writeJson(`docs/web-e2e/results/${runId}.json`, withoutBaselineResults(run));
  await writeReport(run);
  await updateMatrixFile(matrixPath, results, runId, updateMatrix && !limit);
  await updateEvidenceRetention(runId, updateMatrix && !limit);

  process.stdout.write(`\nrunId=${runId}\n`);
  process.stdout.write(`features pass=${featureSummary.pass} fail=${featureSummary.fail} targetOut=${featureSummary.targetOut}\n`);
  process.stdout.write(`operations pass=${operationSummary.pass} fail=${operationSummary.fail} targetOut=${operationSummary.targetOut}\n`);
  process.stdout.write(`negative pass=${negativeSummary.pass} fail=${negativeSummary.fail}\n`);
}

function databaseName(databaseUrl) {
  if (!databaseUrl) {
    return '';
  }

  try {
    return new URL(databaseUrl).pathname.replace(/^\//, '');
  } catch {
    return '';
  }
}

function withoutBaselineResults(run) {
  return {
    ...run,
    results: run.results.map(sanitizeFeatureResultForJson),
    baseline: {
      runId: run.baseline.runId,
      path: run.baseline.path,
      summary: run.baseline.summary,
    },
  };
}

function sanitizeFeatureResultForJson(result) {
  const {
    matrixWebResult: _matrixWebResult,
    matrixScreenshot: _matrixScreenshot,
    rawColumns: _rawColumns,
    ...jsonResult
  } = result;

  return jsonResult;
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
