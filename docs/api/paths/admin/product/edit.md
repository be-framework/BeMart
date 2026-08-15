<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/edit
EC-CUBE 商品登録 / 商品編集 — Product Tier-2 (`admin/Product/product.twig`,
the ~932-line multi-tab product editor).

GET /admin/product/edit                  → blank "new product" editor
  GET /admin/product/edit?productCode=…    → editor pre-filled for one product

Thin GET renderer. The sibling JSON resource
{@see \MyVendor\BeMart\Resource\Page\Admin\Product} carries the
`goProduct` read + `doCreateProduct` / `doUpdateProduct` /
`doDeleteProduct` writes; this resource is the HTML editor shell
only. An empty `$productCode` renders the blank editor (EC-CUBE's
"商品登録" path — the render-smoke test exercises this with empty
JSON-backed fake storage); a known productCode pre-fills; an unknown productCode
is 404.

AUTHZ: the blank-editor path checks the admin session directly
(Pattern B — no Be transition is invoked when there is no product to
read); the pre-fill path delegates to {@see \AdminProductFetched},
which raises {@see \UnauthorizedAdminAccessException} for a non-admin
firewall. Both surface 403.




## GET
ALPS `goProduct` に対応する GET 操作。

**ALPS**: `goProduct` - 商品詳細を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: GET /admin/product/edit response](../schemas/get-admin-product-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| product | array|null|object | 商品詳細 - /admin/product/edit のレスポンスで扱う商品詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u5546\u54c1","minLength":0,"maxLength":255,"description":"/admin/product/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `product` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |

#### Links

| Relation | URL |
|----------|-----|
| doCreateProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| doUpdateProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |