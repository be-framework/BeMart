---
layout: default
title: "/admin/payment/payment-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/payment/payment-list
EC-CUBE goPaymentList + doCreatePayment — collection endpoint
(Wave 9θ).

- GET  → goPaymentList   (admin lists payment masters — safe read)
  - POST → doCreatePayment (admin adds a new payment master)

Single-row affordances (`doUpdatePayment`, `doDeletePayment`) live
at `page://self/admin/payment/payment`.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentMethodName | string | 支払方法名 |  | Required |  |  |
| charge | int | 手数料 | 0 | Optional |  |  |
| ruleMin | int |  |  | Optional |  |  |
| ruleMax | int |  |  | Optional |  |  |
| visible | bool |  | 1 | Optional |  |  |


### Response

_Not available_