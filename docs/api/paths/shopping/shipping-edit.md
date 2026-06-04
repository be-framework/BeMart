<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/shipping-edit
EC-CUBE goShoppingShippingEdit — お届け先変更フォーム (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Maps to `page://self/shopping/shipping/edit`. Submit target is
doUpdateShippingAddress.

Fields mirror ALPS `#ShoppingShippingEdit`: a guest-shipping-style
address form (10 fields). Production EC-CUBE prepopulates with the
current shipping selection; Wave 3H exposes the empty form shape
only — prefill is left as TODO.

Phase 3 — HTML FORM page. `Shopping/shipping_edit.twig` renders the
address inputs through the Symfony FormView; BeMart exposes a {@see \ShoppingShippingEditForm} (Ray.WebFormModule AbstractForm) as
`body['form']` so the HTML port renders real `<input>`s via
`{{ form.input(...) }}`. JSON contexts ignore `body['form']`.




## GET


### Request

_No parameters required_

### Response

_Not available_