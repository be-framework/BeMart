<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/layout/layout
EC-CUBE doUpdateLayout — single-row endpoint (Wave 9 CMS). Only PUT
is exposed to the domain; layouts can be neither created nor deleted
via the admin UI (system-managed).

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminLayoutForm}
(Ray.WebFormModule AbstractForm) as `body['form']` so the admin layout
editor (`Content/layout.twig` port) can render the real layout-name
`<input>` via `{{ form.input(...) }}`.

NOTE — single-row prefill: the Be domain exposes no
`GetAdminLayoutInput` / `AdminLayoutFetched` (single-row fetch), so
`onGet` renders the NEW-layout form (the `admin_content_layout_new`
case — the layout designer with an empty block canvas). Pre-filling an
existing layout + its block positions would need a Be fetch Input — a
`be/src/` change out of this Phase 3 HTML wave's scope. FLAGGED:
follow-up to add `GetAdminLayoutInput` for existing-layout edit prefill.




## GET
Renders the layout editor form (new-layout case).

The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.

**ALPS**: `doUpdateLayout`



### Request

_No parameters required_

### Response

[Object: GET /admin/layout/layout response](../schemas/get-admin-layout-layout.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goLayoutList | [<code>page://self/admin/layout/layout-list</code>](/admin/layout/layout-list.md) |
## PUT
ALPS `doUpdateLayout` に対応する PUT 操作。

**ALPS**: `doUpdateLayout`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| layoutId | string | レイアウトID（入力） - CMSレイアウトを識別する不透明な文字列ハンドル。seed 済みレイアウトを Fake/SQL 同型に扱う。 Fake観察文字長 13〜13; 観察値 'lo-pc-default', 'lo-sp-default'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | lo-pc-default |
| layoutName | string | レイアウト名（入力） - レイアウトの表示名 Fake観察文字長 4〜5; 観察値 'PC標準', 'スマホ標準'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | PC標準 |


### Response

[Object: PUT /admin/layout/layout response](../schemas/put-admin-layout-layout.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deviceType | int|null | デバイス種別 - デバイス種別マスタ（EC-CUBE 2.xからの名残）。値: 2=モバイル, 10=PC。非連番のIDは旧バージョンのデバイスサポート（ガラケー等）に由来。ページレイアウトのデバイス別表示に使用 Fake観察数値 2〜10; 観察値 '10', '2'。 | Required | {"minimum":0} | 10 |
| layoutId | string|null | レイアウトID - CMSレイアウトを識別する不透明な文字列ハンドル。seed 済みレイアウトを Fake/SQL 同型に扱う。 Fake観察文字長 13〜13; 観察値 'lo-pc-default', 'lo-sp-default'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | lo-pc-default |
| layoutName | string|null | レイアウト名 - レイアウトの表示名 Fake観察文字長 4〜5; 観察値 'PC標準', 'スマホ標準'。 | Required | {"minLength":0,"maxLength":32} | PC標準 |

#### Links

| Relation | URL |
|----------|-----|
| goLayoutList | [<code>page://self/admin/layout/layout-list</code>](/admin/layout/layout-list.md) |
| goTradeLawList | [<code>page://self/admin/trade-law</code>](/admin/trade-law.md) |