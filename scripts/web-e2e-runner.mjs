#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
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

function deriveTargetUrl(row, baselineRow, setup = null) {
  const business = setup?.businessState ?? {};
  if (business.confirmScreenshot && /\bPOST\s+\/shopping\/confirm\b/.test(row.resource)) {
    return '/shopping/confirm';
  }

  if (business.productCode && /\b(?:GET|PUT|DELETE)\s+\/admin\/product\b/.test(row.resource)) {
    return `/admin/product?productCode=${encodeURIComponent(business.productCode)}`;
  }

  if (business.productCode && /\b(?:GET|PUT)\s+\/admin\/product\/product-class\b/.test(row.resource)) {
    return `/admin/product/product-class?productCode=${encodeURIComponent(business.productCode)}`;
  }

  if (business.productCode && /\bGET\s+\/product\b/.test(row.resource)) {
    return `/product?productCode=${encodeURIComponent(business.productCode)}`;
  }

  if (business.memberOrderNo && /\bGET\s+\/mypage\/history\b/.test(row.resource)) {
    return `/mypage/history?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (
    business.memberOrderNo
    && (
      row.feature.includes('注文履歴詳細')
      || /\bGET\s+\/mypage\/order-history\b/.test(row.resource)
    )
  ) {
    return `/mypage/history?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (business.memberOrderNo && /\bPOST\s+\/mypage\/reorder\b/.test(row.resource)) {
    return '/cart';
  }

  if (business.memberOrderNo && /\bGET\s+\/admin\/order\/export-order-pdf\b/.test(row.resource)) {
    return `/admin/order/export-order-pdf?orderNos[]=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (business.memberOrderNo && /\b(?:GET|PUT)\s+\/admin\/order(?:\s|;|$)/.test(row.resource)) {
    return `/admin/order?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (business.memberOrderNo && /\b(?:GET|POST)\s+\/admin\/order\/shipping-notify-mail\b/.test(row.resource)) {
    return `/admin/order/shipping-notify-mail?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (business.memberOrderNo && /\b(?:GET|POST)\s+\/admin\/order\/send-mail\b/.test(row.resource)) {
    return `/admin/order/send-mail?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (business.memberOrderNo && /\b(?:GET|PUT|POST)\s+\/admin\/order\/shipping-address\b/.test(row.resource)) {
    return `/admin/order/shipping-address?orderNo=${encodeURIComponent(business.memberOrderNo)}`;
  }

  if (/\bPOST\s+\/admin\/logout\b/.test(row.resource)) {
    return '/admin/login';
  }

  if (/\bPOST\s+\/mypage\/withdraw\b/.test(row.resource)) {
    return '/mypage/withdraw-complete';
  }

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

function isDownloadFeature(row, operations) {
  return operations.some((operation) => operation.startsWith('GET '))
    && (
      row.feature.includes('CSV出力')
      || row.feature.includes('PDF出力')
      || row.feature.includes('CSVエクスポート')
      || row.feature.includes('PDFエクスポート')
    );
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

async function verifyDownloadFeature(page, context, baseUrl, row, targetUrl, screenshotFile, operations) {
  const response = await httpGet(context, baseUrl, targetUrl);
  const contentType = response.headers()['content-type'] ?? '';
  const disposition = response.headers()['content-disposition'] ?? '';
  const body = await response.text().catch(() => '');
  const readable = decodeResponseText(body);
  const runtimeError = isRuntimeErrorPage(readable);
  const status = response.status() < 400 && !runtimeError ? 'pass' : 'fail';

  await page.setContent(`
    <!doctype html>
    <meta charset="utf-8">
    <title>${escapeHtml(row.feature)}</title>
    <body>
      <h1>${escapeHtml(row.feature)}</h1>
      <dl>
        <dt>method</dt><dd>GET</dd>
        <dt>path</dt><dd>${escapeHtml(targetUrl)}</dd>
        <dt>status</dt><dd>${response.status()}</dd>
        <dt>content-type</dt><dd>${escapeHtml(contentType)}</dd>
        <dt>content-disposition</dt><dd>${escapeHtml(disposition)}</dd>
      </dl>
      <pre>${escapeHtml(truncate(extractErrorFromText(readable), 4000))}</pre>
    </body>
  `);
  await screenshot(page, screenshotFile);

  return {
    ...row,
    status,
    webResult: status === 'pass'
      ? '✔ pass（HTTP download evidence）'
      : `✘ fail（download status=${response.status()}${runtimeError ? ' runtime-error' : ''}）`,
    targetUrl,
    finalUrl: makeUrl(baseUrl, targetUrl),
    httpStatus: response.status(),
    title: row.feature,
    h1: row.feature,
    pageText: truncate(extractErrorFromText(readable), 1800),
    errorText: status === 'pass' ? '' : truncate(extractErrorFromText(readable), 800),
    forms: [],
    screenshot: relativeFromWebE2e(screenshotFile),
    reason: status === 'pass'
      ? `Download boundary covered over the authenticated HTTP context. content-type=${contentType || 'n/a'}`
      : truncate(extractErrorFromText(readable), 500),
    operations,
  };
}

async function visitFeature(page, baseUrl, row, baselineRow, screenshotDir, setup = null) {
  const targetUrl = deriveTargetUrl(row, baselineRow, setup);
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
  const setupEvidence = setup?.businessState?.operationEvidence ?? {};
  const unsafeCoveredBySetup = unsafeOperations.length > 0
    && unsafeOperations.every((operation) => setupEvidence[operation]?.ok === true);
  const unsafeTargetOutBySetup = unsafeOperations.length > 0
    && unsafeOperations.every((operation) => setupEvidence[operation]?.targetOut === true);
  const screenshotFile = path.join(
    screenshotDir,
    `${String(row.no).padStart(3, '0')}-${sanitizeFileName(row.feature)}.png`,
  );

  if (isDownloadFeature(row, operations)) {
    if (targetUrl.startsWith('/admin') && !targetUrl.startsWith('/admin/login')) {
      await maybeAdminLogin(page, page.context(), baseUrl, setup?.adminLogin?.totpSecret ?? '');
    }

    return await verifyDownloadFeature(page, page.context(), baseUrl, row, targetUrl, screenshotFile, operations);
  }

  const operationScreenshot = unsafeOperations
    .map((operation) => setupEvidence[operation]?.screenshot)
    .find((value) => typeof value === 'string' && value !== '');
  if (unsafeCoveredBySetup && operationScreenshot) {
    const evidence = unsafeOperations
      .map((operation) => setupEvidence[operation])
      .find((item) => item?.screenshot === operationScreenshot) ?? {};

    return {
      ...row,
      status: 'pass',
      webResult: '✔ pass（setup operation evidence）',
      targetUrl,
      finalUrl: evidence.finalUrl ?? targetUrl,
      httpStatus: evidence.httpStatus ?? null,
      title: evidence.title ?? '',
      h1: evidence.h1 ?? '',
      pageText: evidence.pageText ?? '',
      errorText: '',
      forms: evidence.forms ?? [],
      screenshot: operationScreenshot,
      reason: `Unsafe operation covered by setup: ${unsafeOperations.join(', ')}`,
      operations,
    };
  }

  try {
    if (targetUrl.startsWith('/admin') && !targetUrl.startsWith('/admin/login')) {
      await maybeAdminLogin(page, page.context(), baseUrl, setup?.adminLogin?.totpSecret ?? '');
    }

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
    const unsafeNotExecuted = unsafeOperations.length > 0
      && !unsafeCoveredBySetup
      && !unsafeTargetOutBySetup
      && !failedByStatus
      && !authFailure
      && !runtimeError;
    const targetOut = unsafeTargetOutBySetup && !failedByStatus && !authFailure && !runtimeError;
    const status = targetOut
      ? 'targetOut'
      : (failedByStatus || authFailure || runtimeError || unsafeNotExecuted ? 'fail' : 'pass');
    const detail = status === 'pass'
      ? (unsafeCoveredBySetup ? '✔ pass（setup operation evidence）' : '✔ pass')
      : status === 'targetOut'
        ? '— 対象外（external token boundary）'
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
        ? (unsafeCoveredBySetup ? `Unsafe operation covered by setup: ${unsafeOperations.join(', ')}` : null)
        : status === 'targetOut'
          ? unsafeOperations.map((operation) => setupEvidence[operation]?.reason).filter(Boolean).join(' / ')
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

  return await readCsrf(page);
}

async function readCsrf(page) {
  return await page.evaluate(() => {
    const input = document.querySelector('input[name="csrfToken"], input[name="_csrf_token"], input[name="_token"]');
    return input?.getAttribute('value') ?? '';
  });
}

async function formAction(page, selector) {
  return await page.locator(selector).first().evaluate((form) => form.getAttribute('action') ?? '').catch(() => '');
}

async function inputValue(page, selector) {
  return await page.locator(selector).first().evaluate((element) => element.value).catch(() => '');
}

function base32Decode(secret) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const char of String(secret).toUpperCase().replace(/=+$/g, '')) {
    const index = alphabet.indexOf(char);
    if (index < 0) {
      continue;
    }

    bits += index.toString(2).padStart(5, '0');
  }

  const bytes = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) {
    bytes.push(Number.parseInt(bits.slice(i, i + 8), 2));
  }

  return Buffer.from(bytes);
}

function totp(secret, timestamp = Date.now()) {
  const counter = Math.floor(timestamp / 1000 / 30);
  const buffer = Buffer.alloc(8);
  buffer.writeUInt32BE(0, 0);
  buffer.writeUInt32BE(counter, 4);
  const hmac = crypto.createHmac('sha1', base32Decode(secret)).update(buffer).digest();
  const offset = hmac[hmac.length - 1] & 0x0f;
  const code = ((hmac.readUInt32BE(offset) & 0x7fffffff) % 1000000).toString().padStart(6, '0');

  return code;
}

function parseLocation(response) {
  return response.headers()['location'] ?? response.headers()['Location'] ?? '';
}

function parseQueryValue(location, key) {
  try {
    return new URL(location, 'http://localhost').searchParams.get(key) ?? '';
  } catch {
    return '';
  }
}

function queryValueFromForms(forms, key) {
  for (const form of forms ?? []) {
    const value = parseQueryValue(form.action ?? '', key);
    if (value !== '') {
      return value;
    }
  }

  return '';
}

async function adminCustomerIdFromList(page, email) {
  return await page.evaluate((targetEmail) => {
    for (const row of document.querySelectorAll('tr')) {
      if (!row.textContent?.includes(targetEmail)) {
        continue;
      }

      const rowId = row.getAttribute('id') ?? '';
      if (rowId.startsWith('ex-customer-')) {
        return rowId.slice('ex-customer-'.length);
      }

      for (const anchor of row.querySelectorAll('a[href]')) {
        const href = anchor.getAttribute('href') ?? '';
        const customerId = new URL(href, window.location.href).searchParams.get('customerId');
        if (customerId) {
          return customerId;
        }
      }

      const firstCell = row.querySelector('td')?.textContent?.trim() ?? '';
      if (firstCell !== '') {
        return firstCell;
      }
    }

    return '';
  }, email).catch(() => '');
}

async function productRowAttr(page, productCode, selector, attrName) {
  return await page.evaluate(({ code, selector: rowSelector, attr }) => {
    const explicit = document.getElementById(`ex-product-${code}`);
    const rows = explicit ? [explicit] : Array.from(document.querySelectorAll('tr'));
    for (const row of rows) {
      if (!row.textContent?.includes(code)) {
        continue;
      }

      const element = row.querySelector(rowSelector);
      const value = element?.getAttribute(attr) ?? '';
      if (value !== '') {
        return value;
      }
    }

    return '';
  }, { code: productCode, selector, attr: attrName }).catch(() => '');
}

async function rowAttrByText(page, text, selector, attrName) {
  return await page.evaluate(({ text: rowText, selector: rowSelector, attr }) => {
    for (const row of document.querySelectorAll('tr, li')) {
      if (!row.textContent?.includes(rowText)) {
        continue;
      }

      const element = row.querySelector(rowSelector);
      const value = element?.getAttribute(attr) ?? '';
      if (value !== '') {
        return value;
      }
    }

    return '';
  }, { text, selector, attr: attrName }).catch(() => '');
}

async function rowIdByText(page, text, prefix, queryKey) {
  return await page.evaluate(({ text: rowText, prefix: rowIdPrefix, queryKey: key }) => {
    for (const row of document.querySelectorAll('tr, li')) {
      if (!row.textContent?.includes(rowText)) {
        continue;
      }

      const rowId = row.getAttribute('id') ?? '';
      if (rowIdPrefix !== '' && rowId.startsWith(rowIdPrefix)) {
        return rowId.slice(rowIdPrefix.length);
      }

      for (const element of row.querySelectorAll('a[href], a[data-url]')) {
        const rawUrl = element.getAttribute('href') || element.getAttribute('data-url') || '';
        try {
          const value = new URL(rawUrl, window.location.href).searchParams.get(key) ?? '';
          if (value !== '') {
            return value;
          }
        } catch {
          // Ignore malformed affordances and keep scanning the row.
        }
      }

      const firstCell = row.querySelector('td, .col-auto')?.textContent?.trim() ?? '';
      if (/^\d+$/.test(firstCell)) {
        return firstCell;
      }
    }

    return '';
  }, { text, prefix, queryKey }).catch(() => '');
}

async function jsonValue(response, key) {
  try {
    const body = await response.json();
    const value = body?.[key];

    return value === null || value === undefined ? '' : String(value);
  } catch {
    return '';
  }
}

function encodeFormBody(form) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(form)) {
    if (Array.isArray(value)) {
      for (const item of value) {
        params.append(key, String(item));
      }
      continue;
    }

    if (value !== null && value !== undefined) {
      params.append(key, String(value));
    }
  }

  return params.toString();
}

async function httpForm(context, baseUrl, method, target, form) {
  return await context.request.fetch(makeUrl(baseUrl, target), {
    method,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    data: encodeFormBody(form),
    maxRedirects: 0,
    timeout: NAV_TIMEOUT_MS,
  });
}

async function httpGet(context, baseUrl, target) {
  return await context.request.fetch(makeUrl(baseUrl, target), {
    method: 'GET',
    maxRedirects: 0,
    timeout: NAV_TIMEOUT_MS,
  });
}

function operationEvidenceEntry(method, route, response, detail = {}) {
  return {
    method,
    path: normalizeOpenApiPath(route),
    httpStatus: response.status(),
    location: parseLocation(response),
    ok: response.status() < 400,
    ...detail,
  };
}

function recordOperation(evidence, method, route, response, detail = {}) {
  evidence[operationKey(method, route)] = operationEvidenceEntry(method, route, response, detail);
}

function recordTargetOutOperation(evidence, method, route, reason, detail = {}) {
  evidence[operationKey(method, route)] = {
    method,
    path: normalizeOpenApiPath(route),
    httpStatus: null,
    location: '',
    ok: false,
    targetOut: true,
    reason,
    ...detail,
  };
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
  const csrfMode = testCase.csrfMode ?? 'normal';
  const csrf = csrfMode === 'normal'
    ? await getCsrf(page, makeUrl(baseUrl, testCase.setupPath ?? testCase.path))
    : '';
  const form = { ...(testCase.data ?? {}) };
  if (csrfMode === 'normal') {
    form.csrfToken = csrf;
  } else if (csrfMode === 'invalid') {
    form.csrfToken = 'invalid-csrf-token';
  }

  const response = await context.request.fetch(makeUrl(baseUrl, testCase.path), {
    method: testCase.method,
    form,
    maxRedirects: 0,
    timeout: NAV_TIMEOUT_MS,
  });
  const body = await response.text().catch(() => '');
  const readableBody = decodeResponseText(body);
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
      <pre>${escapeHtml(truncate(readableBody, 4000))}</pre>
    </body>
  `);
  await screenshot(page, screenshotFile);

  return {
    finalUrl: makeUrl(baseUrl, testCase.path),
    httpStatus: response.status(),
    title: testCase.id,
    h1: testCase.name,
    pageText: truncate(readableBody, 1800),
    errorText: truncate(extractErrorFromText(readableBody), 800),
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

function decodeResponseText(body) {
  const text = String(body);
  try {
    const decoded = JSON.parse(text);
    if (decoded && typeof decoded === 'object') {
      const parts = [];
      for (const key of ['message', 'error', 'detail', 'title']) {
        if (typeof decoded[key] === 'string' && decoded[key] !== '') {
          parts.push(decoded[key]);
        }
      }
      return parts.length > 0 ? `${parts.join(' ')}\n${text}` : JSON.stringify(decoded, null, 2);
    }
  } catch {
    // Non-JSON response.
  }

  return text;
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
      expectedText: ['入力してください。', '必須', 'Invalid input', 'Validation error'],
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
      expectedText: ['入力してください。', '必須', 'Invalid input'],
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
      data: { loginId: 'test-admin', password: 'local-dev-admin-password' },
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
      path: '/admin/product-csv',
      setupPath: '/admin/login',
      data: { csv: '' },
      expectedStatuses: [401, 403, 400, 404, 405],
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
  const browser = page.context().browser();
  for (const testCase of cases) {
    const caseContext = browser ? await browser.newContext({ acceptDownloads: true }) : context;
    const casePage = browser ? await caseContext.newPage() : page;
    try {
      const observation = testCase.mode === 'browserForm'
        ? await submitBrowserForm(casePage, baseUrl, testCase, screenshotDir)
        : await submitHttpRequest(casePage, caseContext, baseUrl, testCase, screenshotDir);

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
    } finally {
      if (browser) {
        await caseContext.close().catch(() => {});
      }
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

## Setup Evidence

- 管理ログイン: ${run.setup.adminLogin?.success ? 'pass' : 'fail'} final=\`${run.setup.adminLogin?.finalUrl ?? ''}\`
- 会員登録: ${run.setup.customerRegistration?.success ? 'pass' : 'fail'} final=\`${run.setup.customerRegistration?.finalUrl ?? ''}\`
- 業務状態作成: ${run.setup.businessState?.success ? 'pass' : 'fail'} product=\`${run.setup.businessState?.productCode ?? ''}\` memberOrder=\`${run.setup.businessState?.memberOrderNo ?? ''}\` nonMemberOrder=\`${run.setup.businessState?.nonMemberOrderNo ?? ''}\`
${(run.setup.businessState?.steps ?? []).map((item) => `- ${statusLabel(item.status)} setup:${item.id} final=\`${item.finalUrl ?? item.location ?? ''}\` screenshot=\`${item.screenshot ?? ''}\`${item.error ? ` error=${truncate(item.error, 180)}` : ''}`).join('\n')}

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
  const emailSlug = crypto
    .createHash('sha1')
    .update(`${runId}-${Date.now()}-${Math.random()}`)
    .digest('hex')
    .slice(0, 12);
  const email = `we-${emailSlug}@example.test`;
  const password = 'web-e2e-password-2026';
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
    await page.fill('[name="password"]', password);
    await page.fill('[name="password_confirm"]', password);
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
      password,
      finalUrl: page.url(),
      pageText: truncate((await snapshotPage(page)).pageText, 800),
      success: page.url().includes('/entry/complete') || page.url().includes('/mypage'),
    };
  } catch (error) {
    return {
      attempted: true,
      email,
      password,
      finalUrl: page.url(),
      success: false,
      error: `${error.name}: ${error.message}`,
    };
  }
}

