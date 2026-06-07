<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-new
EC-CUBE admin Product/new — 商品登録フォーム。

The write-side API already exists as Admin\Product::onPost(); this
page is the missing browser UI entry. It is intentionally a first
slice: fields are limited to the current AdminCreateProductInput body
contract (code/name/price/stock/status/description/searchWord/note).




## GET
ALPS `doCreateProduct` に対応する GET 操作。

**ALPS**: `doCreateProduct`



### Request

_No parameters required_

### Response

[Object: GET /admin/product-new response](../schemas/get-admin-product-new.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| productStatusOptions | object | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） | Required | {"$comment":"\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u7531\u6765\u307e\u305f\u306f\u52d5\u7684map\u306e\u305f\u3081\u3001JSON\u5883\u754c\u3067\u306fobject\u3067\u3042\u308b\u3053\u3068\u3068\u610f\u5473\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u30ad\u30fc\u306f\u5225\u5883\u754c\u3067\u6271\u3046\u3002\u8ffd\u52a0\u30ad\u30fc\u306f\u4e0d\u900f\u660e\u69cb\u9020\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doCreateProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |