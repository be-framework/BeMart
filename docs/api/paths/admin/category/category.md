<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/category
EC-CUBE goCategory + doUpdateCategory + doDeleteCategory —
single-row endpoint (Wave 7).

- GET    → goCategory       (admin views one)
  - PUT    → doUpdateCategory (admin edits in place — idempotent)
  - DELETE → doDeleteCategory (admin removes — idempotent)

`categoryId` is the lookup key. The Be Finals enforce the admin
AUTHZ ladder; this resource maps exceptions to HTTP codes.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID |  | Required |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID |  | Required |  |  |
| categoryName | string | カテゴリ名 |  | Optional |  |  |
| sortNo | int | 表示順 |  | Optional |  |  |
| parentId | string |  |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID |  | Required |  |  |


### Response

_Not available_