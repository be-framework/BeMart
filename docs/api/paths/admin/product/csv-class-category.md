<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/csv-class-category
EC-CUBE 規格分類CSV登録 — Product Tier-2
(`admin/Product/csv_class_category.twig`).

GET  /admin/product/csv-class-category → CSV-upload screen
  POST /admin/product/csv-class-category → doImportClassCategoryCsv

Hard ActionRedirect completion: `onGet` is the upload shell
({@see \AbstractCsvUpload}); `onPost` drives the Be
`doImportClassCategoryCsv` transition, the parse/persist isolated
behind {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## POST
Imports the 規格分類 CSV (doImportClassCategoryCsv).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string |  |  | Optional |  |  |


### Response

_Not available_
## GET


### Request

_No parameters required_

### Response

_Not available_