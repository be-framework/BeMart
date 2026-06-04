<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer/resend-activation-mail
EC-CUBE doResendActivationMail — 認証メールを再送する (Phase 3
ALPS-audit remediation).

POST /admin/customer/resend-activation-mail

From the admin customer-list screen an ADMIN resends the email-
verification (full-registration) mail to a 仮会員 (provisional
customer) who never followed the original activation link. Derived
from EC-CUBE's `admin_customer_resend` route. The mail carries an
activation URL embedding the customer's `secretKey`; the customer
later promotes to a full member via `doActivateCustomer`. ALPS marks
it `unsafe` — POST is the matching verb, each call sends a fresh mail.

Failure mapping (cross-firewall AUTHZ → existence → state ladder):
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (email format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - CustomerNotFoundException             → 404 (no such email)
  - CustomerAlreadyActivatedException     → 409 (not a 仮会員)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which emails resolve
(same anti-enumeration discipline as goCustomer).




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Required |  |  |


### Response

_Not available_