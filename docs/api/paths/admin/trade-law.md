<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/trade-law
EC-CUBE doUpdateTradeLaw + goTradeLawList — 特定商取引法 (Wave 8 + Wave 9).

- GET  → goTradeLawList (safe read, admin AUTHZ, Wave 9ι)
  - POST → doUpdateTradeLaw (idempotent, admin AUTHZ + CSRF, Wave 8ε)

Wave 8 first iteration treats the page as a single body blob; Phase 2
will split into per-item rows.

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (body length)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
Wave 9ι: goTradeLawList — admin views the current TradeLaw body.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| tradeLawBody | string | 特定商取引法本文 |  | Required |  |  |


### Response

_Not available_