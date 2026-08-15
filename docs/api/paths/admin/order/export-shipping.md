<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-shipping
EC-CUBE goExportShipping — 配送CSVをエクスポートする (Wave 9η).

GET /admin/order/export-shipping

Pairs with {@see \ImportShipping} — the admin workflow is
"download → fill tracking numbers offline → upload back". Wave 9η
surfaces the export half real, the import half stub (parser is
Phase 2).

Failure mapping:
  - UnauthorizedAdminAccessException → 403 (no admin session)




## GET
ALPS `goExportShipping` に対応する GET 操作。

**ALPS**: `goExportShipping` - 配送CSVをエクスポートする



### Request

_No parameters required_

### Response

[Object: GET /admin/order/export-shipping response](../schemas/get-admin-order-export-shipping.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| rowCount | int|null | 行数 - /admin/order/export-shipping のレスポンスで返す行数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csv | string|null | 輸送ペイロード - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
| doImportShippingCsv | [<code>page://self/admin/order/import-shipping</code>](/admin/order/import-shipping.md) |