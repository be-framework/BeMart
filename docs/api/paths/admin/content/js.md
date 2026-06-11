<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/js
EC-CUBE カスタマイズJavaScript編集 — admin CMS thin renderer
(Phase 3 HTML).

PORT-side note: EC-CUBE's `JsController` reads / writes a single
`customize.js` file on disk; there is no Be domain entity for it. This
customize-JS file was not modelled in any ALPS wave). The Be transition
updates the EC-CUBE-compatible asset boundary; GET renders that readback
through {@see \AdminJsForm}.

FLAGGED: a future `be/src/` wave should model the customize-JS file as
a Be domain so this resource can write the public customize.js asset
instead of the runtime compatibility boundary.




## GET
ALPS `goContentJs` に対応する GET 操作。

**ALPS**: `goContentJs`



### Request

_No parameters required_

### Response

[Object: GET /admin/content/js response](../schemas/get-admin-content-js.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| csrfToken | string|null | CSRFトークン - /admin/content/js のHTMLフォーム送信用CSRFトークン。 | Optional | {"minLength":0,"maxLength":160} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateContentJs | [<code>page://self/admin/content/js</code>](/admin/content/js.md) |
## PUT
Saves the customize JS (doUpdateContentJs). ALPS idempotent → PUT.

**ALPS**: `doUpdateContentJs`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| js | string | 処理一覧（入力） - /admin/content/js のレスポンスで運ぶ処理一覧。CSV/PDF/ログ等の内部形式は専用境界で扱い、JSON Schemaでは輸送上の型とサイズを契約する。 |  | Optional | {"minLength":0,"maxLength":1000000,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| mode | string | フォーム送信モード |  | Optional | {"minLength":0,"maxLength":32,"$comment":"HTML form submit marker; Resource workflow calls omit it."} |  |


### Response

[Object: PUT /admin/content/js response](../schemas/put-admin-content-js.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| length | int|null | 本文長 - /admin/content/js のレスポンスで返す本文長。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| message | string|null | 処理メッセージ - /admin/content/js のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