async function maybeAdminLogin(page, context, baseUrl, knownTotpSecret = '') {
  try {
    await page.goto(makeUrl(baseUrl, '/admin/login'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    const loginCsrf = await readCsrf(page);
    const login = await httpForm(context, baseUrl, 'POST', '/admin/login', {
      loginId: 'test-admin',
      password: 'local-dev-admin-password',
      csrfToken: loginCsrf,
    });
    const loginLocation = parseLocation(login);
    if (loginLocation) {
      await page.goto(makeUrl(baseUrl, loginLocation), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    }
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    let twoFactorStep = null;
    let csrfToken = loginCsrf;
    let configuredTotpSecret = '';
    if (safePath(page.url()) === '/admin/two-factor-auth-set') {
      const authKey = await inputValue(page, '[name="authKey"]');
      configuredTotpSecret = authKey;
      csrfToken = await readCsrf(page) || csrfToken;
      twoFactorStep = { type: 'setup', authKeyPresent: authKey !== '' };
      const setup = await httpForm(context, baseUrl, 'POST', '/admin/two-factor-auth-set?_method=put', {
        deviceToken: totp(authKey),
        csrfToken,
      });
      const setupLocation = parseLocation(setup);
      twoFactorStep = {
        ...twoFactorStep,
        setupStatus: setup.status(),
        setupLocation,
        setupText: truncate(decodeResponseText(await setup.text().catch(() => '')), 300),
      };
      if (setupLocation) {
        await page.goto(makeUrl(baseUrl, setupLocation), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      }
      await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    } else if (safePath(page.url()) === '/admin/two-factor-auth') {
      csrfToken = await readCsrf(page) || csrfToken;
      const secret = knownTotpSecret || process.env.ADMIN_TOTP_SECRET || 'JBSWY3DPEHPK3PXP';
      twoFactorStep = {
        type: 'verify',
        secretSource: knownTotpSecret !== '' ? 'setup-memory' : (process.env.ADMIN_TOTP_SECRET ? 'env' : 'default-seed'),
      };
      const verify = await httpForm(context, baseUrl, 'POST', '/admin/two-factor-auth', {
        deviceToken: totp(secret),
        csrfToken,
      });
      const verifyLocation = parseLocation(verify);
      if (verifyLocation) {
        await page.goto(makeUrl(baseUrl, verifyLocation), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      }
      await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    }

    const snap = await snapshotPage(page);
    const result = {
      attempted: true,
      finalUrl: page.url(),
      success: page.url().includes('/admin/index'),
      csrfToken,
      twoFactorStep,
      pageText: truncate(snap.pageText, 800),
      errorText: snap.errorText,
    };
    if (configuredTotpSecret !== '') {
      Object.defineProperty(result, 'totpSecret', {
        value: configuredTotpSecret,
        enumerable: false,
      });
    }

    return result;
  } catch (error) {
    return {
      attempted: true,
      finalUrl: page.url(),
      success: false,
      error: `${error.name}: ${error.message}`,
    };
  }
}

async function maybeCustomerLogin(page, context, baseUrl, email, password) {
  if (!email || !password) {
    return { attempted: false, success: false, reason: 'customer registration did not produce credentials' };
  }

  try {
    await page.goto(makeUrl(baseUrl, '/login'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    const csrfToken = await readCsrf(page);
    const login = await httpForm(context, baseUrl, 'POST', '/login', {
      email,
      password,
      csrfToken,
    });
    const location = parseLocation(login);
    if (location) {
      await page.goto(makeUrl(baseUrl, location), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    }
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);

    return {
      attempted: true,
      success: safePath(page.url()) === '/mypage',
      finalUrl: page.url(),
      csrfToken,
      pageText: truncate(snap.pageText, 800),
      errorText: snap.errorText,
    };
  } catch (error) {
    return {
      attempted: true,
      success: false,
      finalUrl: page.url(),
      error: `${error.name}: ${error.message}`,
    };
  }
}

async function pageScreenshotStep(page, screenshotDir, id) {
  const file = path.join(screenshotDir, 'setup', `${sanitizeFileName(id)}.png`);
  await screenshot(page, file).catch(() => {});

  return relativeFromWebE2e(file);
}

async function maybeSeedBusinessState(page, context, baseUrl, runId, setup, screenshotDir) {
  const operationEvidence = {};
  const steps = [];
  const productCode = `we-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
  let productName = `Web E2E 完成判定 ${runId}`;
  const copiedProductCode = `${productCode}-copy`;
  const taxonomySuffix = Math.random().toString(36).slice(2, 8);
  const categoryName = `WE Category ${taxonomySuffix}`;
  const updatedCategoryName = `WE Category U ${taxonomySuffix}`;
  const tagName = `WE Tag ${taxonomySuffix}`;
  const paymentMaintenanceName = `Web E2E 支払CRUD ${taxonomySuffix}`;
  const updatedPaymentMaintenanceName = `Web E2E 支払CRUD 更新 ${taxonomySuffix}`;
  let paymentId = '';
  let paymentMaintenanceId = '';
  let csrfToken = setup.adminLogin?.csrfToken || '';
  let memberOrderNo = '';
  let nonMemberOrderNo = '';
  let confirmScreenshot = '';
  let addressId = '';
  let categoryId = '';
  let tagId = '';
  let adminCustomerId = '';
  let adminCustomerLocation = '';
  const adminCustomerEmail = `admin-customer-${runId}@example.test`;

  const step = async (id, fn) => {
    try {
      const detail = await fn();
      steps.push({ id, status: 'pass', ...detail });
      return detail;
    } catch (error) {
      const snap = await snapshotPage(page).catch(() => ({ pageText: '', errorText: '' }));
      const screenshotFile = await pageScreenshotStep(page, screenshotDir, id).catch(() => null);
      steps.push({
        id,
        status: 'fail',
        finalUrl: page.url(),
        error: `${error.name}: ${error.message}`,
        pageText: truncate(snap.errorText || snap.pageText, 800),
        screenshot: screenshotFile,
      });
      return null;
    }
  };

  await step('admin-payment-create', async () => {
    if (!setup.adminLogin?.success) {
      throw new Error('admin login failed');
    }

    const response = await httpForm(context, baseUrl, 'POST', '/admin/payment/payment-list', {
      paymentMethodName: `Web E2E 決済 ${runId}`,
      charge: '0',
      csrfToken,
    });
    paymentId = parseQueryValue(parseLocation(response), 'paymentId');
    recordOperation(operationEvidence, 'POST', '/admin/payment/payment-list', response, { paymentId });
    if (response.status() >= 400) {
      throw new Error(`payment create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    return { httpStatus: response.status(), location: parseLocation(response), paymentId };
  });

  await step('admin-payment-maintenance-create', async () => {
    await page.goto(makeUrl(baseUrl, '/admin/payment/payment'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = (await formAction(page, '#payment_edit_form')).replaceAll('&amp;', '&');
    if (!action.includes('/admin/payment/payment-list')) {
      throw new Error(`payment create form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      paymentMethodName: paymentMaintenanceName,
      charge: '110',
      ruleMin: '0',
      ruleMax: '999999',
      visible: '1',
      csrfToken,
    });
    const location = parseLocation(response);
    paymentMaintenanceId = await jsonValue(response, 'paymentId') || parseQueryValue(location, 'paymentId');
    recordOperation(operationEvidence, 'POST', '/admin/payment/payment-list', response, {
      paymentId: paymentMaintenanceId,
      paymentMethodName: paymentMaintenanceName,
      action,
    });
    if (response.status() >= 400 || !paymentMaintenanceId) {
      throw new Error(`payment maintenance create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || `/admin/payment/payment?paymentId=${encodeURIComponent(paymentMaintenanceId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const renderedName = await inputValue(page, 'input[name="paymentMethodName"]');
    if (renderedName !== paymentMaintenanceName) {
      throw new Error(`created payment was not visible on detail readback: ${renderedName || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('POST', '/admin/payment/payment-list')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-payment-maintenance-create');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      paymentId: paymentMaintenanceId,
      paymentMethodName: paymentMaintenanceName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-payment-maintenance-update', async () => {
    if (!paymentMaintenanceId) {
      throw new Error('payment maintenance id was not created');
    }

    await page.goto(makeUrl(baseUrl, `/admin/payment/payment?paymentId=${encodeURIComponent(paymentMaintenanceId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = (await formAction(page, '#payment_edit_form')).replaceAll('&amp;', '&');
    if (!action.includes('/admin/payment/payment') || !action.includes('_method=put')) {
      throw new Error(`payment update form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      _method: 'put',
      paymentId: paymentMaintenanceId,
      paymentMethodName: updatedPaymentMaintenanceName,
      charge: '120',
      ruleMin: '100',
      ruleMax: '999999',
      visible: '1',
      csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'PUT', '/admin/payment/payment', response, {
      paymentId: paymentMaintenanceId,
      paymentMethodName: updatedPaymentMaintenanceName,
      action,
    });
    if (response.status() >= 400) {
      throw new Error(`payment maintenance update failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || `/admin/payment/payment?paymentId=${encodeURIComponent(paymentMaintenanceId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const renderedName = await inputValue(page, 'input[name="paymentMethodName"]');
    if (renderedName !== updatedPaymentMaintenanceName) {
      throw new Error(`updated payment was not visible on detail readback: ${renderedName || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('PUT', '/admin/payment/payment')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-payment-maintenance-update');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      paymentId: paymentMaintenanceId,
      paymentMethodName: updatedPaymentMaintenanceName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-payment-maintenance-delete', async () => {
    if (!paymentMaintenanceId) {
      throw new Error('payment maintenance id was not created');
    }

    await page.goto(makeUrl(baseUrl, '/admin/payment/payment-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const deleteUrl = (await rowAttrByText(page, updatedPaymentMaintenanceName, 'a[data-url*="/admin/payment/payment"]', 'data-url')).replaceAll('&amp;', '&');
    const deleteToken = await page.locator('#DeleteModal [data-post-action="delete"]').first().getAttribute('token-for-anchor').catch(() => '') || '';
    if (!deleteUrl.includes('/admin/payment/payment')) {
      throw new Error(`payment delete URL was not exposed for ${paymentMaintenanceId}`);
    }

    const response = await httpForm(context, baseUrl, 'POST', deleteUrl, {
      _method: 'delete',
      csrfToken: deleteToken || csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'DELETE', '/admin/payment/payment', response, {
      paymentId: paymentMaintenanceId,
      paymentMethodName: updatedPaymentMaintenanceName,
      action: deleteUrl,
    });
    if (response.status() >= 400) {
      throw new Error(`payment maintenance delete failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || '/admin/payment/payment-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    if (snap.pageText.includes(updatedPaymentMaintenanceName)) {
      throw new Error('deleted payment was still visible on payment list');
    }

    const evidence = operationEvidence[operationKey('DELETE', '/admin/payment/payment')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-payment-maintenance-delete');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      paymentId: paymentMaintenanceId,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-product-create', async () => {
    const response = await httpForm(context, baseUrl, 'POST', '/admin/product', {
      productCode,
      productName,
      price02: '1200',
      stock: '20',
      productStatus: '1',
      description: 'Web+DB completion runner created this product through the admin HTTP boundary.',
      searchWord: '完成判定 音楽',
      note: 'web-e2e',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/admin/product', response, { productCode });
    if (response.status() >= 400) {
      throw new Error(`product create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    return { httpStatus: response.status(), location: parseLocation(response), productCode, productName };
  });

  await step('admin-product-readback', async () => {
    await page.goto(makeUrl(baseUrl, `/admin/product?productCode=${encodeURIComponent(productCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    csrfToken = await readCsrf(page) || csrfToken;
    const snap = await snapshotPage(page);
    if (!snap.pageText.includes(productCode) && !snap.pageText.includes(productName)) {
      throw new Error('created product was not visible on admin readback');
    }

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'admin-product-readback'),
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-product-update', async () => {
    await page.goto(makeUrl(baseUrl, `/admin/product?productCode=${encodeURIComponent(productCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = await formAction(page, '#admin_product_form') || await formAction(page, '#product_form');
    if (!action.includes('/admin/product')) {
      throw new Error(`product edit form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const updatedProductName = `${productName} 更新`;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      productCode,
      productName: updatedProductName,
      price02: '1350',
      stock: '18',
      productStatus: '1',
      description: 'Web+DB completion runner updated this product through the admin HTML form action.',
      searchWord: '完成判定 音楽 更新',
      note: 'web-e2e-updated',
      csrfToken,
    });
    recordOperation(operationEvidence, 'PUT', '/admin/product', response, { productCode, action });
    if (response.status() >= 400) {
      throw new Error(`product update failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    productName = updatedProductName;
    await page.goto(makeUrl(baseUrl, parseLocation(response) || `/admin/product?productCode=${encodeURIComponent(productCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    if (!snap.pageText.includes(productCode) && !snap.pageText.includes(productName)) {
      throw new Error('updated product was not visible on admin readback');
    }

    const evidence = operationEvidence[operationKey('PUT', '/admin/product')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-product-update');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location: parseLocation(response),
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      productCode,
      productName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-product-copy', async () => {
    await page.goto(makeUrl(baseUrl, `/admin/product-list?nameKeyword=${encodeURIComponent(productName)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const copyHref = await productRowAttr(page, productCode, 'a[href*="/admin/product-copy"]', 'href');
    const copyToken = await productRowAttr(page, productCode, 'a[href*="/admin/product-copy"]', 'token-for-anchor');
    if (!copyHref.includes('/admin/product-copy')) {
      throw new Error(`product copy link was not exposed for ${productCode}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', safePath(copyHref), {
      productCode,
      newProductCode: copiedProductCode,
      csrfToken: copyToken || csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/admin/product-copy', response, {
      productCode,
      newProductCode: copiedProductCode,
      action: safePath(copyHref),
    });
    if (response.status() >= 400) {
      throw new Error(`product copy failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, parseLocation(response) || `/admin/product?productCode=${encodeURIComponent(copiedProductCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const copiedProductName = `(コピー) ${productName}`;
    if (!page.url().includes(`productCode=${encodeURIComponent(copiedProductCode)}`) || !snap.pageText.includes(copiedProductName)) {
      throw new Error('copied product was not visible on admin readback');
    }

    const evidence = operationEvidence[operationKey('POST', '/admin/product-copy')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-product-copy');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location: parseLocation(response),
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      productCode,
      copiedProductCode,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-product-bulk-status', async () => {
    await page.goto(makeUrl(baseUrl, `/admin/product-list?nameKeyword=${encodeURIComponent(productName)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const actionButton = page.locator('button.action-submit[data-product-status="2"]').first();
    const action = await actionButton.getAttribute('data-action').catch(() => '') || '';
    const bulkToken = await actionButton.getAttribute('token-for-anchor').catch(() => '') || '';
    if (!action.includes('/admin/product-bulk-status')) {
      throw new Error(`product bulk-status action was not exposed: ${action || '(empty)'}`);
    }

    const response = await httpForm(context, baseUrl, 'POST', action, {
      'productCodes[]': copiedProductCode,
      productStatus: '2',
      csrfToken: bulkToken || csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/admin/product-bulk-status', response, {
      productCodes: [copiedProductCode],
      productStatus: 2,
      action,
    });
    if (response.status() >= 400) {
      throw new Error(`product bulk-status failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, `/admin/product?productCode=${encodeURIComponent(copiedProductCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const selectedStatus = await inputValue(page, 'select[name="productStatus"]');
    const snap = await snapshotPage(page);
    if (selectedStatus !== '2') {
      throw new Error(`bulk-updated copied product status was not visible on admin readback: ${selectedStatus || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('POST', '/admin/product-bulk-status')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-product-bulk-status');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      productCode: copiedProductCode,
      productStatus: selectedStatus,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-product-delete-copy', async () => {
    await page.goto(makeUrl(baseUrl, `/admin/product-list?nameKeyword=${encodeURIComponent(productName)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const deleteUrl = await productRowAttr(page, copiedProductCode, 'input[data-delete-url]', 'data-delete-url');
    const deleteToken = await productRowAttr(page, copiedProductCode, 'input[data-delete-url]', 'token-for-anchor');
    if (!deleteUrl.includes('/admin/product')) {
      throw new Error(`product delete URL was not exposed for ${copiedProductCode}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', deleteUrl, {
      csrfToken: deleteToken || csrfToken,
    });
    recordOperation(operationEvidence, 'DELETE', '/admin/product', response, {
      productCode: copiedProductCode,
      action: deleteUrl,
    });
    if (response.status() >= 400) {
      throw new Error(`product delete failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, `/admin/product?productCode=${encodeURIComponent(copiedProductCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const selectedStatus = await inputValue(page, 'select[name="productStatus"]');
    if (!page.url().includes(`productCode=${encodeURIComponent(copiedProductCode)}`) || selectedStatus !== '3') {
      throw new Error(`deleted copied product status was not visible on admin readback: ${selectedStatus || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('DELETE', '/admin/product')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-product-delete-copy');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      productCode: copiedProductCode,
      productStatus: selectedStatus,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-category-create', async () => {
    await page.goto(makeUrl(baseUrl, '/admin/category/category-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = await formAction(page, 'form[action="/admin/category/category-list"]');
    if (!action.includes('/admin/category/category-list')) {
      throw new Error(`category create form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      categoryName,
      sortNo: '90',
      csrfToken,
    });
    const location = parseLocation(response);
    categoryId = await jsonValue(response, 'categoryId') || parseQueryValue(location, 'categoryId');
    recordOperation(operationEvidence, 'POST', '/admin/category/category-list', response, {
      categoryId,
      categoryName,
      action,
    });
    if (response.status() >= 400 || !categoryId) {
      throw new Error(`category create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || `/admin/category/category?categoryId=${encodeURIComponent(categoryId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const renderedCategoryName = await inputValue(page, 'input[name="categoryName"]');
    if (renderedCategoryName !== categoryName) {
      throw new Error(`created category was not visible on detail readback: ${renderedCategoryName || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('POST', '/admin/category/category-list')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-category-create');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      categoryId,
      categoryName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-category-update', async () => {
    if (!categoryId) {
      throw new Error('category id was not created');
    }

    await page.goto(makeUrl(baseUrl, `/admin/category/category?categoryId=${encodeURIComponent(categoryId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = await formAction(page, 'form[action*="/admin/category/category"]');
    if (!action.includes('/admin/category/category')) {
      throw new Error(`category update form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      _method: 'put',
      categoryId,
      categoryName: updatedCategoryName,
      sortNo: '91',
      csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'PUT', '/admin/category/category', response, {
      categoryId,
      categoryName: updatedCategoryName,
      action,
    });
    if (response.status() >= 400) {
      throw new Error(`category update failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || `/admin/category/category?categoryId=${encodeURIComponent(categoryId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    const renderedCategoryName = await inputValue(page, 'input[name="categoryName"]');
    if (renderedCategoryName !== updatedCategoryName) {
      throw new Error(`updated category was not visible on detail readback: ${renderedCategoryName || '(empty)'}`);
    }

    const evidence = operationEvidence[operationKey('PUT', '/admin/category/category')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-category-update');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      categoryId,
      categoryName: updatedCategoryName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-category-delete', async () => {
    if (!categoryId) {
      throw new Error('category id was not created');
    }

    await page.goto(makeUrl(baseUrl, `/admin/category/category?categoryId=${encodeURIComponent(categoryId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const deleteHref = await page.locator('a[href*="/admin/category/category"][data-method="delete"]').first().getAttribute('href').catch(() => '') || '';
    const deleteToken = await page.locator('a[href*="/admin/category/category"][data-method="delete"]').first().getAttribute('token-for-anchor').catch(() => '') || '';
    if (!deleteHref.includes('/admin/category/category')) {
      throw new Error(`category delete href was not exposed for ${categoryId}`);
    }

    const response = await httpForm(context, baseUrl, 'POST', deleteHref, {
      csrfToken: deleteToken || csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'DELETE', '/admin/category/category', response, {
      categoryId,
      action: deleteHref,
    });
    if (response.status() >= 400) {
      throw new Error(`category delete failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || '/admin/category/category-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    if (snap.pageText.includes(updatedCategoryName)) {
      throw new Error('deleted category was still visible on category list');
    }

    const evidence = operationEvidence[operationKey('DELETE', '/admin/category/category')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-category-delete');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      categoryId,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-tag-create', async () => {
    await page.goto(makeUrl(baseUrl, '/admin/tag/tag-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const action = await formAction(page, 'form[action="/admin/tag/tag-list"]');
    if (!action.includes('/admin/tag/tag-list')) {
      throw new Error(`tag create form action was not exposed: ${action || '(empty)'}`);
    }

    csrfToken = await readCsrf(page) || csrfToken;
    const response = await httpForm(context, baseUrl, 'POST', action, {
      tagName,
      csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'POST', '/admin/tag/tag-list', response, {
      tagName,
      action,
    });
    if (response.status() >= 400) {
      throw new Error(`tag create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || '/admin/tag/tag-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    tagId = await jsonValue(response, 'tagId') || await rowIdByText(page, tagName, 'ex-tag-', 'tagId');
    const snap = await snapshotPage(page);
    if (!tagId || !snap.pageText.includes(tagName)) {
      throw new Error('created tag was not visible on tag list readback');
    }

    const evidence = operationEvidence[operationKey('POST', '/admin/tag/tag-list')];
    if (evidence) {
      evidence.tagId = tagId;
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-tag-create');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      tagId,
      tagName,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-tag-delete', async () => {
    if (!tagId) {
      throw new Error('tag id was not created');
    }

    await page.goto(makeUrl(baseUrl, '/admin/tag/tag-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const deleteUrl = await rowAttrByText(page, tagName, 'a[data-url*="/admin/tag/tag"]', 'data-url');
    const deleteToken = await page.locator('#DeleteModal [data-post-action="delete"]').first().getAttribute('token-for-anchor').catch(() => '') || '';
    if (!deleteUrl.includes('/admin/tag/tag')) {
      throw new Error(`tag delete URL was not exposed for ${tagId}`);
    }

    const response = await httpForm(context, baseUrl, 'POST', deleteUrl, {
      _method: 'delete',
      csrfToken: deleteToken || csrfToken,
    });
    const location = parseLocation(response);
    recordOperation(operationEvidence, 'DELETE', '/admin/tag/tag', response, {
      tagId,
      tagName,
      action: deleteUrl,
    });
    if (response.status() >= 400) {
      throw new Error(`tag delete failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, location || '/admin/tag/tag-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const snap = await snapshotPage(page);
    if (snap.pageText.includes(tagName)) {
      throw new Error('deleted tag was still visible on tag list');
    }

    const evidence = operationEvidence[operationKey('DELETE', '/admin/tag/tag')];
    if (evidence) {
      evidence.screenshot = await pageScreenshotStep(page, screenshotDir, 'admin-tag-delete');
      evidence.finalUrl = page.url();
      evidence.pageText = truncate(snap.pageText, 600);
    }

    return {
      httpStatus: response.status(),
      location,
      finalUrl: page.url(),
      screenshot: evidence?.screenshot ?? '',
      tagId,
      pageText: truncate(snap.pageText, 600),
    };
  });

  await step('admin-customer-create', async () => {
    const response = await httpForm(context, baseUrl, 'POST', '/admin/create-customer', {
      email: adminCustomerEmail,
      password: 'web-e2e-admin-customer-password-2026',
      name01: '管理',
      name02: '顧客',
      kana01: 'カンリ',
      kana02: 'コキャク',
      phoneNumber: '0312345678',
      postalCode: '1000001',
      pref: '13',
      addr01: '千代田区',
      addr02: '管理1-1',
      csrfToken,
    });
    adminCustomerLocation = parseLocation(response);
    adminCustomerId = await jsonValue(response, 'customerId')
      || parseQueryValue(adminCustomerLocation, 'customerId')
      || parseQueryValue(adminCustomerLocation, 'id');
    recordOperation(operationEvidence, 'POST', '/admin/create-customer', response, {
      customerId: adminCustomerId,
      email: adminCustomerEmail,
    });
    if (response.status() >= 400 || !adminCustomerLocation) {
      throw new Error(`admin customer create failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    return { httpStatus: response.status(), location: adminCustomerLocation, customerId: adminCustomerId };
  });

  await step('admin-customer-readback', async () => {
    if (!adminCustomerId && !adminCustomerLocation) {
      throw new Error('admin customer location was not created');
    }

    await page.goto(makeUrl(baseUrl, adminCustomerLocation || `/admin/customer?customerId=${encodeURIComponent(adminCustomerId)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    csrfToken = await readCsrf(page) || csrfToken;
    const snap = await snapshotPage(page);
    adminCustomerId ||= queryValueFromForms(snap.forms, 'customerId');
    if (!snap.pageText.includes(adminCustomerEmail) && !snap.pageText.includes(adminCustomerId)) {
      throw new Error('created admin customer was not visible on detail readback');
    }

    await page.goto(makeUrl(baseUrl, `/admin/customer-list?emailKeyword=${encodeURIComponent(adminCustomerEmail)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const listSnap = await snapshotPage(page);
    adminCustomerId ||= await adminCustomerIdFromList(page, adminCustomerEmail);
    if (!listSnap.pageText.includes(adminCustomerEmail) && !listSnap.pageText.includes(adminCustomerId)) {
      throw new Error('created admin customer was not visible on list readback');
    }
    if (!adminCustomerId) {
      throw new Error('created admin customer id was not visible in detail or list representation');
    }

    const createEvidence = operationEvidence[operationKey('POST', '/admin/create-customer')];
    if (createEvidence) {
      createEvidence.customerId = adminCustomerId;
    }

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'admin-customer-readback'),
      pageText: truncate(listSnap.pageText, 600),
    };
  });

  await step('admin-customer-delete', async () => {
    if (!adminCustomerId) {
      throw new Error('admin customer id was not created');
    }

    const response = await httpForm(context, baseUrl, 'POST', '/admin/delete-customer', {
      customerId: adminCustomerId,
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/admin/delete-customer', response, {
      customerId: adminCustomerId,
      email: adminCustomerEmail,
    });
    if (response.status() >= 400) {
      throw new Error(`admin customer delete failed status=${response.status()} body=${truncate(await response.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, `/admin/customer-list?emailKeyword=${encodeURIComponent(adminCustomerEmail)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const deleteSnap = await snapshotPage(page);
    if (deleteSnap.pageText.includes(adminCustomerEmail)) {
      throw new Error('deleted admin customer was still visible by original email');
    }

    return {
      httpStatus: response.status(),
      location: parseLocation(response),
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'admin-customer-delete'),
      pageText: truncate(deleteSnap.pageText, 600),
    };
  });

  await step('admin-logout', async () => {
    const browser = context.browser();
    if (!browser) {
      throw new Error('Playwright browser is not available for isolated admin logout context');
    }

    const adminContext = await browser.newContext();
    const adminPage = await adminContext.newPage();
    try {
      const login = await maybeAdminLogin(adminPage, adminContext, baseUrl, setup.adminLogin?.totpSecret ?? '');
      if (!login.success) {
        throw new Error(login.error || login.errorText || 'isolated admin login failed');
      }

      const logout = await httpForm(adminContext, baseUrl, 'POST', '/admin/logout', {
        csrfToken: login.csrfToken,
      });
      recordOperation(operationEvidence, 'POST', '/admin/logout', logout, {});
      if (logout.status() >= 400) {
        throw new Error(`admin logout failed status=${logout.status()} body=${truncate(await logout.text().catch(() => ''), 300)}`);
      }

      return {
        httpStatus: logout.status(),
        location: parseLocation(logout),
      };
    } finally {
      await adminContext.close().catch(() => {});
    }
  });

  await step('storefront-product-readback', async () => {
    await page.goto(makeUrl(baseUrl, `/products?name=${encodeURIComponent(productName)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const listSnap = await snapshotPage(page);
    if (!listSnap.pageText.includes(productName) && !listSnap.pageText.includes(productCode)) {
      throw new Error('created product was not visible on storefront search');
    }

    await page.goto(makeUrl(baseUrl, `/product?productCode=${encodeURIComponent(productCode)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const detailSnap = await snapshotPage(page);
    if (!detailSnap.pageText.includes(productName) && !detailSnap.pageText.includes(productCode)) {
      throw new Error('created product detail was not visible');
    }

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'storefront-product-readback'),
      pageText: truncate(detailSnap.pageText, 600),
    };
  });

  await step('non-member-purchase', async () => {
    const add = await httpForm(context, baseUrl, 'POST', '/cart/item', {
      productCode,
      quantity: '1',
      operation: 'add',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/cart/item', add, { productCode, scenario: 'non-member' });
    if (add.status() >= 400) {
      throw new Error(`cart add failed status=${add.status()} body=${truncate(await add.text().catch(() => ''), 300)}`);
    }

    const nonMember = await httpForm(context, baseUrl, 'POST', '/shopping/non-member', {
      name01: '山田',
      name02: '太郎',
      kana01: 'ヤマダ',
      kana02: 'タロウ',
      companyName: '',
      email: `non-member-${runId}@example.test`,
      email_confirm: `non-member-${runId}@example.test`,
      phoneNumber: '0312345678',
      postalCode: '1000001',
      pref: '13',
      addr01: '千代田区',
      addr02: '1-1',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/shopping/non-member', nonMember, { scenario: 'non-member' });
    const preOrderId = parseQueryValue(parseLocation(nonMember), 'preOrderId');
    if (!preOrderId) {
      throw new Error(`non-member submit did not return preOrderId status=${nonMember.status()} body=${truncate(await nonMember.text().catch(() => ''), 300)}`);
    }

    const confirm = await httpForm(context, baseUrl, 'POST', '/shopping/confirm', {
      preOrderId,
      payment: paymentId || '1',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/shopping/confirm', confirm, { preOrderId, scenario: 'non-member' });
    if (confirm.status() >= 400) {
      throw new Error(`non-member confirm failed status=${confirm.status()} body=${truncate(await confirm.text().catch(() => ''), 300)}`);
    }

    const checkout = await httpForm(context, baseUrl, 'POST', '/shopping/checkout', {
      preOrderId,
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/shopping/checkout', checkout, { preOrderId, scenario: 'non-member' });
    nonMemberOrderNo = parseQueryValue(parseLocation(checkout), 'orderNo');
    if (!nonMemberOrderNo) {
      throw new Error(`non-member checkout did not return orderNo status=${checkout.status()} body=${truncate(await checkout.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, `/shopping/complete?orderNo=${encodeURIComponent(nonMemberOrderNo)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    return {
      orderNo: nonMemberOrderNo,
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'non-member-purchase-complete'),
    };
  });

  const customerLogin = await step('customer-login', async () => {
    const loggedIn = await maybeCustomerLogin(page, context, baseUrl, setup.customerRegistration?.email, setup.customerRegistration?.password);
    if (!loggedIn.success) {
      throw new Error(loggedIn.error || loggedIn.reason || loggedIn.errorText || 'customer login failed');
    }

    return loggedIn;
  });

  await step('member-purchase-history-reorder', async () => {
    if (!customerLogin?.success) {
      throw new Error('customer login failed');
    }

    const add = await httpForm(context, baseUrl, 'POST', '/cart/item', {
      productCode,
      quantity: '1',
      operation: 'add',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/cart/item', add, { productCode, scenario: 'member' });
    if (add.status() >= 400) {
      throw new Error(`member cart add failed status=${add.status()} body=${truncate(await add.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, '/shopping'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    csrfToken = await readCsrf(page) || csrfToken;
    const preOrderId = await inputValue(page, '[name="preOrderId"]');
    const payment = await inputValue(page, '[name="payment"]:checked').catch(() => '') || paymentId || '1';
    if (!preOrderId) {
      const snap = await snapshotPage(page);
      throw new Error(`member shopping page did not expose preOrderId: ${truncate(snap.pageText, 400)}`);
    }

    const confirm = await httpForm(context, baseUrl, 'POST', '/shopping/confirm', {
      preOrderId,
      payment,
      csrfToken,
    });
    const confirmHtml = await confirm.text().catch(() => '');
    const confirmText = decodeResponseText(confirmHtml);
    confirmScreenshot = await (async () => {
      if (!confirmText.includes('<html') && !confirmText.includes('<!doctype')) {
        return '';
      }

      await page.setContent(confirmText, { waitUntil: 'domcontentloaded' });
      const snap = await snapshotPage(page);
      const file = await pageScreenshotStep(page, screenshotDir, 'shopping-confirm');
      operationEvidence[operationKey('POST', '/shopping/confirm')] = {
        method: 'POST',
        path: '/shopping/confirm',
        httpStatus: confirm.status(),
        location: parseLocation(confirm),
        ok: confirm.status() < 400,
        preOrderId,
        scenario: 'member',
        finalUrl: '/shopping/confirm',
        screenshot: file,
        title: snap.title,
        h1: snap.h1,
        pageText: truncate(snap.pageText, 1000),
        forms: snap.forms,
      };

      return file;
    })();
    if (!confirmScreenshot) {
      recordOperation(operationEvidence, 'POST', '/shopping/confirm', confirm, { preOrderId, scenario: 'member' });
    }
    if (confirm.status() >= 400) {
      throw new Error(`member confirm failed status=${confirm.status()} body=${truncate(confirmText, 300)}`);
    }

    const checkout = await httpForm(context, baseUrl, 'POST', '/shopping/checkout', {
      preOrderId,
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/shopping/checkout', checkout, { preOrderId, scenario: 'member' });
    memberOrderNo = parseQueryValue(parseLocation(checkout), 'orderNo');
    if (!memberOrderNo) {
      throw new Error(`member checkout did not return orderNo status=${checkout.status()} body=${truncate(await checkout.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, '/mypage/order-history'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    let snap = await snapshotPage(page);
    if (!snap.pageText.includes(memberOrderNo)) {
      throw new Error(`order history did not include ${memberOrderNo}`);
    }

    await page.goto(makeUrl(baseUrl, `/mypage/history?orderNo=${encodeURIComponent(memberOrderNo)}`), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    snap = await snapshotPage(page);
    if (!snap.pageText.includes(memberOrderNo)) {
      throw new Error(`order detail did not include ${memberOrderNo}`);
    }

    const reorder = await httpForm(context, baseUrl, 'POST', '/mypage/reorder', {
      orderNo: memberOrderNo,
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/mypage/reorder', reorder, { orderNo: memberOrderNo });
    if (reorder.status() >= 400) {
      throw new Error(`reorder failed status=${reorder.status()} body=${truncate(await reorder.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, '/cart'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    return {
      orderNo: memberOrderNo,
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'member-reorder-cart'),
    };
  });

  await step('cart-quantity-and-delete', async () => {
    const add = await httpForm(context, baseUrl, 'POST', '/cart/item', {
      productCode,
      quantity: '1',
      operation: 'add',
      csrfToken,
    });
    if (add.status() >= 400) {
      throw new Error(`cart setup add failed status=${add.status()} body=${truncate(await add.text().catch(() => ''), 300)}`);
    }

    const update = await httpForm(context, baseUrl, 'POST', '/cart/item?_method=put', {
      productCode,
      quantity: '2',
      csrfToken,
    });
    recordOperation(operationEvidence, 'PUT', '/cart/item', update, { productCode, quantity: '2' });
    if (update.status() >= 400) {
      throw new Error(`cart quantity update failed status=${update.status()} body=${truncate(await update.text().catch(() => ''), 300)}`);
    }

    const remove = await httpForm(context, baseUrl, 'POST', '/cart/item?_method=delete', {
      productCode,
      csrfToken,
    });
    recordOperation(operationEvidence, 'DELETE', '/cart/item', remove, { productCode });
    if (remove.status() >= 400) {
      throw new Error(`cart delete failed status=${remove.status()} body=${truncate(await remove.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, '/cart'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'cart-quantity-and-delete'),
    };
  });

  await step('customer-profile-favorite-address', async () => {
    if (!customerLogin?.success) {
      throw new Error('customer login failed');
    }

    await page.goto(makeUrl(baseUrl, '/mypage/change'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    csrfToken = await readCsrf(page) || csrfToken;
    const updatedProfile = await httpForm(context, baseUrl, 'POST', '/mypage/change', {
      email: setup.customerRegistration?.email,
      name01: '山田',
      name02: '更新',
      kana01: 'ヤマダ',
      kana02: 'コウシン',
      phoneNumber: '0312345678',
      postalCode: '1000001',
      pref: '13',
      addr01: '千代田区',
      addr02: '2-2',
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/mypage/change', updatedProfile, { email: setup.customerRegistration?.email });
    if (updatedProfile.status() >= 400) {
      throw new Error(`profile update failed status=${updatedProfile.status()} body=${truncate(await updatedProfile.text().catch(() => ''), 300)}`);
    }

    const favoriteAdd = await httpForm(context, baseUrl, 'POST', '/mypage/favorite', {
      productCode,
      csrfToken,
    });
    recordOperation(operationEvidence, 'POST', '/mypage/favorite', favoriteAdd, { productCode });
    if (favoriteAdd.status() >= 400) {
      throw new Error(`favorite add failed status=${favoriteAdd.status()} body=${truncate(await favoriteAdd.text().catch(() => ''), 300)}`);
    }

    const favoriteRemove = await httpForm(context, baseUrl, 'POST', '/mypage/favorite?_method=delete', {
      productCode,
      csrfToken,
    });
    recordOperation(operationEvidence, 'DELETE', '/mypage/favorite', favoriteRemove, { productCode });
    if (favoriteRemove.status() >= 400) {
      throw new Error(`favorite remove failed status=${favoriteRemove.status()} body=${truncate(await favoriteRemove.text().catch(() => ''), 300)}`);
    }

    const addressCreate = await httpForm(context, baseUrl, 'POST', '/mypage/address-list', {
      name01: '佐藤',
      name02: '配送',
      kana01: 'サトウ',
      kana02: 'ハイソウ',
      phoneNumber: '0311112222',
      postalCode: '1000001',
      pref: '13',
      addr01: '千代田区',
      addr02: '3-3',
      csrfToken,
    });
    addressId = await jsonValue(addressCreate, 'addressId');
    if (!addressId) {
      await page.goto(makeUrl(baseUrl, parseLocation(addressCreate) || '/mypage/address-list'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
      addressId = await page.evaluate(() => {
        const link = document.querySelector('a[href*="/mypage/address?addressId="]');
        const href = link?.getAttribute('href') ?? '';
        try {
          return new URL(href, window.location.href).searchParams.get('addressId') ?? '';
        } catch {
          return '';
        }
      });
    }
    recordOperation(operationEvidence, 'POST', '/mypage/address-list', addressCreate, { addressId });
    if (addressCreate.status() >= 400 || !addressId) {
      throw new Error(`address create failed status=${addressCreate.status()} body=${truncate(await addressCreate.text().catch(() => ''), 300)}`);
    }

    const addressUpdate = await httpForm(context, baseUrl, 'POST', '/mypage/address?_method=put', {
      addressId,
      name01: '佐藤',
      name02: '更新',
      kana01: 'サトウ',
      kana02: 'コウシン',
      phoneNumber: '0311113333',
      postalCode: '1000001',
      pref: '13',
      addr01: '千代田区',
      addr02: '4-4',
      csrfToken,
    });
    recordOperation(operationEvidence, 'PUT', '/mypage/address', addressUpdate, { addressId });
    if (addressUpdate.status() >= 400) {
      throw new Error(`address update failed status=${addressUpdate.status()} body=${truncate(await addressUpdate.text().catch(() => ''), 300)}`);
    }

    const addressDelete = await httpForm(context, baseUrl, 'POST', '/mypage/address?_method=delete', {
      addressId,
      csrfToken,
    });
    recordOperation(operationEvidence, 'DELETE', '/mypage/address', addressDelete, { addressId });
    if (addressDelete.status() >= 400) {
      throw new Error(`address delete failed status=${addressDelete.status()} body=${truncate(await addressDelete.text().catch(() => ''), 300)}`);
    }

    await page.goto(makeUrl(baseUrl, '/mypage'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'customer-profile-favorite-address'),
    };
  });

  await step('customer-logout-and-relogin', async () => {
    const logout = await httpForm(context, baseUrl, 'POST', '/logout', { csrfToken });
    recordOperation(operationEvidence, 'POST', '/logout', logout, {});
    if (logout.status() >= 400) {
      throw new Error(`logout failed status=${logout.status()} body=${truncate(await logout.text().catch(() => ''), 300)}`);
    }

    const relogin = await maybeCustomerLogin(page, context, baseUrl, setup.customerRegistration?.email, setup.customerRegistration?.password);
    if (!relogin.success) {
      throw new Error(relogin.error || relogin.reason || relogin.errorText || 'customer relogin failed');
    }

    csrfToken = await readCsrf(page) || csrfToken;

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'customer-logout-and-relogin'),
    };
  });

  await step('contact-submit', async () => {
    await page.goto(makeUrl(baseUrl, '/contact'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const contactCsrf = await readCsrf(page) || csrfToken;
    const contact = await httpForm(context, baseUrl, 'POST', '/contact', {
      contactName01: '問い合わせ',
      contactName02: '太郎',
      contactEmail: `contact-${runId}@example.test`,
      contactContents: 'Web+DB completion runner contact submit boundary.',
      mode: 'confirm',
      csrfToken: contactCsrf,
    });
    recordOperation(operationEvidence, 'POST', '/contact', contact, {});
    if (contact.status() >= 400) {
      throw new Error(`contact submit failed status=${contact.status()} body=${truncate(await contact.text().catch(() => ''), 300)}`);
    }

    const location = parseLocation(contact);
    if (location) {
      await page.goto(makeUrl(baseUrl, location), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    }

    return {
      finalUrl: page.url(),
      screenshot: await pageScreenshotStep(page, screenshotDir, 'contact-submit'),
    };
  });

  await step('password-reset-request', async () => {
    await page.goto(makeUrl(baseUrl, '/forgot-password'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
    const forgotCsrf = await readCsrf(page) || csrfToken;
    const forgot = await httpForm(context, baseUrl, 'POST', '/forgot-password', {
      email: setup.customerRegistration?.email,
      csrfToken: forgotCsrf,
    });
    recordOperation(operationEvidence, 'POST', '/forgot-password', forgot, { email: setup.customerRegistration?.email });
    if (forgot.status() >= 400) {
      throw new Error(`forgot password request failed status=${forgot.status()} body=${truncate(await forgot.text().catch(() => ''), 300)}`);
    }

    recordTargetOutOperation(
      operationEvidence,
      'POST',
      '/reset',
      'Password reset requires a resetKey delivered through the external SMTP/noop mail boundary; the request boundary is covered by POST /forgot-password.',
      { dependsOn: 'POST /forgot-password' },
    );

    return {
      httpStatus: forgot.status(),
      location: parseLocation(forgot),
    };
  });

  await step('customer-withdraw', async () => {
    const browser = context.browser();
    if (!browser) {
      throw new Error('Playwright browser is not available for isolated withdraw context');
    }

    const withdrawContext = await browser.newContext();
    const withdrawPage = await withdrawContext.newPage();
    try {
      const registration = await maybeRegisterCustomer(withdrawPage, baseUrl, `${runId}-withdraw`);
      if (!registration.success) {
        throw new Error(registration.error || registration.errorText || 'withdraw customer registration failed');
      }

      const login = await maybeCustomerLogin(
        withdrawPage,
        withdrawContext,
        baseUrl,
        registration.email,
        registration.password,
      );
      if (!login.success) {
        throw new Error(
          login.error
          || login.reason
          || login.errorText
          || `withdraw customer login failed final=${login.finalUrl ?? ''} text=${truncate(login.pageText ?? '', 300)}`,
        );
      }

      await withdrawPage.goto(makeUrl(baseUrl, '/mypage/withdraw'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      await withdrawPage.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
      const withdrawCsrf = await readCsrf(withdrawPage) || login.csrfToken;
      const withdraw = await httpForm(withdrawContext, baseUrl, 'POST', '/mypage/withdraw', { csrfToken: withdrawCsrf });
      recordOperation(operationEvidence, 'POST', '/mypage/withdraw', withdraw, { email: registration.email });
      if (withdraw.status() >= 400) {
        throw new Error(`withdraw failed status=${withdraw.status()} body=${truncate(await withdraw.text().catch(() => ''), 300)}`);
      }

      await withdrawPage.goto(makeUrl(baseUrl, '/mypage/withdraw-complete'), { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
      await withdrawPage.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

      return {
        finalUrl: withdrawPage.url(),
        screenshot: await pageScreenshotStep(withdrawPage, screenshotDir, 'customer-withdraw'),
      };
    } finally {
      await withdrawContext.close().catch(() => {});
    }
  });

  const failed = steps.filter((item) => item.status === 'fail');
  recordTargetOutOperation(
    operationEvidence,
    'POST',
    '/entry/activate',
    'Customer activation requires a secretKey delivered through the external SMTP/noop mail boundary; customer registration itself is covered by POST /entry.',
    { dependsOn: 'POST /entry' },
  );

  return {
    attempted: true,
    success: failed.length === 0,
    productCode,
    productName,
    paymentId,
    paymentMaintenanceId,
    categoryId,
    tagId,
    memberOrderNo,
    nonMemberOrderNo,
    confirmScreenshot,
    operationEvidence,
    steps,
  };
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
    adminLogin: await maybeAdminLogin(page, context, baseUrl),
  };
  setup.customerRegistration = await maybeRegisterCustomer(page, baseUrl, runId);
  setup.businessState = await maybeSeedBusinessState(page, context, baseUrl, runId, setup, screenshotDir);
  if (setup.adminLogin?.success) {
    setup.businessState.operationEvidence[operationKey('POST', '/admin/login')] = {
      method: 'POST',
      path: '/admin/login',
      httpStatus: 200,
      location: setup.adminLogin.finalUrl,
      ok: true,
      scenario: 'admin setup login',
    };
  }
  if ((setup.adminLogin?.twoFactorStep?.setupStatus ?? 500) < 400) {
    setup.businessState.operationEvidence[operationKey('PUT', '/admin/two-factor-auth-set')] = {
      method: 'PUT',
      path: '/admin/two-factor-auth-set',
      httpStatus: setup.adminLogin.twoFactorStep.setupStatus,
      location: setup.adminLogin.twoFactorStep.setupLocation,
      ok: true,
      scenario: 'admin 2FA setup',
    };
  }
  if (setup.customerRegistration?.success) {
    setup.businessState.operationEvidence[operationKey('POST', '/entry')] = {
      method: 'POST',
      path: '/entry',
      httpStatus: 303,
      location: setup.customerRegistration.finalUrl,
      ok: true,
      scenario: 'customer registration setup',
    };
  }
  if (setup.businessState.steps.some((step) => step.id === 'customer-login' && step.status === 'pass')) {
    setup.businessState.operationEvidence[operationKey('POST', '/login')] = {
      method: 'POST',
      path: '/login',
      httpStatus: 303,
      location: '/mypage',
      ok: true,
      scenario: 'customer setup login',
    };
  }

  const featureRows = limit ? matrixRows.slice(0, limit) : matrixRows;
  const results = [];
  for (const row of featureRows) {
    const result = await visitFeature(page, baseUrl, row, baselineByNo.get(row.no), screenshotDir, setup);
    results.push(result);
    process.stdout.write(`${String(row.no).padStart(3, '0')} ${result.webResult} ${row.section} ${row.feature}\n`);
  }

  if (limit && matrixRows.length > limit) {
    for (const row of matrixRows.slice(limit)) {
      results.push({
        ...row,
        status: 'fail',
        webResult: '✘ fail（--limit により未実行）',
        targetUrl: deriveTargetUrl(row, baselineByNo.get(row.no), setup),
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
      databaseUrl: redactDatabaseUrl(process.env.DATABASE_URL || ''),
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

function redactDatabaseUrl(databaseUrl) {
  if (!databaseUrl) {
    return '';
  }

  try {
    const parsed = new URL(databaseUrl);
    if (parsed.password) {
      parsed.password = '***';
    }

    return parsed.toString();
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
