# BeMart spec-coverage browser harness

Drives the **real** BeMart HTML app with a real browser, **performs** each
EC-CUBE 結合試験 item's operations (not GET substitutes), and captures
evidence a separate verifier model can judge:

| file | for | purpose |
|---|---|---|
| `<id>.png` | humans | full-page screenshot — visual judgement |
| `<id>.html` | machines | rendered DOM — grep/assert against 期待結果 |
| `browser-run.jsonl` | both | one record/item: `http_status`, `url`, `verifier_status:null`, `error?` |

The executor records evidence only (`verifier_status` stays `null`); a
stronger model judges evidence vs the 期待結果 in a later pass.

## Why a browser (not curl)

The earlier curl/GET run proved nothing about behaviour — every "step" was a
GET, HTTP 200 ≠ 期待結果. This harness clicks, types and submits like a user,
and screenshots the result. Three BeMart-specific facts are baked in so the
executor never has to rediscover them:

1. **Login form is server-prefilled** (PoC: `test-admin` / `local-dev-admin-password`).
   Typing must **replace** the value, not append — `fill()` clears first.
   (Typing onto the prefill produced `test-admintest-admin` → 401.)
2. **State transitions are PRG as `200 + Location`, not `302`.** Browsers do
   not auto-follow a `Location` on a 200. `submitFollow()` chases it manually.
   (Login → `/admin/two-factor-auth` → `/admin/index` are two such hops.)
3. **2FA is bypassed in dev** by `BEMART_DEV_LOGIN=1` (the `serve:page:dev`
   script): any admin logs in with the magic code **`123456`**.

## Prerequisites

```bash
# 1) HTML app with the dev 2FA bypass (serves on 127.0.0.1:8081)
composer serve:page:dev      # = BEMART_DEV_LOGIN=1 ... php -S 127.0.0.1:8081 -t public public/page.php

# 2) the admin row the login PoC expects must exist in the server's DATABASE_URL db.
#    serve:page:dev defaults to eccubedb; analysis-sample seeds admin1..5 (SHA256, NOT loginnable),
#    so load the bcrypt test-admin row (idempotent UPSERT):
mysql -h127.0.0.1 -uroot eccubedb < sql/seed/dtb-system-master.sql

# 3) this harness's dependency (puppeteer-core drives the system Chrome; no Chromium download)
cd docs/eccube-spec-coverage/harness && npm install
```

Needs Google Chrome installed (default
`/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`; override with
`CHROME_BIN`).

## Run

```bash
cd docs/eccube-spec-coverage/harness
node run.cjs                 # uses scenarios.json
node run.cjs my-scenarios.json
```

Evidence lands in `../evidence-browser/` (override with `OUT`). Env overrides:
`BEMART_BASE`, `CHROME_BIN`, `ADMIN_ID`, `ADMIN_PW`, `DEV_2FA`.

## Scenario format

`scenarios.json` is an array. One object per test item:

```json
{ "area": "EA", "item_id": "product-list", "title": "商品マスター 一覧",
  "auth": "admin",
  "steps": [ { "action": "goto", "url": "/admin/product-list" } ] }
```

- `area` + `item_id` → evidence filename `<area>-<item_id>.{png,html}`. Use the
  結合試験 item id (e.g. `EA03-1`) so the verifier can map evidence → spec.
- `auth`: `"admin"` logs in (per item) before the steps; `"none"` for front /
  anonymous items. **Each item runs in an isolated session** — cookies are
  cleared between items, so cart/login state never leaks from a prior scenario
  (every 結合試験 item is an independent test). Do not rely on state from an
  earlier scenario; establish what the item needs inside its own `steps`.
- `title`: human label, copied into the JSONL record.

### Step actions (the operation vocabulary)

| action | fields | does |
|---|---|---|
| `goto` | `url` | navigate (records HTTP status) |
| `fill` | `sel`, `value` | clear + type into an input |
| `select` | `sel`, `value` | choose a `<select>` option |
| `check` | `sel` | tick a checkbox/radio |
| `click` | `sel` | click + wait for any navigation |
| `submitFollow` | `sel?` | submit (default submit button) + follow BeMart PRG |
| `clickText` | `text` | click a button/link by **visible text** (regex) + follow PRG — for flow buttons with no stable selector (レジに進む / ゲスト購入 / 次へ / 注文する) |
| `selectFirst` | `sel` | choose the first non-empty `<option>` (e.g. 都道府県 `select[name=pref]`) |
| `wait` | `sel`, `timeout?` | wait for a selector |

The proven **guest checkout** chain (cart→レジ→ゲスト→お客様情報→確認→注文する, which
creates a real order) lives in [`scenarios-checkout.json`](scenarios-checkout.json) — clone it
for the EF0305 variations.

The `EA-login` scenario in `scenarios.json` is the canonical multi-step
operation template (goto → fill → fill → submitFollow → fill → submitFollow).

## Scaling to the full 248-item suite

`scenarios.json` ships a proven starter set (front screens, the admin login
operation, 4 admin list screens). To cover all 17 項目書 / 248 items, translate
each item's 操作手順 (in `../all_items.json`) into `steps`. Screen-display items
are one `goto`; state-changing items (商品登録, 受注編集, 会員登録, カート投入→
注文) are multi-step `fill`/`select`/`submitFollow` chains. Data-dependent admin
screens need sample products/orders/customers seeded first (eccubedb ships none,
so lists render empty — valid evidence, but not a populated-state check).
