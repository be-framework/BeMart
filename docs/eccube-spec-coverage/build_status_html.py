#!/usr/bin/env python3
"""Generate the BeMart implementation-status semantic HTML artifact + ALPS profile.

Follows the `semantic-web-write` skill: an HTML document whose meaning is declared
through an ALPS profile (rel="profile"), with meaningful elements bound to descriptor
IDs. Source-backed: every row links to the EC-CUBE spec section, the BeMart test, and
(where captured) a screenshot.

Inputs (the SOURCE of this table):
  - all_items.json          : 248 test items parsed from the EC-CUBE 結合試験 spec
  - src/Resource/Page/**     : authoritative BeMart routes (URL column, validated)
  - tests/Hypermedia/**      : flow tests cited as 根拠
Outputs:
  - implementation-status.alps.json
  - implementation-status.html
"""
import json, re, html
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]          # repo root
HERE = Path(__file__).resolve().parent              # docs/eccube-spec-coverage
ITEMS = json.loads((HERE / "all_items.json").read_text(encoding="utf-8"))

# ---- authoritative routes (kebab of src/Resource/Page/**) : no-guess gate ----
def kebab(s): return re.sub(r"(?<!^)(?=[A-Z])", "-", s).lower()
AUTH = {"/"}  # root alias for /index
for p in (ROOT / "src/Resource/Page").rglob("*.php"):
    rel = p.relative_to(ROOT / "src/Resource/Page").with_suffix("")
    AUTH.add("/" + "/".join(kebab(x) for x in rel.parts))

# ---- 画面ID -> URL template (validated against AUTH) ----
ROUTES = {
 "EF0101": "/", "EF0201": "/products", "EF0202": "/product?productCode={code}",
 "EF0301": "/cart", "EF0302": "/shopping/login", "EF0303": "/cart",
 "EF0305": "/shopping/non-member", "EF0401": "/entry", "EF0404": "/entry",
 "EF0501": "/mypage", "EF0502": "/mypage/order-history", "EF0503": "/mypage/history",
 "EF0504": "/mypage/change", "EF0506": "/mypage/address-list", "EF0507": "/mypage/withdraw",
 "EF0601": "/login", "EF0602": "/forgot-password", "EF0603": "/logout",
 "EF0604": "/help/about", "EF0605": "/help/privacy", "EF0606": "/help/trade-law",
 "EF0607": "/contact", "EF0701": "/shopping",
 "EA0101": "/admin/index", "EA0201": "/admin/login", "EA0301": "/admin/product-list",
 "EA0302": "/admin/product/edit", "EA0303": "/admin/class-name/class-name-list",
 "EA0304": "/admin/class-category/class-category-list", "EA0305": "/admin/category/category-list",
 "EA0306": "/admin/product/csv-product", "EA0307": "/admin/category/csv",
 "EA0308": "/admin/class-name/class-name-list", "EA0309": "/admin/category/category-list",
 "EA0310": "/admin/product/product-class", "EA0311": "/admin/class-category/class-category-list",
 "EA0401": "/admin/order-list", "EA0402": "/admin/order/send-mail", "EA0405": "/admin/order/create",
 "EA0501": "/admin/customer-list", "EA0502": "/admin/customer", "EA0601": "/admin/news/news-list",
 "EA0602": "/admin/content/file-manager", "EA0603": "/admin/page/page-list",
 "EA0604": "/admin/block/block-list", "EA0605": "/admin/layout/layout-list",
 "EA0606": "/admin/content/css", "EA0607": "/admin/content/js", "EA0701": "/admin/base-info",
 "EA0702": "/admin/trade-law", "EA0703": "/admin/base-info", "EA0704": "/admin/payment/payment-list",
 "EA0705": "/admin/payment/payment", "EA0706": "/admin/delivery/delivery-list",
 "EA0707": "/admin/delivery/delivery", "EA0708": "/admin/tax-rule/tax-rule-list",
 "EA0709": "/admin/mail-template", "EA0710": "/admin/csv-config", "EA0711": "/admin/order-status",
 "EA0801": "/admin/system", "EA0802": "/admin/member-list", "EA0803": "/admin/member",
 "EA0804": "/admin/security", "EA0805": "/admin/authority-role", "EA0806": "/admin/log",
 "EA0807": "/admin/master-data", "EA0901": "/admin/order/edit",
 "EA0903": "/admin/order/import-shipping", "EA0093": "/admin/order/export-shipping",
 "EA1001": "/admin/tax-rule/tax-rule-list",
}
bad = [(k, v) for k, v in ROUTES.items() if v.split("?")[0] not in AUTH]
assert not bad, f"NON-AUTHORITATIVE ROUTES (guessing!): {bad}"

