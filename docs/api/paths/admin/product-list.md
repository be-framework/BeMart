---
layout: default
title: "/admin/product-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-list
EC-CUBE goProductList — 商品一覧（管理画面） (Wave 8, admin filter
search + pagination).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when AdminSession reports
no admin session, which we map to 403. The customer-facing product
list (when it lands) will be a sibling resource at a different URL.

Failure mapping:
  - SemanticVariableException             → 400 (filter format invalid)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Hypermedia: links to per-product admin detail + CSV export + bulk
status update endpoints — the operator drills into a row from the
grid, exports the corpus, or applies a bulk action.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string |  |  | Optional |  |  |
| limit | int |  | 50 | Optional |  |  |
| offset | int |  | 0 | Optional |  |  |


### Response

_Not available_