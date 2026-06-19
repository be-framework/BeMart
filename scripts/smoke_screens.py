#!/usr/bin/env python3
"""Full-screen smoke sweep for the BeMart HTML server.

Derives every onGet page URL from src/Resource/Page, logs in (admin + customer),
GETs every page, and flags real defects. Use it after a pull / branch switch /
template or seed change to confirm no screen regressed — the browser round-trip
coverage that the JSON/resource test suites do not exercise.

    python3 scripts/smoke_screens.py                 # against http://localhost:8081
    BASE_URL=http://127.0.0.1:8081 python3 scripts/smoke_screens.py

Exit code is non-zero if any HARD defect is found:
  - HTTP 5xx / fetch error
  - mojibake (double-encoded Japanese, e.g. クレジットカード -> ã‚¯ãƒ¬ã‚¸...)
  - an empty <input name="csrfToken"> in a form (a save that would 403)
  - "Invalid input" (a response that failed its JSON Schema)
  - an uncaught exception / fatal error in the body

SOFT findings (reported, do not fail the run): by-design 303 redirects, and 4xx
for not-yet-seeded ids or validation/authz on synthetic params.

Env overrides: BASE_URL, ADMIN_ID, ADMIN_PW, DEV_2FA_TOKEN, CUST_EMAIL, CUST_PW.
Needs the dev server running with BEMART_DEV_LOGIN=1 (for the 123456 2FA bypass).
"""
import os, re, sys, pathlib, urllib.request, urllib.parse, urllib.error, http.cookiejar

BASE = os.environ.get('BASE_URL', 'http://localhost:8081').rstrip('/')
ADMIN_ID = os.environ.get('ADMIN_ID', 'test-admin')
ADMIN_PW = os.environ.get('ADMIN_PW', 'local-dev-admin-password')
DEV_2FA = os.environ.get('DEV_2FA_TOKEN', '123456')
CUST_EMAIL = os.environ.get('CUST_EMAIL', 'login-test@example.com')
CUST_PW = os.environ.get('CUST_PW', 'local-dev-member-password')
ROOT = pathlib.Path(__file__).resolve().parents[1]

# Best-effort values for required onGet params, using the dev seed catalogue.
PARAM = {
    'paymentId': '2', 'customerId': '4', 'productCode': 'sample-001', 'deliveryId': '1',
    'orderNo': 'deadbeefcafe1234567890ab', 'categoryId': '1', 'classNameId': '1',
    'classCategoryId': '1', 'tagId': '1', 'newsId': '1', 'pageId': '1', 'blockId': '1',
    'memberId': '1', 'taxRuleId': '1', 'layoutId': '1', 'templateId': '1',
    'paymentMethodId': '2', 'id': '1', 'authorityRoleId': '1', 'mailTemplateId': '1',
    'productClassId': '1', 'preOrderId': 'x',
}
MOJI_MARKERS = ['ãƒ', 'ã‚', 'â€', 'Ã£', 'Â¥']


def kebab(s):
    return re.sub(r'(?<!^)(?=[A-Z])', '-', s).lower()


def url_for(rel):  # "Page/Admin/Payment/Payment" -> "/admin/payment/payment"
    return '/' + '/'.join(kebab(p) for p in rel.split('/')[1:])


def parse_onget(text):
    m = re.search(r'function onGet\s*\(([^)]*)\)', text, re.S)
    if not m:
        return None
    required, every = [], []
    for chunk in m.group(1).split(','):
        pm = re.search(r'\$(\w+)', chunk)
        if not pm:
            continue
        every.append(pm.group(1))
        if '=' not in chunk:
            required.append(pm.group(1))
    return required, every


def make_opener():
    cj = http.cookiejar.CookieJar()

    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k):
            return None

    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), NoRedirect)


def fetch(op, method, url, data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    req = urllib.request.Request(url, data=body, method=method)
    try:
        r = op.open(req, timeout=20)
        return r.getcode(), r.read().decode('utf-8', 'replace'), dict(r.headers)
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', 'replace'), dict(e.headers)
    except Exception as e:  # noqa: BLE001 — report connection failures as a hard defect
        return -1, repr(e), {}


def form_csrf(html):
    tag = re.search(r'<input[^>]*name="csrfToken"[^>]*>', html)
    if not tag:
        return ''
    v = re.search(r'value="([^"]*)"', tag.group(0))
    return v.group(1) if v else ''


def main():
    admin, cust = make_opener(), make_opener()

    _, h, _ = fetch(admin, 'GET', BASE + '/admin/login')
    fetch(admin, 'POST', BASE + '/admin/login',
          {'loginId': ADMIN_ID, 'password': ADMIN_PW, 'csrfToken': form_csrf(h)})
    _, h2, _ = fetch(admin, 'GET', BASE + '/admin/two-factor-auth')
    fetch(admin, 'POST', BASE + '/admin/two-factor-auth',
          {'deviceToken': DEV_2FA, 'csrfToken': form_csrf(h2)})
    _, hc, _ = fetch(cust, 'GET', BASE + '/login')
    fetch(cust, 'POST', BASE + '/login',
          {'email': CUST_EMAIL, 'password': CUST_PW, 'mode': 'login', 'csrfToken': form_csrf(hc)})

    urls = []
    for f in sorted((ROOT / 'src/Resource/Page').rglob('*.php')):
        pg = parse_onget(f.read_text(encoding='utf-8'))
        if pg is None:
            continue
        rel = str(f.relative_to(ROOT / 'src/Resource')).removesuffix('.php')
        path = url_for(rel)
        if 'logout' in path:
            continue
        required, every = pg
        q = {n: PARAM[n] for n in every if n in PARAM}
        q.update({n: '1' for n in required if n not in PARAM})
        urls.append((path + ('?' + urllib.parse.urlencode(q) if q else ''), path))

    hard, soft, clean = [], [], 0
    for full, path in urls:
        op = admin if path.startswith('/admin') else cust
        code, body, hdr = fetch(op, 'GET', BASE + full)
        hf, sf = [], []
        if code == -1:
            hf.append('FETCH-ERR')
        elif code >= 500:
            hf.append(f'HTTP{code}')
        elif 300 <= code < 400:
            sf.append(f'{code}->{hdr.get("Location", "?")}')
        elif 400 <= code < 500:
            sf.append(f'HTTP{code}')
        if any(m in body for m in MOJI_MARKERS):
            hf.append('MOJIBAKE')
        if any('value=""' in t for t in re.findall(r'<input[^>]*name="csrfToken"[^>]*>', body)):
            hf.append('EMPTY-CSRF')
        if 'Invalid input' in body:
            hf.append('INVALID-INPUT')
        if any(s in body for s in ('Uncaught', 'Stack trace', 'Fatal error')):
            hf.append('EXCEPTION')
        if hf:
            hard.append((full, code, hf + sf))
        elif sf:
            soft.append((full, code, sf))
        else:
            clean += 1

    print(f'swept {len(urls)} pages against {BASE}\n')
    if hard:
        print('=== HARD DEFECTS ===')
        for full, code, flags in hard:
            print(f'  [{",".join(flags)}]  {full}')
    print('\n=== SOFT (expected: redirects / missing-seed / validation) ===' if soft else '')
    for full, code, flags in soft:
        print(f'  [{",".join(flags)}]  {full}')
    print(f'\nsummary: total={len(urls)} clean={clean} soft={len(soft)} hard={len(hard)}')
    return 1 if hard else 0


if __name__ == '__main__':
    sys.exit(main())
