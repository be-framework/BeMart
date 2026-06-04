<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /entry/activate
EC-CUBE doActivateCustomer — provisional → active (Pilot 7).

The email-link UX in EC-CUBE is GET, but the operation has side
effects (status flip + secretKey clear) so the Be migration uses
onPost behind a one-button confirmation form. Both the secretKey
and a CSRF token are submitted; the secretKey is the per-customer
proof-of-email-receipt, and the CSRF token guards against drive-by
activation triggered by another origin.

Failure mapping:
  - SemanticVariableException    → 400 (secretKey malformed)
  - SecretKeyNotFoundException   → 404 (wrong key / expired / already used)

Idempotent: re-activating a customer is a no-op on the storage side
but still redirects from this resource — the caller cannot tell
"first activate" from "second activate", which is correct.

Phase 3 — `onGet` is the email-verification-complete LANDING SCREEN.
EC-CUBE's `doActivateCustomer` controller renders `Entry/activate.twig`
(the "本登録が完了しました" page) after the status flip; `onPost`
performs the flip. The `onGet` here is a THIN PURE RENDERER for that
landing screen — no Be Framework, no domain logic — added so Phase 3
has a page to render `Entry/activate.twig` against. The template's
optional `{% if qtyInCart %}` cart button is gated behind a cart-state
field the thin-renderer body does not carry; the common case (no
pending cart) renders only the top-page button, recorded as a residual
in the render test.




## GET
EC-CUBE doActivateCustomer landing — the email-verification-complete
screen. Pure renderer: the body surfaces only the screen shape + the
outbound `goTop` transition (ALPS `#CustomerActivationComplete`).



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| secretKey | string | 認証キー |  | Required |  |  |


### Response

_Not available_