<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-list
EC-CUBE goProductList — 商品一覧（管理画面） (Wave 8, admin filter
search + pagination).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when AdminSession reports
no admin session, which we map to 403. The customer-facing product
list (when it lands) will be a sibling resource at a different URL.

Failure mapping:
  - SemanticVariableException             → 400 (filter format invalid)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Hypermedia: links to per-product admin detail + CSV export + bulk
status update endpoints — the operator drills into a row from the
grid, exports the corpus, or applies a bulk action.




## GET
ALPS `goProductList` に対応する GET 操作。

**ALPS**: `goProductList` - 商品一覧を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string | 名前検索キーワード - /admin/product-list の検索条件。商品名・会員名・管理者名など、この一覧画面で名前として扱う表示名を部分一致検索する。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| limit | int | 表示件数（入力） - /admin/product-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 50 | Optional | {"default":50,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| offset | int | 開始位置（入力） - /admin/product-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 0 | Optional | {"default":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /admin/product-list response](../schemas/get-admin-product-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| filters | object|null | 検索条件 - /admin/product-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | Required | {"properties":{"nameKeyword":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u540d\u524d\u691c\u7d22\u30ad\u30fc\u30ef\u30fc\u30c9","description":"/admin/product-list \u306e\u691c\u7d22\u6761\u4ef6\u3002\u5546\u54c1\u540d\u30fb\u4f1a\u54e1\u540d\u30fb\u7ba1\u7406\u8005\u540d\u306a\u3069\u3001\u3053\u306e\u4e00\u89a7\u753b\u9762\u3067\u540d\u524d\u3068\u3057\u3066\u6271\u3046\u8868\u793a\u540d\u3092\u90e8\u5206\u4e00\u81f4\u691c\u7d22\u3059\u308b\u3002","example":"\u9234\u6728"},"offset":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u958b\u59cb\u4f4d\u7f6e","description":"/admin/product-list \u306e\u4e00\u89a7\u8868\u793a\u3092\u5236\u5fa1\u3059\u308b\u30da\u30fc\u30b8\u30f3\u30b0/\u691c\u7d22\u6761\u4ef6\u3002\u4ef6\u6570\u3001\u958b\u59cb\u4f4d\u7f6e\u3001\u4e26\u3073\u9806\u3001\u524d\u5f8c\u30ea\u30f3\u30af\u3092\u30af\u30e9\u30a4\u30a2\u30f3\u30c8\u304c\u518d\u73fe\u3059\u308b\u305f\u3081\u306e\u5024\u3002"},"limit":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u4ef6\u6570","description":"/admin/product-list \u306e\u4e00\u89a7\u8868\u793a\u3092\u5236\u5fa1\u3059\u308b\u30da\u30fc\u30b8\u30f3\u30b0/\u691c\u7d22\u6761\u4ef6\u3002\u4ef6\u6570\u3001\u958b\u59cb\u4f4d\u7f6e\u3001\u4e26\u3073\u9806\u3001\u524d\u5f8c\u30ea\u30f3\u30af\u3092\u30af\u30e9\u30a4\u30a2\u30f3\u30c8\u304c\u518d\u73fe\u3059\u308b\u305f\u3081\u306e\u5024\u3002"}},"additionalProperties":false,"required":["nameKeyword","offset","limit"]} |  |
| count | int|null | 件数 - /admin/product-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| searchForm | object|array|null | 検索フォーム - /admin/product-list のレスポンスで保持するフォーム文脈。Aura/WebForm由来の内部構造は別境界の責務で、ここではResource上の役割を示す。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| products | array|null | 商品一覧 - /admin/product-list のレスポンスで扱う商品一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u5546\u54c1\u6982\u8981","description":"/admin/product-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u6982\u8981\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `products` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"tagNames":{"type":["array","null"],"title":"\u30bf\u30b0\u540d\u4e00\u89a7","description":"Fake\u89b3\u5bdf\u6570\u5024 0\u301c2\u3002","items":{"type":"string","title":"\u30bf\u30b0\u540d","minLength":0,"maxLength":128,"description":"/admin/product-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `tagNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0},"stock":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u5728\u5eab\u6570","description":"\u7269\u7406\u5728\u5eab\u6570\u3002stockUnlimited=true\u306e\u5834\u5408\u306f\u7121\u8996\u3055\u308c\u308b\u3002\u6ce8\u6587\u78ba\u5b9a\u6642\u306b\u5f15\u304d\u5f53\u3066\u304c\u884c\u308f\u308c\u308b Fake\u89b3\u5bdf\u6570\u5024 0\u301c100; \u89b3\u5bdf\u5024 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73\u3002","example":0},"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"categoryNames":{"type":["array","null"],"title":"\u30ab\u30c6\u30b4\u30ea\u540d\u4e00\u89a7","description":"Fake\u89b3\u5bdf\u6570\u5024 0\u301c4\u3002","items":{"type":"string","title":"\u30ab\u30c6\u30b4\u30ea\u540d","minLength":0,"maxLength":128,"description":"/admin/product-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `categoryNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001"},"productStatus":{"title":"\u5546\u54c1\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u516c\u958b\uff08\u30d5\u30ed\u30f3\u30c8\u8868\u793a\uff09, 2=\u975e\u516c\u958b\uff08\u30d5\u30ed\u30f3\u30c8\u975e\u8868\u793a\uff09, 3=\u5ec3\u6b62\uff08\u8ad6\u7406\u524a\u9664\u3001\u7ba1\u7406\u753b\u9762\u3067\u3082\u30c7\u30d5\u30a9\u30eb\u30c8\u975e\u8868\u793a\uff09 Fake\u89b3\u5bdf\u6570\u5024 1\u301c3; \u89b3\u5bdf\u5024 '1', '2', '3'\u3002","type":["integer","null"],"enum":[1,2,3],"example":1},"imagePath":{"title":"\u753b\u50cfURI","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'assets/img/top/img_item02_01.jpg', 'assets/img/top/img_item01_02.jpg', 'assets/img/top/img_item02_02.jpg', 'assets/img/top/img_item02_03.jpg', 'assets/img/top/img_item01_01.jpg'; null 1/7\u3002","type":["string","null"],"format":"uri-reference","minLength":1,"maxLength":2048,"example":"assets/img/top/img_item02_01.jpg"},"price02":{"title":"\u8ca9\u58f2\u4fa1\u683c","description":"\u5b9f\u969b\u306e\u8ca9\u58f2\u4fa1\u683c\uff08\u7a0e\u629c\uff09\u3002\u7a0e\u8a08\u7b97\u30fb\u5c0f\u8a08\u8a08\u7b97\u306e\u30d9\u30fc\u30b9 Fake\u89b3\u5bdf\u6570\u5024 800\u301c28000\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":3500},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":1200},"fileName":{"type":["string","null"],"minLength":1,"maxLength":255,"title":"\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u5546\u54c1\u753b\u50cf\u306e\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c15; \u89b3\u5bdf\u5024 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'\u3002","example":"Mail/order.twig"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| doCreateProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| doBulkUpdateProductStatus | [<code>page://self/admin/product-bulk-status</code>](/admin/product-bulk-status.md) |
| goExportProduct | [<code>page://self/admin/product-csv</code>](/admin/product-csv.md) |