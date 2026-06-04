<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-category/class-category-export
EC-CUBE 規格分類CSVダウンロード (goExportClassCategory).

GET/POST /admin_product_class_category_export → CSV download

`onGet` drives the Be `goExportClassCategory` transition (optionally
scoped to one 規格名); the EC-CUBE-format encoding + download headers
are isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID |  | Optional |  |  |


### Response

_Not available_