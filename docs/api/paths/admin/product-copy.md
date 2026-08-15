<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-copy
EC-CUBE doCopyProduct — 商品をコピーする (Wave 8 admin).

onPost only. CSRF enforced. The Be Final raises (in this order)
UnauthorizedAdmin (403), ProductNotFound (404 — source missing),
ProductCodeAlreadyInUse (409 — target slot occupied). Success: 201
with a Location header pointing at the new product's admin detail
URL.




## POST
ALPS `doCopyProduct` に対応する POST 操作。

**ALPS**: `doCopyProduct` - 商品をコピーする



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| newProductCode | string | 処理識別子（入力） - /admin/product-copy のレスポンスで扱う処理識別子。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: POST /admin/product-copy response](../schemas/post-admin-product-copy.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| productCode | string|null | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| newProductCode | string|null | 処理識別子 - /admin/product-copy のレスポンスで扱う処理識別子。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| newProductName | string | 表示項目 - /admin/product-copy の画面表示に使う表示項目。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":1,"maxLength":255} |  |
| price02 | int|null | 販売価格 - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 | Required | {"minimum":0,"maximum":999999999} | 3500 |
| stock | int|null | 在庫数 - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 | Optional | {"minimum":0,"maximum":2147483647} | 0 |

#### Links

| Relation | URL |
|----------|-----|
| goProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |