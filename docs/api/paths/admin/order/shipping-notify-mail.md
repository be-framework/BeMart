<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/shipping-notify-mail
EC-CUBE doSendShippingNotifyMail — 出荷通知メールを送信する (Phase 3
ALPS-audit remediation).

POST /admin/order/shipping-notify-mail

Sends the "your order has shipped" mail for a finalized order,
derived from EC-CUBE's `admin_shipping_notify_mail` route. Distinct
from {@see \SendMail} (the order-received mail). ALPS marks it
`unsafe` — POST is the matching verb, each call sends a fresh mail.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_