<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/tag/tag-list
EC-CUBE goTagList + doCreateTag — collection endpoint (Wave 9).






## GET
ALPS `goTagList` に対応する GET 操作。

**ALPS**: `goTagList`



### Request

_No parameters required_

### Response

[Object: GET /admin/tag/tag-list response](../schemas/get-admin-tag-tag-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| count | int|null | 件数 - /admin/tag/tag-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| tags | array|null | タグ一覧 - /admin/tag/tag-list のレスポンスで扱うタグ一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30bf\u30b0","description":"/admin/tag/tag-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30bf\u30b0\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `tags` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"tagId":{"type":["string","null"],"title":"\u30bf\u30b0ID","description":"dtb_tag.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e TagEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f `tg-` \u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9\u4ed8\u304d\u306e\u82f1\u6570\u5b57\u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_tag.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlTagStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (TagDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `tg-new` / `tg-sale` \u306f Fake \u5c02\u7528 Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c7; \u89b3\u5bdf\u5024 'tg-new', 'tg-sale'\u3002","example":"tg-new","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"tagName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30bf\u30b0\u540d","description":"\u5546\u54c1\u306b\u4ed8\u4e0e\u3059\u308b\u30bf\u30b0\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c3; \u89b3\u5bdf\u5024 '\u65b0\u5546\u54c1', '\u30bb\u30fc\u30eb'\u3002","example":"\u65b0\u5546\u54c1"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateTag | [<code>page://self/admin/tag/tag-list</code>](/admin/tag/tag-list.md) |
| doDeleteTag | [<code>page://self/admin/tag/tag</code>](/admin/tag/tag.md) |
## POST
ALPS `doCreateTag` に対応する POST 操作。

**ALPS**: `doCreateTag`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| tagName | string | タグ名（入力） - 商品に付与するタグの表示名 Fake観察文字長 3〜3; 観察値 '新商品', 'セール'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 新商品 |


### Response

[Object: POST /admin/tag/tag-list response](../schemas/post-admin-tag-tag-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| tagId | string|null | タグID - dtb_tag.id の不透明な文字列ハンドル。BeMart の TagEntity 層は数値ではなく文字列として保持する。Fake 実装は `tg-` プレフィックス付きの英数字を生成し、SQL 実装は dtb_tag.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTagStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TagDeleted) を踏むため、シードハンドル `tg-new` / `tg-sale` は Fake 専用 Fake観察文字長 6〜7; 観察値 'tg-new', 'tg-sale'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tg-new |
| tagName | string|null | タグ名 - 商品に付与するタグの表示名 Fake観察文字長 3〜3; 観察値 '新商品', 'セール'。 | Required | {"minLength":0,"maxLength":32} | 新商品 |

#### Links

| Relation | URL |
|----------|-----|
| goTagList | [<code>page://self/admin/tag/tag-list</code>](/admin/tag/tag-list.md) |