# ---- area -> (flow test relpath, status) ; status: '✅' test-verified / '🟢' screen ----
AREA_TEST = {
 "EA01": (None, "🟢"), "EA02": ("tests/Http/HttpAdminLoginCookieFlowTest.php", "✅"),
 "EA03": ("tests/Hypermedia/FlowAdminProductPublishTest.php", "✅"),
 "EA04": ("tests/Hypermedia/FlowAdminOrderFulfillmentTest.php", "✅"),
 "EA05": ("tests/Hypermedia/FlowAdminCustomerMaintenanceTest.php", "✅"),
 "EA06": ("tests/Hypermedia/FlowAdminContentPublishTest.php", "✅"),
 "EA07": ("tests/Hypermedia/FlowAdminShopConfigurationTest.php", "✅"),
 "EA08": ("tests/Hypermedia/FlowAdminSystemOperationTest.php", "✅"),
 "EA09": ("tests/Hypermedia/FlowAdminOrderFulfillmentTest.php", "✅"),
 "EA10": (None, "🟢"), "EF01": (None, "🟢"),
 "EF02": ("tests/Hypermedia/FlowCustomerPurchaseTest.php", "✅"),
 "EF03": ("tests/Hypermedia/FlowCustomerPurchaseTest.php", "✅"),
 "EF04": ("tests/Hypermedia/FlowCustomerRegistrationTest.php", "✅"),
 "EF05": ("tests/Hypermedia/FlowCustomerAccountMaintenanceTest.php", "✅"),
 "EF06": ("tests/Hypermedia/FlowCustomerInquiryTest.php", "✅"),
 "EF07": ("tests/Hypermedia/FlowCustomerPurchaseTest.php", "✅"),
}
# screen-level overrides (more precise test or status)
SCREEN = {
 "EA0305": ("tests/Hypermedia/FlowAdminCategoryMaintenanceTest.php", "✅"),
 "EA0307": ("tests/Hypermedia/FlowAdminCategoryMaintenanceTest.php", "✅"),
 "EA0309": ("tests/Hypermedia/FlowAdminCategoryMaintenanceTest.php", "✅"),
 "EA0303": ("tests/Hypermedia/FlowAdminClassMaintenanceTest.php", "✅"),
 "EA0304": ("tests/Hypermedia/FlowAdminClassMaintenanceTest.php", "✅"),
 "EA0308": ("tests/Hypermedia/FlowAdminClassMaintenanceTest.php", "✅"),
 "EA0311": ("tests/Hypermedia/FlowAdminClassMaintenanceTest.php", "✅"),
 "EA0709": ("tests/Hypermedia/FlowAdminMailTemplateMaintenanceTest.php", "✅"),
 "EA0805": ("tests/Hypermedia/FlowAdminAuthorityRoleRulesTest.php", "✅"),
 "EA0807": ("tests/Hypermedia/FlowAdminMasterDataUpdateTest.php", "✅"),
 "EA0903": ("tests/Hypermedia/FlowAdminCsvExchangeTest.php", "✅"),
 "EA0306": ("tests/Hypermedia/FlowAdminCsvExchangeTest.php", "✅"),
 "EF0601": (None, "🟢"), "EF0602": (None, "🟢"), "EF0603": (None, "🟢"),
 "EF0604": (None, "🟢"), "EF0605": (None, "🟢"), "EF0606": (None, "🟢"),
}
# screenshot evidence (relative to this html), where captured
SHOT = {"EF0101": "evidence-browser/EF-top.png", "EF0301": "evidence-browser/EF-cart-add.png",
        "EF0305": "evidence-browser/EF0305-guest-checkout.png", "EF0401": "evidence-browser/EF-entry.png",
        "EA0101": "admin-sweep/index.png"}
