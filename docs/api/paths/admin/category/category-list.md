<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/category-list
EC-CUBE goCategoryList + doCreateCategory — collection endpoint
(Wave 7).

- GET  → goCategoryList    (admin lists categories — safe read)
  - POST → doCreateCategory  (admin adds a new category)

Single-row affordances (`goCategory`, `doUpdateCategory`,
`doDeleteCategory`) live at `page://self/admin/category/category`.
CSV affordances live at `page://self/admin/category/csv`.

Failure mapping (collapsed admin AUTHZ + CSRF + format):
  - SemanticVariableException             → 400 (parameter format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - CategoryNotFoundException (parentId)  → 404 (referenced parent
                                                 does not exist)
  - CSRF mismatch (POST)                  → 403




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryName | string | カテゴリ名 |  | Required |  |  |
| sortNo | int | 表示順 |  | Required |  |  |
| parentId | string |  |  | Optional |  |  |


### Response

_Not available_