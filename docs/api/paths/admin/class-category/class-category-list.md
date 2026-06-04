<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-category/class-category-list
EC-CUBE goClassCategoryList + doCreateClassCategory — collection
endpoint (Wave 7).

- GET  → goClassCategoryList   (admin lists VALUES — safe read)
  - POST → doCreateClassCategory (admin adds a new value under one
                                  axis)

Optional `?classNameId=` query parameter narrows the GET list to one
axis; omit it for the unscoped grid view.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID |  | Optional |  |  |


### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID |  | Required |  |  |
| classCategoryName | string | 規格分類名 |  | Required |  |  |


### Response

_Not available_