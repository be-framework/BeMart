<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/product-class
EC-CUBE 商品規格 — Product Tier-2 (`admin/Product/product_class.twig`,
the ~448-line product-class matrix editor).

GET /admin/product/product-class?productCode=…  → class-matrix editor

Thin GET renderer. EC-CUBE's editor renders one row per
規格1 × 規格2 class-category cell, each carrying its own
price / stock / stock-unlimited / product-code / shipping-charge
controls. The Be domain has no transition to READ a product's
ProductClass matrix — the ProductClass join is Grade-C 厳密移植 scope
— so this resource renders a blank "新規規格" editor (the
render-smoke test exercises this with empty JSON-backed fake storage), mirroring
the sibling {@see \MyVendor\BeMart\Resource\Page\Admin\Order\ShippingAddress}
GET renderer.

AUTHZ: a direct admin-session check (Pattern B — no Be transition is
invoked on the GET path). No admin session → 403.




## GET
ALPS `goAdminProductProductClass` に対応する GET 操作。

**ALPS**: `goAdminProductProductClass`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: GET /admin/product/product-class response](../schemas/get-admin-product-product-class.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| classes | array|null | 商品規格一覧 - /admin/product/product-class のレスポンスで扱う商品規格一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u5546\u54c1\u898f\u683c","description":"/admin/product/product-class \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u898f\u683c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `classes` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"classNameId":{"type":["string","null"],"title":"\u898f\u683c\u540dID","description":"dtb_class_name.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e ClassNameEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_class_name.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlClassNameStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def\uff08\u898f\u683c\u540d\u306e\u66f4\u65b0\u30fb\u524a\u9664 Final\uff09\u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002categoryId / blockId / tagId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c8; \u89b3\u5bdf\u5024 'cn-color', 'cn-size'\u3002","example":"cn-color","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"classCategoryId":{"type":["string","null"],"title":"\u898f\u683c\u5206\u985eID","description":"dtb_class_category.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e ClassCategoryEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_class_category.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlClassCategoryStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def\uff08\u898f\u683c\u5206\u985e\u306e\u66f4\u65b0\u30fb\u524a\u9664 Final\uff09\u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002classNameId / categoryId / blockId / tagId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c8; \u89b3\u5bdf\u5024 'cc-red', 'cc-blue', 'cc-small'\u3002","example":"cc-red","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"className":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u898f\u683c\u540d","description":"/admin/product/product-class \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u898f\u683c\u540d\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002"},"classCategoryName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u898f\u683c\u5206\u985e\u540d","description":"\u5546\u54c1\u30d0\u30ea\u30a8\u30fc\u30b7\u30e7\u30f3\u8ef8\u306e\u5177\u4f53\u7684\u306a\u5024\uff08\u4f8b: \u8d64\u3001L\u30b5\u30a4\u30ba\uff09\u3002EC-CUBE\u306e\"classCategory\"\u306fOOP\u306e\u30ab\u30c6\u30b4\u30ea\u3067\u306f\u306a\u304f\u898f\u683c\u5024\u3092\u610f\u5473\u3059\u308b"},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":["string","null"],"minLength":0,"maxLength":64,"example":"sample-001"},"stock":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u5728\u5eab\u6570","description":"\u7269\u7406\u5728\u5eab\u6570\u3002stockUnlimited=true\u306e\u5834\u5408\u306f\u7121\u8996\u3055\u308c\u308b\u3002\u6ce8\u6587\u78ba\u5b9a\u6642\u306b\u5f15\u304d\u5f53\u3066\u304c\u884c\u308f\u308c\u308b Fake\u89b3\u5bdf\u6570\u5024 0\u301c100; \u89b3\u5bdf\u5024 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73\u3002","example":0},"price02":{"title":"\u8ca9\u58f2\u4fa1\u683c","description":"\u5b9f\u969b\u306e\u8ca9\u58f2\u4fa1\u683c\uff08\u7a0e\u629c\uff09\u3002\u7a0e\u8a08\u7b97\u30fb\u5c0f\u8a08\u8a08\u7b97\u306e\u30d9\u30fc\u30b9 Fake\u89b3\u5bdf\u6570\u5024 800\u301c28000\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":3500}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goProduct | [<code>page://self/admin/product/edit</code>](/admin/product/edit.md) |
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |