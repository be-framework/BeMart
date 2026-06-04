<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-category/class-category
EC-CUBE doUpdateClassCategory + doDeleteClassCategory — single-row
endpoint (Wave 7).

- PUT    → doUpdateClassCategory (admin renames a value —
                                  idempotent)
- DELETE → doDeleteClassCategory (admin removes a value —
                                  idempotent)




## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classCategoryId | string | 規格分類ID |  | Required |  |  |
| classCategoryName | string | 規格分類名 |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classCategoryId | string | 規格分類ID |  | Required |  |  |


### Response

_Not available_