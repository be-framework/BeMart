---
layout: default
title: "/admin/order/tracking-number"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/tracking-number
EC-CUBE doUpdateTrackingNumber — 伝票番号を更新する (Phase 3
ALPS-audit remediation).

PUT /admin/order/tracking-number

Inline single-row update of an order's shipping tracking number,
derived from EC-CUBE's `admin_shipping_update_tracking_number` route.
ALPS marks it `idempotent`; PUT is the matching verb.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo / trackingNumber)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |
| trackingNumber | string | 追跡番号 |  | Required |  |  |


### Response

_Not available_