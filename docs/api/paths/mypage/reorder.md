---
layout: default
title: "/mypage/reorder"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/reorder
EC-CUBE doReorder — 再注文 (Mypage/Reorder, Pilot 12).

Repopulates the current customer's cart(s) from a past order.
ALPS: "在庫切れ商品はスキップ、現在価格を適用" — out-of-stock /
discontinued products are skipped, current prices apply.

Failure mapping:
  - SemanticVariableException           → 400 (orderNo malformed)
  - UnauthenticatedException            → 401 (no logged-in customer)
  - UnauthorizedOrderAccessException    → 403 (not the order owner)
  - OrderNotFoundException              → 404 (no such order)
  - CSRF                                → 403 (checked before AUTHN)




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_