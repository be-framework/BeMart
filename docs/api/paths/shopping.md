<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping
EC-CUBE goShopping — 注文情報入力画面 (Pilot — checkout review).

Safe read. No CSRF (read-only). AUTHN required — Be Final raises
UnauthenticatedException when the session has no customerId, which
we map to 401. Aggregates the customer's default shipping fields,
the current carts under the active sessionPrefix, and the list of
user-selectable payment methods into a single review projection.

Empty-cart handling: 200 with `canCheckout = false` rather than
404. The frontend renders the "カートが空です" panel in that case;
the customer can navigate back to `goCart` to add items.

Failure mapping:
  - SemanticVariableException → 400 (sessionPrefix malformed)
  - UnauthenticatedException  → 401 (no / stale session)

Coexists with `Resource\Page\Shopping\` directory (which holds
Checkout.php from Pilot 5) — the same file-plus-sibling-directory
pattern as Mypage.

Phase 3 — HTML FORM page. `Shopping/index.twig` is form-heavy: the
checkout page carries the order message textarea + the delivery /
payment selection controls. The resource builds a {@see \ShoppingOrderForm} (Ray.WebFormModule AbstractForm) and exposes it as
`body['form']` so the HTML port renders real `<input>` / `<select>`
markup via `{{ form.input(...) }}`. The form is a field-definition +
renderer only — VALIDATION AUTHORITY STAYS WITH the Be Becoming chain
(doCheckout / CheckoutInput). The JSON contexts ignore `body['form']`;
the JSON-context tests assert key-wise on `body` and are unaffected.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| sessionPrefix | string |  | session-prefix-1 | Optional |  |  |


### Response

_Not available_