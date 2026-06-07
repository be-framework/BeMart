<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/template/template-add
EC-CUBE テンプレート登録 — Store Tier-2 (`admin/Store/template_add.twig`).

GET /admin/store/template/add → template-upload screen

Thin GET renderer for EC-CUBE's design-template registration screen:
a template code, a template name and a zip-archive file-upload form.
The matching `doTemplateInstall` write transition is a Phase-A stub —
this port renders the upload shell only, mirroring the Product
CSV-upload Tier-2 wave ({@see \MyVendor\BeMart\Support\Resource\AbstractCsvUpload}).

AUTHZ is a direct admin-session check (Pattern B — no Be transition is
invoked on the GET path; an anonymous admin → 403). The form renders
blank against empty JSON-backed fake storage — no storage is seeded.




## GET
ALPS `goAdminTemplateTemplateAdd` に対応する GET 操作。

**ALPS**: `goAdminTemplateTemplateAdd`



### Request

_No parameters required_

### Response

[Object: GET /admin/template/template-add response](../schemas/get-admin-template-template-add.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTemplateList | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
| doInstallTemplate | [<code>page://self/admin/template/template-add</code>](/admin/template/template-add.md) |
## POST
Installs an uploaded design template (doInstallTemplate). ALPS
marks it `unsafe` → POST.

**ALPS**: `doInstallTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateCode | string | テンプレートコード（入力） - テンプレートの一意識別コード。標準テンプレートは'default' |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| templateName | string | テンプレート名（入力） - テンプレートの表示名 Fake観察文字長 10〜11; 観察値 'デフォルト (PC)', 'デフォルト (スマホ)'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | デフォルト (PC) |


### Response

[Object: POST /admin/template/template-add response](../schemas/post-admin-template-template-add.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/template/template-add のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| templateId | string|null | テンプレートID - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tp-default-pc |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goTemplateList | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
| doSelectTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |