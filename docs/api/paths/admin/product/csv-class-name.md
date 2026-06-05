---
layout: default
title: "/admin/product/csv-class-name"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/csv-class-name
EC-CUBE 規格CSV登録 — Product Tier-2
(`admin/Product/csv_class_name.twig`).

GET  /admin/product/csv-class-name → CSV-upload screen
  POST /admin/product/csv-class-name → doImportClassNameCsv

Hard ActionRedirect completion: `onGet` is the upload shell
({@see \AbstractCsvUpload}); `onPost` drives the Be
`doImportClassNameCsv` transition, the parse/persist isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## POST
Imports the 規格名 CSV (doImportClassNameCsv).



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