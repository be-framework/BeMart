---
layout: default
title: "/admin/order/create"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/create
EC-CUBE doCreateOrder — 受注を手動作成する (Wave 9η,
**Phase 2 simplification**).

POST /admin/order/create

Admin-created orders bypass Cart, PaymentMethod::verify(), and the
customer-side checkout entirely (EC-CUBE supports this for phone /
FAX orders entered by back-office staff). The admin posts the
purchased line items (`orderItems`) plus the delivery / charge /
discount columns; {@see \AdminOrderCreated} recomputes subtotal / tax /
total via the shared PurchaseFlow and persists the dtb_order_item
snapshot. The orderNo is allocated server-side via the existing
{@see \MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider} — admins
cannot inject a chosen orderNo.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (field formats)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID |  | Required |  |  |
| paymentMethodId | int |  |  | Required |  |  |
| orderItems | array |  |  | Required |  |  |
| deliveryFeeTotal | int | 送料合計 | 0 | Optional |  |  |
| charge | int | 手数料 | 0 | Optional |  |  |
| discount | int | 値引き額 | 0 | Optional |  |  |


### Response

_Not available_