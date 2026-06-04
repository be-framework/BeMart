---
layout: default
title: "/admin/product-bulk-status"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-bulk-status
EC-CUBE doBulkUpdateProductStatus — 商品ステータスを一括変更する
(Wave 8 admin).

onPost only. CSRF enforced. The Final silently skips unknown codes;
`requestedCount` vs `changedCount` lets the UI surface anomalies
(a stale grid row, an already-aligned status, etc.).




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCodes | array |  |  | Required |  |  |
| productStatus | int | 商品ステータス |  | Required |  |  |


### Response

_Not available_