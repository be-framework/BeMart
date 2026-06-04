<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /product
EC-CUBE goProduct —商品詳細ページ。

Resource is the HTTP entry point: it builds a Be Input, hands it to
Becoming, and projects the resulting Final into the response body.
All validation and DB access live in the Be domain layer.

Phase 3 — HTML page. The product detail page carries the add-to-cart
action, which EC-CUBE renders as a FORM (`AddCartType` — quantity +,
for class products, the product-class selects). The resource builds
an {@see \AddCartForm} (Ray.WebFormModule AbstractForm), seeds its
hidden `product_id` with the product code, and exposes it as
`body['form']` so the HTML port can render the real quantity
`<input>` via `{{ form.input('quantity') }}`. The form is a
field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
Be Framework Becoming chain (the Cart add-item Input). JSON contexts
(`app`, `prod`, `test`) ignore `body['form']`; the JSON-context tests
assert key-wise on `body` and are unaffected.

FormFactory is self-sufficient (no Ray.Di bindings needed), so the
resource builds the form in every context cheaply; only the `html`
context's TwigRenderer actually renders it.




## GET
Phase B Slice 9: `$productCode` is user input (URI / query param);
declared explicitly so Psalm taint analysis can trace it through
Becoming into any downstream sink. The Be Semantic\ProductCode
constructor format-validates but does not escape — sinks downstream
still need to defend (e.g. bound parameters for SQL).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |


### Response

_Not available_