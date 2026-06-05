---
layout: default
title: "/mypage/order-history"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/order-history
EC-CUBE goOrderHistory — 注文履歴一覧 (Mypage/OrderHistory).

Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer: the
customer's full order history is surfaced from {@see \CustomerSession}'s
customerId, so request-parameter tampering cannot widen the scope to
another customer's orders.

Distinct from `page://self/mypage` (the dashboard, which only carries
the most recent 5 orders for the summary panel): this resource is the
unbounded view, paged by `historyLimit` + `offset`.

Failure mapping:
  - SemanticVariableException → 400 (limit / offset out of range)
  - UnauthenticatedException  → 401 (no / stale session)




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| historyLimit | int |  | 50 | Optional |  |  |
| offset | int |  | 0 | Optional |  |  |


### Response

_Not available_