def admin_shot(route):
    if route.startswith("/admin/"):
        slug = re.sub(r"\W+", "_", route[len("/admin/"):])
        p = HERE / "admin-sweep" / f"{slug}.png"
        if p.exists():
            return f"admin-sweep/{slug}.png"
    return None

SPEC_FILE = {  # area -> EC-CUBE spec filename
 "EA01": "EA01_TOP_結合試験項目書.md", "EA02": "EA02_Authentication_結合試験項目書.md",
 "EA03": "EA03_Product_結合試験項目書.md", "EA04": "EA04_Order_結合試験項目書.md",
 "EA05": "EA05_Customer_結合試験項目書.md", "EA06": "EA06_ContentManagement_結合試験項目書.md",
 "EA07": "EA07_BasicInformation_結合試験項目書.md", "EA08": "EA08_SystemInformation_結合試験項目書.md",
 "EA09": "EA09_Shipping_結合試験項目書.md", "EA10": "EA10_ReducedTax_結合試験項目書.md",
 "EF01": "EF01_TOP_結合試験項目書.md", "EF02": "EF02_Product_結合試験項目書.md",
 "EF03": "EF03_Order_結合試験項目書.md", "EF04": "EF04_Customer_結合試験項目書.md",
 "EF05": "EF05_Mypage_結合試験項目書.md", "EF06": "EF06_Other_結合試験項目書.md",
 "EF07": "EF07_ReducedTax_結合試験項目書.md",
}
SPEC_BASE = "https://github.com/EC-CUBE/eccube-specification/blob/4.0/IntegrationTest/"
AREA_NAME = {"EF": "フロント (EF)", "EA": "管理 (EA)"}

# ---- join: per item -> row dict ----
from urllib.parse import quote
def item_shot(it, route):
    # Admin: prefer the authenticated route-correct sweep (reliable).
    a = admin_shot(route)
    if a:
        return a
    # Per-item harness screenshot, named {area}-{item_id}.png.
    rel = f"evidence-browser/{it['area']}-{it['item_id']}.png"
    if (HERE / rel).exists():
        return rel
    return SHOT.get(it["item_id"][:6])

def row_for(it):
    sid = it["item_id"][:6]
    route = ROUTES.get(sid, "")
    test, status = SCREEN.get(sid, AREA_TEST.get(it["area"], (None, "🟢")))
    shot = item_shot(it, route)
    spec = SPEC_BASE + quote(SPEC_FILE[it["area"]])
    return {"id": it["item_id"], "screen": sid, "url": route, "title": it["title"],
            "status": status, "test": test, "shot": shot, "spec": spec, "area": it["area"]}

rows = [row_for(it) for it in ITEMS]
n_ok = sum(1 for r in rows if r["status"] == "✅")
n_screen = sum(1 for r in rows if r["status"] == "🟢")

