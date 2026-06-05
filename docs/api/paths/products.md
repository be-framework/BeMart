---
layout: default
title: "/products"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /products
EC-CUBE goProductList — 商品一覧ページ.

Anonymous-accessible (returns 200 regardless of session state). Maps
to `page://self/products`, the target of the `goProductList`
transition declared on Index / Login and of the storefront header
search block.

Earlier Phase 3 rendered only the empty-result scaffold. That kept the
EC-CUBE template port small but made the top-page "全ての商品" flow a
dead end. This resource now reads the existing ProductQuery corpus,
filters to public products, and projects the small row shape expected
by the ported EC-CUBE `Product/list.twig`.




## GET
EC-CUBE goProductList — render the product-list page.

`name` is the EC-CUBE header search field. `nameKeyword` is accepted
as a BeMart/API-friendly alias.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name | string |  |  | Optional |  |  |
| nameKeyword | string |  |  | Optional |  |  |
| limit | int |  | 50 | Optional |  |  |
| offset | int |  | 0 | Optional |  |  |
| category_id | string |  |  | Optional |  |  |
| pageno | string |  |  | Optional |  |  |
| disp_number | string |  |  | Optional |  |  |
| orderby | string |  |  | Optional |  |  |


### Response

_Not available_