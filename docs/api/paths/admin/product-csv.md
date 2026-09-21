<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-csv
EC-CUBE goExportProduct — 商品CSVをエクスポートする (Wave 8 admin).

onGet only — safe download. Admin-only.

POST imports rows using the same default columns as the export
projection: productCode, productName, price02, stock, productStatus,
description, searchWord, note. Each row is handed to the existing
doCreateProduct Be chain so AUTHZ, semantic validation and duplicate
detection stay in the same place as the form flow.

Failure mapping:
  - UnauthorizedAdminAccessException → 403

Success: 200 with the CSV as the response body's `csv` field and
the row count as `count`. The current first iteration returns the
CSV in the JSON body for testability; an HTTP-streaming Phase 2
variant will set `Content-Type: text/csv` and stream the bytes
directly. The shape here exists so the BEAR + Be integration is
proven end-to-end before adding stream plumbing.




## GET
ALPS `goExportProduct` に対応する GET 操作。

**ALPS**: `goExportProduct`



### Request

_No parameters required_

### Response

[Object: GET /admin/product-csv response](../schemas/get-admin-product-csv.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/product-csv のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csv | string|null | 輸送ペイロード - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |
| doImportProductCsv | [<code>page://self/admin/product-csv</code>](/admin/product-csv.md) |
| goExportCategory | [<code>page://self/admin/category/csv</code>](/admin/category/csv.md) |
## POST
ALPS `doCreateProduct` に対応する POST 操作。

**ALPS**: `doCreateProduct`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string | 輸送ペイロード（入力） - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 |  | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/product-csv response](../schemas/post-admin-product-csv.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | CSVメッセージ - /admin/product-csv のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| count | int|null | 件数 - /admin/product-csv のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| productCodes | array | 取込商品コード一覧 - 商品CSV取込で処理対象になったSKU一覧。各要素は商品コード制約に従う。 | Required | {"items":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 SKU\u3068\u3057\u3066\u5728\u5eab\u30fb\u30ab\u30fc\u30c8\u30fb\u53d7\u6ce8\u660e\u7d30\u3092\u63a5\u7d9a\u3059\u308b\u3002Fake\u89b3\u5bdf\u3067\u306fASCII\u82f1\u6570\u3068\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3002","type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$","example":"sample-001"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goExportCategory | [<code>page://self/admin/category/csv</code>](/admin/category/csv.md) |