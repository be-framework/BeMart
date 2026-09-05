<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/csv-class-name
EC-CUBE 規格CSV登録 — Product Tier-2
(`admin/Product/csv_class_name.twig`).

GET  /admin/product/csv-class-name → CSV-upload screen
  POST /admin/product/csv-class-name → doImportClassNameCsv

Hard ActionRedirect completion: `onGet` is the upload shell
({@see \AbstractCsvUpload}); `onPost` drives the Be
`doImportClassNameCsv` transition, the parse/persist isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## GET
ALPS `goExportClassName` に対応する GET 操作。

**ALPS**: `goExportClassName`



### Request

_No parameters required_

### Response

[Object: GET /admin/product/csv-class-name response](../schemas/get-admin-product-csv-class-name.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| csvTitle | string|null | CSVタイトル - /admin/product/csv-class-name の画面表示に使うCSVタイトル。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
| skeletonRoute | string|null | スケルトンルート - /admin/product/csv-class-name の画面表示に使うスケルトンルート。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
| columns | array|null | CSV列定義 - /admin/product/csv-class-name のCSV列設定。各要素は出力対象フィールドと表示名を表す。 | Required | {"items":{"type":["object","null"],"title":"CSV\u5217","description":"/admin/product/csv-class-name \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308bCSV\u5217\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `columns` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"description":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8a73\u7d30\u8aac\u660e\u6587","description":"\u5546\u54c1\u8a73\u7d30\u30da\u30fc\u30b8\u306b\u8868\u793a\u3059\u308b\u8aac\u660e\u6587 Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c32; \u89b3\u5bdf\u5024 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u540d\u79f0\u5909\u66f4\u3057\u305f\u3001\u5f69\u308a\u8c4a\u304b\u306a\u30b8\u30a7\u30e9\u30fc\u30c8\u30bb\u30c3\u30c8\u3067\u3059\u3002', '\u7ba1\u7406\u753b\u9762\u304b\u3089\u4f5c\u6210\u3057\u305f\u5546\u54c1'; null 1/7\u3002","example":"Stock-unlimited fixture"},"name":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u5546\u54c1\u540d","description":"/admin/product/csv-class-name \u3067\u8868\u793a\u3059\u308b\u5546\u54c1\u540d\u3002\u691c\u7d22\u30fb\u4e00\u89a7\u30fb\u8a73\u7d30\u3067\u30e6\u30fc\u30b6\u30fc\u306b\u898b\u305b\u308b\u8ca9\u58f2\u540d\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |
## POST
Imports the 規格名 CSV (doImportClassNameCsv).

**ALPS**: `doImportClassNameCsv`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string | 輸送ペイロード（入力） - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 |  | Optional | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","default":""} |  |


### Response

[Object: POST /admin/product/csv-class-name response](../schemas/post-admin-product-csv-class-name.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | CSVメッセージ - /admin/product/csv-class-name のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| accepted | int|boolean|null | 受理件数 - /admin/product/csv-class-name の処理状態を示す受理件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goExportClassCategory | [<code>page://self/admin/class-category/class-category-export</code>](/admin/class-category/class-category-export.md) |