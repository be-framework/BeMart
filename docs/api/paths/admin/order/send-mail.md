<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/send-mail
EC-CUBE doSendOrderMail — 受注メールを送信する (Wave 9η).

POST /admin/order/send-mail

Reuses {@see \MyVendor\BeMart\Be\Reason\Service\MailerInterface::sendOrderConfirmation}
(Pilot 5) — the same call that fires after a customer-driven
checkout. The custom subject / body overrides ALPS surfaces on
`doSendOrderMail.descriptor` are not wired in Wave 9η (the Mailer
interface only takes the order entity); Phase 2 will extend the
Mailer contract.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## GET
EC-CUBE 受注メール送信 — Order Tier-2.

Thin GET renderer for `admin/Order/mail.twig` — the order-mail
composition screen. The POST below re-sends the confirmation
mail; this GET serves the composition form keyed by the order.
AUTHZ is a direct admin-session check (Pattern B — no Be
transition is invoked on the GET path). The composition fields
render blank so the page is faithful with empty JSON-backed fake storage.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_