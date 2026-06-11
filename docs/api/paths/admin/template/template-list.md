<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/template/template-list
EC-CUBE goTemplateList — list-only endpoint (Wave 9). ALPS exposes
no other affordances; template upload / activation is Phase 2.






## GET
ALPS `goTemplateList` に対応する GET 操作。

**ALPS**: `goTemplateList`



### Request

_No parameters required_

### Response

[Object: GET /admin/template/template-list response](../schemas/get-admin-template-template-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/template/template-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| templates | array|null | テンプレート一覧 - /admin/template/template-list のレスポンスで扱うテンプレート一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8","description":"/admin/template/template-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `templates` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"deviceType":{"type":["integer","null"],"title":"\u30c7\u30d0\u30a4\u30b9\u7a2e\u5225","description":"\u30c7\u30d0\u30a4\u30b9\u7a2e\u5225\u30de\u30b9\u30bf\uff08EC-CUBE 2.x\u304b\u3089\u306e\u540d\u6b8b\uff09\u3002\u5024: 2=\u30e2\u30d0\u30a4\u30eb, 10=PC\u3002\u975e\u9023\u756a\u306eID\u306f\u65e7\u30d0\u30fc\u30b8\u30e7\u30f3\u306e\u30c7\u30d0\u30a4\u30b9\u30b5\u30dd\u30fc\u30c8\uff08\u30ac\u30e9\u30b1\u30fc\u7b49\uff09\u306b\u7531\u6765\u3002\u30da\u30fc\u30b8\u30ec\u30a4\u30a2\u30a6\u30c8\u306e\u30c7\u30d0\u30a4\u30b9\u5225\u8868\u793a\u306b\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 2\u301c10; \u89b3\u5bdf\u5024 '10', '2'\u3002","example":10,"minimum":0},"templateId":{"type":["string","null"],"title":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8ID","description":"dtb_template.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e TemplateEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f `tp-` \u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9\u4ed8\u304d\u306e\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb\uff08tp-default-pc / tp-default-sp\uff09\u3092\u6301\u3061\u3001SQL \u5b9f\u88c5\u306f dtb_template.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002Template \u306b\u306f list \u30a2\u30d5\u30a9\u30fc\u30c0\u30f3\u30b9\u306e\u307f\uff08goTemplateList\uff09\u3067\u4f5c\u6210\u30fb\u66f4\u65b0\u30fb\u524a\u9664\u304c\u7121\u3044\u305f\u3081 ID \u30b8\u30a7\u30cd\u30ec\u30fc\u30bf\u306f\u5b58\u5728\u305b\u305a\u3001\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u306f\u30a4\u30f3\u30b9\u30c8\u30fc\u30e9/fixture \u304c seed \u3057\u305f\u884c\u304b\u3089\u8aad\u307f\u51fa\u3059\u306e\u307f\u3002layoutId / blockId / categoryId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027\u3002templateCode\uff08\u4e00\u610f\u306e install-time \u30b3\u30fc\u30c9\uff09\u3068\u306f\u5225\u7269 \u2014 TemplateEntity \u306e\u6295\u5f71\u306f id \u30cf\u30f3\u30c9\u30eb\u3092\u7528\u3044 templateCode \u306f\u6295\u5f71\u5916 Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c13; \u89b3\u5bdf\u5024 'tp-default-pc', 'tp-default-sp'\u3002","example":"tp-default-pc","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"templateName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u540d","description":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c11; \u89b3\u5bdf\u5024 '\u30c7\u30d5\u30a9\u30eb\u30c8 (PC)', '\u30c7\u30d5\u30a9\u30eb\u30c8 (\u30b9\u30de\u30db)'\u3002","example":"\u30c7\u30d5\u30a9\u30eb\u30c8 (PC)"},"active":{"type":["boolean","null"],"title":"\u9069\u7528\u4e2d\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8","description":"\u73fe\u5728\u9069\u7528\u3055\u308c\u3066\u3044\u308b\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u304b\u3069\u3046\u304b\u3002TemplateCompatibility \u306e runtime readback \u5883\u754c\u304b\u3089\u5c0e\u51fa\u3055\u308c\u3001HTML radio \u306e checked \u72b6\u614b\u306b\u5bfe\u5fdc\u3059\u308b\u3002","example":true}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| csrfToken | string |  | Optional | {"$ref":"#/$defs/csrfToken"} |  |
| links | object|null | ALPS遷移リンク集合 - /admin/template/template-list のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"goTemplateAdd":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goTemplateAdd` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["goTemplateAdd"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTemplateAdd | [<code>page://self/admin/template/template-add</code>](/admin/template/template-add.md) |
| goTemplateInstall | [<code>page://self/admin/template/template-add</code>](/admin/template/template-add.md) |
| doSelectTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
| doDownloadTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
| doDeleteTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
## PUT
Activates a template (doSelectTemplate). ALPS idempotent → PUT.

ALPS `doSelectTemplate` に対応する PUT 操作。

**ALPS**: `doSelectTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID（入力） - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | tp-default-pc |


### Response

[Object: PUT /admin/template/template-list response](../schemas/put-admin-template-template-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/template/template-list のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| templateId | string|null | テンプレートID - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tp-default-pc |

#### Links

| Relation | URL |
|----------|-----|
| doDownloadTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
## DELETE
Deletes a template (doDeleteTemplate). ALPS idempotent → DELETE.

ALPS `doDeleteTemplate` に対応する DELETE 操作。

**ALPS**: `doDeleteTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID（入力） - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | tp-default-pc |


### Response

[Object: DELETE /admin/template/template-list response](../schemas/delete-admin-template-template-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/template/template-list のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| templateId | string|null | テンプレートID - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tp-default-pc |

#### Links

| Relation | URL |
|----------|-----|
| goTemplateList | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |
## POST
Downloads a template zip (doDownloadTemplate). ALPS unsafe → POST.

ALPS `doDownloadTemplate` に対応する POST 操作。

**ALPS**: `doDownloadTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID（入力） - dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外 Fake観察文字長 13〜13; 観察値 'tp-default-pc', 'tp-default-sp'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | tp-default-pc |


### Response

[Object: POST /admin/template/template-list response](../schemas/post-admin-template-template-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/template/template-list のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| value | string | 表示値 - /admin/template/template-list のレスポンスで表示または選択肢として使う値。親コンテキスト `` に属する。 | Required | {"minLength":1,"maxLength":255} |  |

#### Links

| Relation | URL |
|----------|-----|
| doDeleteTemplate | [<code>page://self/admin/template/template-list</code>](/admin/template/template-list.md) |