<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/checkout
EC-CUBE doCheckout —注文確定 (Shopping/Checkout).

Resource is the HTTP entry point: builds CheckoutInput, hands it to
Becoming, and projects the resulting CheckoutCompleted into the
ShoppingComplete response body. Pilot 5 deliberately maps Reason-thrown
DomainExceptions to HTTP codes (ShoppingError 422 / 404) rather than
routing through a Branching Final — Branching itself was already covered
by Pilot 3, so we keep the failure path simple.

Failure mapping (per `be/docs/pilot5/alps-analyze.md` §例外フロー):
  - PreOrderNotFoundException           → 404 (the pre-order never existed)
  - UnauthorizedPreOrderAccessException → 403 (not the owner; Pilot 5 F-1)
  - InsufficientStockException          → 422 (stock cannot fulfill the order)
  - PaymentDeclinedException            → 422 (gateway refused the charge)
  - SemanticVariableException           → 400 (preOrderId malformed)

Note: paymentMethodId is intentionally NOT accepted here. It is sourced
from the persisted OrderEntity inside CheckoutSettled to prevent
mass-assignment tampering (Pilot 5 F-2).




## POST
Phase B Slice 9: the domain parameter arrives from the HTTP request body.

`$preOrderId` is a 40-hex-char id that PreOrderId Semantic
format-validates. The CSRF boundary token is enforced declaratively by
the CsrfProtected attribute.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| preOrderId | string | 仮注文ID |  | Required |  |  |


### Response

_Not available_