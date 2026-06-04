<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/shipping-multiple-edit
EC-CUBE goShoppingShippingMultipleEdit — 複数配送の新規お届け先追加フォーム
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE reaches
`Shopping/shipping_multiple_edit.twig` from the multi-destination
screen ({@see \ShippingMultiple}) via the "新規お届け先を追加する"
link; it adds a shipping address that the multi-destination split UI
can then assign cart items to. On submit the flow returns to the
multi-destination screen.

BeMart's ALPS surface models the multi-destination allocation as a
Wave-future vertical-slice (see {@see \ShippingMultiple}), so no
`ShoppingShippingMultipleEdit` SCREEN resource ever existed. Phase 3
needs a page to render `Shopping/shipping_multiple_edit.twig` against,
so this THIN PURE RENDERER is added: no Be Framework, no domain logic,
no Reasons.

`Shopping/shipping_multiple_edit.twig` is a FORM page — EC-CUBE renders
its address inputs through the Symfony FormView (the SAME
`CustomerAddressType` used by `Shopping/shipping_edit.twig`). The form
shape is therefore identical to {@see \ShippingEdit}'s, so this resource
reuses {@see \ShoppingShippingEditForm} (an AbstractForm) — exposed as
`body['form']` so the HTML port renders real `<input>`s via
`{{ form.input(...) }}`. JSON contexts ignore `body['form']`. The two
pages differ only in the submit-target route + the page header text;
the address form definition itself is shared.

Maps to `page://self/shopping/shipping-multiple-edit`.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST
EC-CUBE doUpdateShippingAddress — add/update an address for the
multi-destination checkout branch.

This mirrors {@see \ShippingEdit::onPost()} but returns to the
multi-shipping screen instead of the main shopping screen.



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