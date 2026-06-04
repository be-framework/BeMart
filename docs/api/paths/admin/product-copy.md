<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-copy
EC-CUBE doCopyProduct — 商品をコピーする (Wave 8 admin).

onPost only. CSRF enforced. The Be Final raises (in this order)
UnauthorizedAdmin (403), ProductNotFound (404 — source missing),
ProductCodeAlreadyInUse (409 — target slot occupied). Success: 201
with a Location header pointing at the new product's admin detail
URL.




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| newProductCode | string |  |  | Required |  |  |


### Response

_Not available_