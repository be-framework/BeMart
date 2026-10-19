<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/csv-product
EC-CUBE 商品CSV登録 — Product Tier-2 (`admin/Product/csv_product.twig`).

GET /admin/product/csv-product → CSV-upload screen

Thin GET renderer — see {@see \AbstractCsvUpload}. The matching
`doImportProductCsv` write transition is a Phase-A stub; the export
download lives at the sibling action-only
{@see \MyVendor\BeMart\Resource\Page\Admin\ProductCsv}.




## GET
ALPS `goExportProduct` に対応する GET 操作。

**ALPS**: `goExportProduct`



### Request

_No parameters required_

### Response

[Object: GET /admin/product/csv-product response](../schemas/get-admin-product-csv-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| csvTitle | string|null | CSVタイトル - /admin/product/csv-product の画面表示に使うCSVタイトル。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
| skeletonRoute | string|null | スケルトンルート - /admin/product/csv-product の画面表示に使うスケルトンルート。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
| columns | array|null | CSV列定義 - /admin/product/csv-product のCSV列設定。各要素は出力対象フィールドと表示名を表す。 | Required | {"items":{"type":["object","null"],"title":"CSV\u5217","description":"/admin/product/csv-product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308bCSV\u5217\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `columns` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"description":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8a73\u7d30\u8aac\u660e\u6587","description":"\u5546\u54c1\u8a73\u7d30\u30da\u30fc\u30b8\u306b\u8868\u793a\u3059\u308b\u8aac\u660e\u6587 Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c32; \u89b3\u5bdf\u5024 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u540d\u79f0\u5909\u66f4\u3057\u305f\u3001\u5f69\u308a\u8c4a\u304b\u306a\u30b8\u30a7\u30e9\u30fc\u30c8\u30bb\u30c3\u30c8\u3067\u3059\u3002', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u4f5c\u6210\u3057\u305f\u5546\u54c1'; null 1/7\u3002","example":"Stock-unlimited fixture"},"name":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u5546\u54c1\u540d","description":"/admin/product/csv-product \u3067\u8868\u793a\u3059\u308b\u5546\u54c1\u540d\u3002\u691c\u7d22\u30fb\u4e00\u89a7\u30fb\u8a73\u7d30\u3067\u30e6\u30fc\u30b6\u30fc\u306b\u898b\u305b\u308b\u8ca9\u58f2\u540d\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |