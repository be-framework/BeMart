<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-csv
EC-CUBE goExportCustomer — 会員CSVをエクスポートする (Wave 9).

onGet only — safe download. Admin-only. Mirrors Wave 8α's
{@see \ProductCsv} and Wave 8β's {@see \Category\Csv} pattern.

Failure mapping:
  - UnauthorizedAdminAccessException → 403

Success: 200 with the CSV as the response body's `csv` field and the
row count as `rowCount`. The Final emits the RFC 4180 dump via PHP's
native fputcsv() (same approach as Wave 8β CategoryCsvExported); the
Resource layer sets the `Content-Type: text/csv` and
`Content-Disposition: attachment` headers.




## GET
ALPS `goExportCustomer` に対応する GET 操作。

**ALPS**: `goExportCustomer` - 会員CSVをエクスポートする



### Request

_No parameters required_

### Response

[Object: GET /admin/customer-csv response](../schemas/get-admin-customer-csv.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| rowCount | int|null | 行数 - /admin/customer-csv のレスポンスで返す行数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csv | string|null | 輸送ペイロード - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |
| goExportClassName | [<code>page://self/admin/class-name/class-name-export</code>](/admin/class-name/class-name-export.md) |