---
layout: default
title: "/admin/csv-config"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/csv-config
EC-CUBE doUpdateCsv — CSV出力設定を更新する (Wave 9).

POST. Admin replaces the column vector for one csvType (order=1,
customer=2, product=3, shipping=4) — each column carries
`columnName`, `enabled`, `sortNo`. The storage replaces the per-type
row set atomically so the column vector cannot drift.

Wave 9 first iteration scope:
  - persists the configuration (the storage holds it; a subsequent
    read sees the write)
  - the export Finals (Wave 8α product, Wave 8β category, Wave 9
    customer) still emit the hardcoded column list — consuming this
    configuration in the exporters is Phase 2.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (csvType / column shape)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
EC-CUBE CSV出力項目設定 — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/csv.twig`. The existing POST
persists a submitted vector; this GET serves the editor body.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| id | int |  | 1 | Optional |  |  |


### Response

_Not available_
## POST
Wave 9: admin-form input. The columns list is sanitized by Be /
Semantic; the column entries themselves carry user-supplied
column names so the taint mark applies to the whole payload.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csvType | int | CSV種別 |  | Required |  |  |
| columns | array |  |  | Required |  |  |


### Response

_Not available_