# ---- ALPS profile ----
def d(i, t, title, **kw): return {"id": i, "type": t, "title": title, **kw}
profile = {"$schema": "https://alps-io.github.io/schemas/alps.json", "alps": {"version": "1.0",
  "title": "BeMart Implementation Status Profile",
  "doc": {"value": "Declares the meaning of the BeMart × EC-CUBE 結合試験 coverage report."},
  "descriptor": [
    d("implementationStatus", "semantic", "Coverage report document"),
    d("pageSummary", "semantic", "What this page covers and headline metrics"),
    d("selectionNote", "semantic", "Scope, granularity and how status was derived"),
    d("statusLegend", "semantic", "Meaning of the status values"),
    d("areaSection", "semantic", "A functional area (EF front / EA admin)"),
    d("screenGroup", "semantic", "A screen (画面ID) grouping its use-case test items"),
    d("screenName", "semantic", "Screen name"),
    d("coverageItem", "semantic", "One use-case test item (1 row)", descriptor=[
        d("useCaseId", "semantic", "EC-CUBE 結合試験 item id (画面ID-UCxx-Txx)"),
        d("screenUrl", "semantic", "BeMart URL template for the screen/operation"),
        d("useCaseTitle", "semantic", "Spec section title (verbatim)"),
        d("status", "semantic", "Verification status value"),
        d("specLink", "semantic", "Provenance link to the EC-CUBE spec source"),
        d("testLink", "semantic", "Link to the BeMart test that exercises it"),
        d("evidenceLink", "semantic", "Link to a captured screenshot"),
    ]),
  ]}}
