<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product
EC-CUBE admin product surface — combines goProduct (admin variant),
doCreateProduct, doUpdateProduct, doDeleteProduct in one
ResourceObject keyed at `page://self/admin/product`.

Method routing:
  - onGet    — goProduct (admin variant) → 200 / 403 / 404
  - onPost   — doCreateProduct           → 201 / 400 / 403 / 409
  - onPut    — doUpdateProduct           → 200 / 400 / 403 / 404
  - onDelete — doDeleteProduct           → 200 (incl. alreadyDeleted) / 400 / 403 / 404

The customer-facing Product.php (Pilot 1) lives at
`page://self/product` — a sibling resource for the consumer path
(shallow body, no AUTHZ). This admin resource surfaces the full
ProductEntity including the admin-only columns (note, searchWord,
productStatus).

CSRF: enforced on every state-changing method (POST/PUT/DELETE).
The onGet path is read-only and skips CSRF (same convention as
AdminCustomer onGet).




## GET
goProduct (admin variant) — fetch full product detail.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |


### Response

_Not available_
## POST
doCreateProduct — create a new product.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| productName | string | 商品名 |  | Required |  |  |
| price02 | int | 販売価格 |  | Required |  |  |
| stock | int | 在庫数 |  | Optional |  |  |
| productStatus | int | 商品ステータス |  | Optional |  |  |
| description | string |  |  | Optional |  |  |
| searchWord | string | 検索ワード |  | Optional |  |  |
| note | string |  |  | Optional |  |  |


### Response

_Not available_
## PUT
doUpdateProduct — edit an existing product (partial overwrite).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| productName | string | 商品名 |  | Optional |  |  |
| price02 | int | 販売価格 |  | Optional |  |  |
| stock | int | 在庫数 |  | Optional |  |  |
| productStatus | int | 商品ステータス |  | Optional |  |  |
| description | string |  |  | Optional |  |  |
| searchWord | string | 検索ワード |  | Optional |  |  |
| note | string |  |  | Optional |  |  |


### Response

_Not available_
## DELETE
doDeleteProduct — soft-delete (status=3). Idempotent replay
surfaces `alreadyDeleted=true`.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |


### Response

_Not available_