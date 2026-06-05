---
layout: default
title: "/admin/order/export-order"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-order
EC-CUBE goExportOrder — 受注CSVをエクスポートする (Wave 9η).

GET /admin/order/export-order

Light implementation — dumps every finalized order via
{@see \MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface::listAll}.
Search-condition filtering is Phase 2 (mirrors the Wave 8
AdminProductCsv decision).

Failure mapping:
  - UnauthorizedAdminAccessException → 403 (no admin session)




## GET


### Request

_No parameters required_

### Response

_Not available_