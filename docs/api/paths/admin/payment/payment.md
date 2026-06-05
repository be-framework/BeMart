---
layout: default
title: "/admin/payment/payment"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/payment/payment
EC-CUBE doUpdatePayment + doDeletePayment — single-row endpoint
(Wave 9θ).

- GET    → goPaymentEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
- PUT    → doUpdatePayment (admin edits a payment master — idempotent)
- DELETE → doDeletePayment (admin removes a payment master — idempotent)




## GET
EC-CUBE 支払方法設定（編集） — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/payment_edit.twig`. An empty
`$paymentId` renders a blank "new payment" form; a known id
pre-fills the editor; an unknown id is 404. The payment-master
list doubles as the AUTHZ gate — no admin session → 403.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID |  | Optional |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID |  | Required |  |  |
| paymentMethodName | string | 支払方法名 |  | Optional |  |  |
| charge | int | 手数料 |  | Optional |  |  |
| ruleMin | int |  |  | Optional |  |  |
| ruleMax | int |  |  | Optional |  |  |
| visible | bool |  |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID |  | Required |  |  |


### Response

_Not available_