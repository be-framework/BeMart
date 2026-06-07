<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-name/class-name-export
EC-CUBE 規格名CSVダウンロード (goExportClassName).

GET/POST /admin_product_class_name_export → CSV download

`onGet` drives the Be `goExportClassName` transition; the EC-CUBE-format
encoding + download headers are isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## GET
ALPS `goExportClassName` に対応する GET 操作。

**ALPS**: `goExportClassName`



### Request

_No parameters required_

### Response

[Object: GET /admin/class-name/class-name-export response](../schemas/get-admin-class-name-class-name-export.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| value | string | CSVエクスポート本文 - /admin/class-name/class-name-export が返すCSV本文。列意味はCSV互換サービス側、JSON境界では文字列として契約する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u610f\u5473\u691c\u67fb\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u6271\u3044\u3001\u3053\u3053\u3067\u306f\u30ec\u30b9\u30dd\u30f3\u30b9\u672c\u6587\u3068\u3057\u3066\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u691c\u67fb\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doImportClassNameCsv | [<code>page://self/admin/product/csv-class-name</code>](/admin/product/csv-class-name.md) |