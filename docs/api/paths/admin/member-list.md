---
layout: default
title: "/admin/member-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/member-list
EC-CUBE goMemberList — 管理者一覧 (Wave 8, admin member grid).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
{@see \UnauthorizedAdminAccessException} when
{@see \MyVendor\BeMart\Be\Reason\Service\AdminSession}
reports no admin session; we map that to 403. Distinct from
customer-side 401 (admin and customer firewalls are parallel,
Wave 4 decision).

Failure mapping:
  - SemanticVariableException             → 400 (filter / paging format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Filter scope (Wave 8 first iteration):
  - nameKeyword  — substring on admin's display `name`
  - limit / offset — paginated grid

Hypermedia: links to per-admin detail (goMember), to the create
affordance (doCreateMember), and to the role-flip surface
(doUpdateAuthorityRole). The latter two are admin sub-resources
surfaced here per the bear-hypermedia discipline.




## GET
Wave 8: filter / paging knobs are admin-form input. Same taint
discipline as the customer-list and order-list variants.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string |  |  | Optional |  |  |
| limit | int |  | 50 | Optional |  |  |
| offset | int |  | 0 | Optional |  |  |


### Response

_Not available_