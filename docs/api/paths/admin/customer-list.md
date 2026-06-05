---
layout: default
title: "/admin/customer-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-list
EC-CUBE goCustomerList — 会員一覧 (Wave 5, admin filter search).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when AdminSession reports
no admin session, which we map to 403. Distinct from customer-side
401 (Unauthenticated): admin and customer firewalls are parallel and
a logged-in customer is NOT logged-in-as-admin (Wave 4 decision).

Failure mapping:
  - SemanticVariableException             → 400 (filter format invalid)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Filter scope (Wave 5 first iteration):
  - nameKeyword  — substring on name01/name02/companyName
  - emailKeyword — substring on email
  - limit        — caps the result set (default 50)
  Phase 2 will add phoneNumber, dateRange, purchaseAmount filters.

Hypermedia: links to the per-customer admin detail and the admin
customer-create endpoints. Those are Wave 5+ scope; the link targets
exist as resource URIs but the resources themselves are deferred —
the BEAR layer is forward-declaring the affordances per the
`bear-skills:bear-hypermedia` discipline.




## GET
Wave 5: filter fields are admin-form input — taint discipline
mirrors the Wave 4 admin login.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string |  |  | Optional |  |  |
| emailKeyword | string |  |  | Optional |  |  |
| limit | int |  | 50 | Optional |  |  |


### Response

_Not available_