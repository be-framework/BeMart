---
layout: default
title: "/admin/customer-csv"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-csv
EC-CUBE goExportCustomer — 会員CSVをエクスポートする (Wave 9).

onGet only — safe download. Admin-only. Mirrors Wave 8α's
{@see \ProductCsv} and Wave 8β's {@see \Category\Csv} pattern.

Failure mapping:
  - UnauthorizedAdminAccessException → 403

Success: 200 with the CSV as the response body's `csv` field and the
row count as `rowCount`. The Final emits the RFC 4180 dump via PHP's
native fputcsv() (same approach as Wave 8β CategoryCsvExported); the
Resource layer sets the `Content-Type: text/csv` and
`Content-Disposition: attachment` headers.




## GET


### Request

_No parameters required_

### Response

_Not available_