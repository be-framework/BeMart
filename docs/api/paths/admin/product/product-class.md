<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/product-class
EC-CUBE 商品規格 — Product Tier-2 (`admin/Product/product_class.twig`,
the ~448-line product-class matrix editor).

GET /admin/product/product-class?productCode=…  → class-matrix editor

Thin GET renderer. EC-CUBE's editor renders one row per
規格1 × 規格2 class-category cell, each carrying its own
price / stock / stock-unlimited / product-code / shipping-charge
controls. The Be domain has no transition to READ a product's
ProductClass matrix — the ProductClass join is Grade-C 厳密移植 scope
— so this resource renders a blank "新規規格" editor (the
render-smoke test exercises this with empty JSON-backed fake storage), mirroring
the sibling {@see \MyVendor\BeMart\Resource\Page\Admin\Order\ShippingAddress}
GET renderer.

AUTHZ: a direct admin-session check (Pattern B — no Be transition is
invoked on the GET path). No admin session → 403.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Optional |  |  |


### Response

_Not available_