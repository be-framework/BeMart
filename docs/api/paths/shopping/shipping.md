<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/shipping
EC-CUBE goShoppingShipping — お届け先選択画面 (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Maps to `page://self/shopping/shipping`. The submit target is
doSelectShippingAddress.

Production EC-CUBE populates the body with the authenticated
customer's registered shipping address list. Wave 3H exposes the
shape only; the data lookup (customer's address book under the
active pre-order) is left as TODO until a dedicated aggregation
lands — the renderer is intentionally anonymous-permissive (matches
other Shopping/* renderers under the Wave 3H scope).




## GET


### Request

_No parameters required_

### Response

_Not available_