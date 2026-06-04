---
layout: default
title: "/admin/product-csv"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-csv
EC-CUBE goExportProduct — 商品CSVをエクスポートする (Wave 8 admin).

onGet only — safe download. Admin-only.

ALPS counterpart `doImportProductCsv` is INTENTIONALLY NOT
implemented in Wave 8: the EC-CUBE importer parses dtb_product
shaped CSV rows with insert-or-update semantics + multi-column
uniqueness contracts + extended PurchaseFlow orchestration. That
depth doesn't fit a single-day migration and would force the
JSON-backed fake product handler to grow a bulk-upsert surface that contradicts
the CQRS split. Phase 2 will land it as a dedicated Cascade Diamond
pattern (`insurance-claim` demo). The ALPS id remains documented;
no Be Input or BEAR resource is shipped for it yet.

Failure mapping:
  - UnauthorizedAdminAccessException → 403

Success: 200 with the CSV as the response body's `csv` field and
the row count as `count`. The current first iteration returns the
CSV in the JSON body for testability; an HTTP-streaming Phase 2
variant will set `Content-Type: text/csv` and stream the bytes
directly. The shape here exists so the BEAR + Be integration is
proven end-to-end before adding stream plumbing.




## GET


### Request

_No parameters required_

### Response

_Not available_