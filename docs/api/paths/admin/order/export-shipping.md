---
layout: default
title: "/admin/order/export-shipping"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-shipping
EC-CUBE goExportShipping — 配送CSVをエクスポートする (Wave 9η).

GET /admin/order/export-shipping

Pairs with {@see \ImportShipping} — the admin workflow is
"download → fill tracking numbers offline → upload back". Wave 9η
surfaces the export half real, the import half stub (parser is
Phase 2).

Failure mapping:
  - UnauthorizedAdminAccessException → 403 (no admin session)




## GET


### Request

_No parameters required_

### Response

_Not available_