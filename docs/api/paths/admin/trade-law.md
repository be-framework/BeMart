<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/trade-law
EC-CUBE doUpdateTradeLaw + goTradeLawList — 特定商取引法 (Wave 8 + Wave 9).

- GET  → goTradeLawList (safe read, admin AUTHZ, Wave 9ι)
  - POST → doUpdateTradeLaw (idempotent, admin AUTHZ + CSRF, Wave 8ε)

Wave 8 first iteration treats the page as a single body blob; Phase 2
will split into per-item rows.

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (body length)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
Wave 9ι: goTradeLawList — admin views the current TradeLaw body.

**ALPS**: `goTradeLawList`



### Request

_No parameters required_

### Response

[Object: GET /admin/trade-law response](../schemas/get-admin-trade-law.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| tradeLawBody | string|null | 特定商取引法本文 - 特定商取引法ページ本文の単一ブロブ投影。項目別行ではなく、ページ全体を1本の本文として扱う。 | Required | {"minLength":0,"maxLength":255} |  |
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| tradeLawRows | array|null | 特定商取引法表示行 - /admin/trade-law のレスポンスで扱う特定商取引法表示行。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u7279\u5b9a\u5546\u53d6\u5f15\u6cd5\u8868\u793a\u884c","description":"/admin/trade-law \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u7279\u5b9a\u5546\u53d6\u5f15\u6cd5\u8868\u793a\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `tradeLawRows` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"description":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8a73\u7d30\u8aac\u660e\u6587","description":"\u5546\u54c1\u8a73\u7d30\u30da\u30fc\u30b8\u306b\u8868\u793a\u3059\u308b\u8aac\u660e\u6587 Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c32; \u89b3\u5bdf\u5024 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u540d\u79f0\u5909\u66f4\u3057\u305f\u3001\u5f69\u308a\u8c4a\u304b\u306a\u30b8\u30a7\u30e9\u30fc\u30c8\u30bb\u30c3\u30c8\u3067\u3059\u3002', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u4f5c\u6210\u3057\u305f\u5546\u54c1'; null 1/7\u3002","example":"Stock-unlimited fixture"},"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"},"displayOrderScreen":{"type":["boolean","null"],"title":"\u8868\u793a\u9806\u753b\u9762\u30d5\u30e9\u30b0","description":"\u6ce8\u6587\u78ba\u8a8d\u753b\u9762\u306b\u3053\u306e\u9805\u76ee\u3092\u8868\u793a\u3059\u308b\u304b"},"displayOrderScreenKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8868\u793a\u9806\u753b\u9762\u30ad\u30fc","description":"\u6ce8\u6587\u78ba\u8a8d\u753b\u9762\u306b\u3053\u306e\u9805\u76ee\u3092\u8868\u793a\u3059\u308b\u304b","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"},"nameKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u540d\u79f0\u30ad\u30fc","description":"/admin/trade-law \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u540d\u79f0\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"},"descriptionKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8aac\u660e\u30ad\u30fc","description":"/admin/trade-law \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u8aac\u660e\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateTradeLaw | [<code>page://self/admin/trade-law</code>](/admin/trade-law.md) |
## POST
ALPS `doUpdateTradeLaw` に対応する POST 操作。

**ALPS**: `doUpdateTradeLaw`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| tradeLawBody | string | 特定商取引法本文（入力） - 特定商取引法ページ本文の単一ブロブ投影。項目別行ではなく、ページ全体を1本の本文として扱う。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/trade-law response](../schemas/post-admin-trade-law.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| tradeLawBody | string|null | 特定商取引法本文 - 特定商取引法ページ本文の単一ブロブ投影。項目別行ではなく、ページ全体を1本の本文として扱う。 | Required | {"minLength":0,"maxLength":255} |  |
| changed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Required |  | 1 |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/admin</code>](/admin.md) |
| goContentCss | [<code>page://self/admin/content/css</code>](/admin/content/css.md) |