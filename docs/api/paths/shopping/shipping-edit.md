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
## POST
EC-CUBE doUpdateShippingAddress — accept the edited shipping address.

The BeMart checkout page still keeps the richer pre-order shipping
persistence as a later enrichment. This method removes the former
ActionRedirect placeholder and gives the submitted address a concrete
Resource surface while returning the user to the main shopping page.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name01 | string | 姓 |  | Optional |  |  |
| name02 | string | 名 |  | Optional |  |  |
| kana01 | string | セイ |  | Optional |  |  |
| kana02 | string | メイ |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |
| postalCode | string | 郵便番号 |  | Optional |  |  |
| pref | int | 都道府県 | 0 | Optional |  |  |
| addr01 | string | 市区町村 |  | Optional |  |  |
| addr02 | string | 番地・建物名 |  | Optional |  |  |
| phoneNumber | string | 電話番号 |  | Optional |  |  |


### Response

_Not available_