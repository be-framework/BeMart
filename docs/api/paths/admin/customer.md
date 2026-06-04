<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer
EC-CUBE goCustomer — 会員詳細を見る（管理画面）.

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when the admin session is empty,
which this resource maps to 403. Aggregates full profile + complete
order history + favorites list into a flat admin detail projection.

Failure mapping (cross-firewall AUTHZ → existence ladder):
  - SemanticVariableException            → 400 (email format invalid)
  - UnauthorizedAdminAccessException     → 403 (no admin session)
  - CustomerNotFoundException            → 404 (no such email)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which emails resolve
(same anti-enumeration discipline as the customer-side Pilot 8 /
Pilot 12 AUTHN-first ladders).

Unlike the customer's own goMypage, this surface is the FULL profile
(birth, sex, job, full address, point balance, registrationDate
analogue), FULL order history (capped at 50 with derived totalSpent),
and FULL favorites list (not just the count). The admin back-office
needs the richer projection — drill-downs into individual orders /
favorites are deferred to dedicated admin endpoints.

Mirrors {@see \Login} / {@see \Logout} for the admin firewall —
distinct namespace under `Page\Admin\` (URI prefix
`page://self/admin/...`). Coexists with a potential future
`Page\Admin\Customer\` sibling directory: PHP allows a file and a
sibling directory of the same name to share a namespace prefix
(same as `Resource\Page\Mypage.php` + `Resource\Page\Mypage\`).




## GET
Wave 5: the email comes from the admin UI (typed input or query
string), so it is user-controlled — same taint discipline as the
customer-side LoginResource.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Optional |  |  |
| customerId | string | 会員ID |  | Optional |  |  |
| id | string |  |  | Optional |  |  |


### Response

_Not available_