<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/authority-role
EC-CUBE doUpdateAuthorityRole — 権限ルール更新 (Wave 8).

POST → flip the persisted `authority` column on one admin.

Role-flip is a sub-resource of the admin member rather than a
method on {@see \Member} because its semantics carry distinct
privilege-escalation risk (the Final enforces
`caller.authority < target.authority`). Surfacing a separate URL
(`/admin/authority-role`) keeps the AUTHZ story explicit and
matches the ALPS-level separation of `doUpdateMember` vs
`doUpdateAuthorityRole`. Same architectural choice as Wave 7's
`doUpdateOrderStatus` → {@see \OrderStatus}.

Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
POST / PUT / DELETE — PATCH is not first-class. POST against this
sub-resource carries the same shape as Wave 7 OrderStatus and Wave
6 DeleteCustomer (POST + CSRF + target id + new value).

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (authority format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - AdminNotFoundException                → 404 (unknown loginId)
  - InsufficientAuthorityException        → 403 (priv-escalation refused)

Idempotency: when the supplied `authority` matches the persisted
value, the projection carries `changed=false` and the storage is
untouched. Replay returns 200 with the same body shape — mirrors
AdminOrderStatusUpdated's `changed` discipline (Wave 7).

Mass-assignment safety: only `loginId` (target) and `authority`
(new value) are accepted; no path here reaches the other
dtb_member columns.




## GET
Phase 3 admin HTML Tier-2: render the authority-rule management
screen. The ALPS transition covers `doUpdateAuthorityRole`; EC-CUBE
also has a GET page for editing URL-deny rules. No persisted
`dtb_authority_role` storage exists in BeMart yet, so this GET
exposes the stable form body shape the HTML needs and flags the
rule rows as static placeholders.



### Request

_No parameters required_

### Response

_Not available_
## POST
Wave 8: both `loginId` and `authority` are admin-form input
(loginId from the row selection, authority from a dropdown of
mtb_authority values).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |
| authority | int | 権限 |  | Required |  |  |


### Response

_Not available_