<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/toggle-visible
EC-CUBE doToggleVisible — 表示・非表示を切り替える (Phase 3
ALPS-audit remediation).

PUT /admin/toggle-visible

The generic admin-list visibility transition. EC-CUBE has a
per-master *_visible / *_visibility route for each list screen
(Payment / Delivery / ClassCategory / News); BeMart folds them into
this one resource keyed by `masterType`. ALPS marks it `idempotent`
— the flag is set to an explicit `visible` value, so PUT is the verb.

Failure mapping:
  - Invalid CSRF                            → 403
  - SemanticVariableException               → 400 (masterType / visible)
  - UnauthorizedAdminAccessException        → 403 (no admin session)
  - MasterRowNotFoundException              → 404
  - MasterOperationNotSupportedException    → 400 (master lacks visible)




## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string |  |  | Required |  |  |
| rowId | string |  |  | Required |  |  |
| visible | bool |  |  | Required |  |  |


### Response

_Not available_