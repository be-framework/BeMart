---
layout: default
title: "/admin/category/csv"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/csv
EC-CUBE goExportCategory + doImportCategoryCsv — CSV endpoint
(Wave 7).

- GET  → goExportCategory   (RFC 4180 dump — admin AUTHZ)
  - POST → doImportCategoryCsv (**Phase 2 stub** — accepts the body
                                but does not persist; ALPS/AUTHZ
                                contract is exercised, full parser
                                deferred)

Both methods enforce the admin firewall. The stubbed import path
returns `accepted=false` with an explanatory notice so callers
cannot mistake the stub for a real import.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string |  |  | Required |  |  |


### Response

_Not available_