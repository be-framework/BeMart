<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/favorite
EC-CUBE doAddFavorite — お気に入りに追加 (Pilot 13).

AUTHZ via Session (customerId never in body). Idempotent re-add
returns 200 (alreadyExisted=true) rather than 201, so the UI can
distinguish first-add from re-add.




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |


### Response

_Not available_
## DELETE
EC-CUBE doRemoveFavorite — お気に入りから削除 (idempotent inverse
of Pilot 13). DELETE is idempotent (ALPS type=idempotent):
re-removing an already-absent item returns 200 with
alreadyAbsent=true rather than 404. The flag lets the UI
distinguish first-remove from re-remove without leaking the
underlying state.

Unlike onPost, we do NOT validate that productCode resolves to
a real product — DELETE removes a stored row, not a product.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |


### Response

_Not available_