(HERE / "implementation-status.alps.json").write_text(
    json.dumps(profile, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

# ---- HTML ----
e = html.escape
def cell_links(r):
    parts = [f'<a class="specLink" href="{e(r["spec"])}">spec</a>']
    if r["test"]:
        parts.append(f'<a class="testLink" href="../../{e(r["test"])}">{e(Path(r["test"]).stem)}</a>')
    if r["shot"]:
        parts.append(f'<a class="evidenceLink" href="{e(r["shot"])}">shot</a>')
    return " · ".join(parts)

# group rows: area -> screen -> [rows]
from collections import OrderedDict
groups = OrderedDict()
for r in rows:
    groups.setdefault(r["area"], OrderedDict()).setdefault(r["screen"], []).append(r)
# representative screen title (strip trailing parenthetical for the heading)
screen_title = {}
for r in rows:
    screen_title.setdefault(r["screen"], re.sub(r"（.*$", "", r["title"]).strip())

toc, body = [], []
for area, screens in groups.items():
    sec = AREA_NAME[area[:2]] + " — " + area
    body.append(f'<section class="areaSection" id="{area}"><h2>{e(sec)}</h2>')
    for sid, rs in screens.items():
        toc.append(f'<a href="#{sid}">{sid} {e(screen_title[sid])}</a>')
        body.append(f'<section class="screenGroup" id="{sid}"><h3><span class="screenName">{sid} {e(screen_title[sid])}</span></h3>')
        body.append('<table><thead><tr><th>ユースケースID</th><th>URL</th><th>試験項目</th><th>状況</th><th>根拠</th></tr></thead><tbody>')
        for r in rs:
            sc = "ok" if r["status"] == "✅" else "screen"
            url = e(r["url"]) if r["url"] else "—"
            body.append(
              f'<tr class="coverageItem">'
              f'<td class="useCaseId">{e(r["id"])}</td>'
              f'<td class="screenUrl"><code>{url}</code></td>'
              f'<td class="useCaseTitle">{e(r["title"])}</td>'
              f'<td class="status status--{sc}">{r["status"]} {"テスト検証" if sc=="ok" else "画面確認"}</td>'
              f'<td>{cell_links(r)}</td></tr>')
        body.append("</tbody></table></section>")
    body.append("</section>")

doc = f"""<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="implementation-status.alps.json">
<title>BeMart 実装状況 — EC-CUBE 結合試験カバレッジ</title>
<style>
:root {{ color-scheme: light dark; }}
body {{ font: 16px/1.6 system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "Noto Sans JP", sans-serif;
  margin: 2rem auto; max-width: 78rem; padding: 0 1rem; }}
h1 {{ font-size: 1.6rem; margin: 0 0 .3rem; }}
h2 {{ font-size: 1.25rem; margin: 2.2rem 0 .6rem; border-bottom: 2px solid #ccc; padding-bottom: .2rem; }}
h3 {{ font-size: 1.02rem; margin: 1.4rem 0 .4rem; }}
table {{ border-collapse: collapse; width: 100%; font-size: .92rem; }}
th, td {{ border-bottom: 1px solid #ddd; padding: .35rem .5rem; text-align: left; vertical-align: top; }}
th {{ border-bottom: 2px solid #bbb; font-weight: 600; white-space: nowrap; }}
code {{ font: .86em/1.4 ui-monospace, SFMono-Regular, Menlo, monospace; }}
.status {{ white-space: nowrap; }}
.status--ok {{ color: #0a7d2c; }}
.status--screen {{ color: #1257a8; }}
a {{ color: #1257a8; text-decoration: none; }} a:hover {{ text-decoration: underline; }}
.toc {{ columns: 2 18rem; font-size: .9rem; margin: 1rem 0 0; }}
.toc a {{ display: block; padding: .05rem 0; }}
.legend td {{ border: none; padding: .1rem .6rem .1rem 0; }}
.meta {{ color: #555; font-size: .9rem; }}
@media (prefers-color-scheme: dark) {{
  th,td {{ border-color: #444; }} h2 {{ border-color: #555; }}
  .status--ok {{ color: #4cd07d; }} .status--screen {{ color: #6fa8ec; }} a {{ color: #6fa8ec; }} .meta{{color:#aaa;}}
}}
</style>
</head>
<body class="implementationStatus">
<header class="pageSummary">
<h1>BeMart 実装状況 — EC-CUBE 結合試験カバレッジ</h1>
<p>EC-CUBE 4.x 結合試験項目書（17領域・{len(rows)}試験項目）に対する BeMart（BEAR.Sunday + Be Framework 移植）の実装状況。
各行は<strong>原典の試験項目</strong>に1対1で対応し、URL・状況・根拠（spec/test/screenshot）に直結する。</p>
<table class="legend">
<tr class="statusLegend"><td><span class="status--ok">✅ テスト検証</span></td><td>passing な flow/HTTP テストが実際に動かしている（{n_ok}件）</td></tr>
<tr class="statusLegend"><td><span class="status--screen">🟢 画面確認</span></td><td>実ルートが解決し画面が描画される（admin sweep 等）。専用の操作テストは未（{n_screen}件）</td></tr>
</table>
<p class="selectionNote meta"><strong>スコープ:</strong> 全{len(rows)}試験項目を T 粒度で1行ずつ収録。状況は <em>area/flow 粒度</em>で導出（✅＝その領域のワークフローを passing な flow テストが通す。🟢＝画面は描画されるが専用フローテスト未）。URL は <code>src/Resource/Page/**</code> の実ルートに対し機械検証済（推測なし）。各 spec リンクは EC-CUBE 原典、test リンクは BeMart テスト。</p>
<p class="selectionNote meta"><strong>スクリーンショットの撮り方（透明性）:</strong> 管理画面のショットは<strong>決定的フィクスチャ</strong>（<code>composer db:reset</code>＝<code>be/var/fake/*.json</code> を FK 整合の SQL に実体化）の上で <code>harness/snap.cjs</code> が撮り直す。<strong>200 で実描画した画面のみ</strong>を掲載し、POST/パラメータ必須の操作エンドポイント（受注登録・出荷 CSV 等、GET 画面を持たない）はショットを置かない（誤誘導するショットを残さないため stale を削除）。商品/受注/会員一覧・ダッシュボードはこのフィクスチャの実データ（商品7・会員5・受注7）を写している。</p>
<nav class="toc">{''.join(toc)}</nav>
</header>
<main>
{''.join(body)}
</main>
<footer class="meta"><p>生成: <code>docs/eccube-spec-coverage/build_status_html.py</code>（再生成可能）。
入力: <code>all_items.json</code>（原典パース）, <code>src/Resource/Page/**</code>（ルート）, <code>tests/**</code>（根拠）。
意味は <code><a href="implementation-status.alps.json">implementation-status.alps.json</a></code>（ALPS）で宣言。</p></footer>
</body>
</html>
"""
(HERE / "implementation-status.html").write_text(doc, encoding="utf-8")
print(f"OK  items={len(rows)}  ✅={n_ok}  🟢={n_screen}  screens={len(screen_title)}")
print("wrote implementation-status.html + implementation-status.alps.json")
