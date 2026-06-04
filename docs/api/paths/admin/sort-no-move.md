<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/sort-no-move
EC-CUBE doSortNoMove — 並び順を変更する (Phase 3 ALPS-audit
remediation).

PUT /admin/sort-no-move

The generic admin-list reorder transition. EC-CUBE has a per-master
*_sort_no_move route for each list screen (Payment / Delivery / Tag /
ClassName / ClassCategory); BeMart folds them into this one resource
keyed by `masterType`. ALPS marks it `idempotent` — PUT is the verb.

Failure mapping:
  - Invalid CSRF                            → 403
  - SemanticVariableException               → 400 (masterType / sortNo)
  - UnauthorizedAdminAccessException        → 403 (no admin session)
  - MasterRowNotFoundException              → 404
  - MasterOperationNotSupportedException    → 400 (master lacks sort_no)




## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string |  |  | Required |  |  |
| rowId | string |  |  | Required |  |  |
| sortNo | int | 表示順 |  | Required |  |  |


### Response

_Not available_