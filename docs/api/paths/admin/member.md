<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/member
EC-CUBE goMember / doCreateMember / doUpdateMember / doDeleteMember
— 管理者 (Wave 8). The four verbs on the admin-member detail
resource share one URL (`page://self/admin/member`) and dispatch by
HTTP method:

- GET    → goMember            (safe read, no CSRF)
  - POST   → doCreateMember      (unsafe, CSRF, multi-Reason Being)
  - PUT    → doUpdateMember      (idempotent, CSRF, name/mail merge)
  - DELETE → doDeleteMember      (idempotent, CSRF, soft-delete)

All four are admin-only. The Be Finals raise
{@see \UnauthorizedAdminAccessException} when no admin session is
present; we map that to 403 here.

Distinct from the role-flip surface ({@see \AuthorityRole}) — that
goes through its own URL because the privilege-escalation guard
needs to be observable in the resource layout.

Failure mapping (common to all four):
  - Invalid CSRF                          → 403 (POST/PUT/DELETE)
  - SemanticVariableException             → 400 (any field format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - AdminNotFoundException                → 404 (no such loginId)

POST-only:
  - LoginIdAlreadyTakenException          → 409 (loginId conflict)

DELETE-only:
  - InsufficientAuthorityException        → 403 (caller targeting self)




## GET
Wave 8: the loginId comes from the admin UI (typed input or
query string) — user-controlled.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Optional |  |  |


### Response

_Not available_
## POST
Wave 8: all form fields are user-controlled. The admin AUTHZ
check lives inside the first Being (MemberCreating), so this
method just maps the exceptions.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |
| password | string | パスワード |  | Required |  |  |
| name | string |  |  | Required |  |  |
| authority | int | 権限 |  | Required |  |  |


### Response

_Not available_
## PUT
Wave 8: doUpdateMember — edits `name` only. The other admin
fields (authority, work, passwordHash) have their own dedicated
transitions / are out of scope for Phase 1. EC-CUBE 4.3
dtb_member has no email column, so no mailAddress field is
accepted.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |
| name | string |  |  | Optional |  |  |


### Response

_Not available_
## DELETE
Wave 8: doDeleteMember — soft-delete (work=0). Idempotent
replay returns 200 with `alreadyDeleted=true`. Self-target
raises {@see InsufficientAuthorityException} → 403.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |


### Response

_Not available_