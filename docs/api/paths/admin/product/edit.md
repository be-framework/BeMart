<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product/edit
EC-CUBE 商品登録 / 商品編集 — Product Tier-2 (`admin/Product/product.twig`,
the ~932-line multi-tab product editor).

GET /admin/product/edit                  → blank "new product" editor
  GET /admin/product/edit?productCode=…    → editor pre-filled for one product

Thin GET renderer. The sibling JSON resource
{@see \MyVendor\BeMart\Resource\Page\Admin\Product} carries the
`goProduct` read + `doCreateProduct` / `doUpdateProduct` /
`doDeleteProduct` writes; this resource is the HTML editor shell
only. An empty `$productCode` renders the blank editor (EC-CUBE's
"商品登録" path — the render-smoke test exercises this with empty
JSON-backed fake storage); a known productCode pre-fills; an unknown productCode
is 404.

AUTHZ: the blank-editor path checks the admin session directly
(Pattern B — no Be transition is invoked when there is no product to
read); the pre-fill path delegates to {@see \AdminProductFetched},
which raises {@see \UnauthorizedAdminAccessException} for a non-admin
firewall. Both surface 403.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Optional |  |  |


### Response

_Not available_