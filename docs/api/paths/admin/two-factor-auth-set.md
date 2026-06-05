---
layout: default
title: "/admin/two-factor-auth-set"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/two-factor-auth-set
EC-CUBE admin 2段階認証 デバイス登録 — top-level wave, Phase 3.

Thin renderer for the admin 2FA device-setup screen
(`admin/two_factor_auth_set.twig`, extends `login_frame.twig`). This
is a LOGIN-CONTEXT page reached after correct credentials when the
member has no 2FA device yet, so — like the admin login page — it is
anonymous-accessible (no admin-firewall guard).

EC-CUBE's controller generates a TOTP secret, renders it as a QR code
(the JS in the template builds the `otpauth://` URI) and verifies the
first token. There is no Be Framework 2FA transition (no such id in
`alps.json`, and the be/ domain layer is frozen for this wave), so
this resource is a THIN RENDERER: `onGet` exposes an
{@see \AdminTwoFactorAuthForm} as `body['form']` for the HTML page.

Hard ActionRedirect completion: `onPut` drives the Be
`doSetTwoFactorAuth` transition ({@see \SetTwoFactorAuthInput} →
{@see \TwoFactorAuthConfigured}) — register the secret, then confirm by
verifying the first device code.

MISSING-BODY-FIELD residual (kept for EC-CUBE render fidelity): EC-CUBE
generates the TOTP secret server-side and embeds it in the QR `authKey`.
BeMart's render-diff baseline tolerates `authKey` empty (the QR `secret=`
stays blank), so `onGet` keeps the empty placeholder; the real secret is
round-tripped from the form into `onPut`. Seeding `onGet` with a
generated secret would diverge the QR URI from EC-CUBE's reference.




## GET
Renders the admin 2FA device-setup form.

Anonymous-accessible (login-context): returns 200 regardless of
session state.



### Request

_No parameters required_

### Response

_Not available_
## PUT
Registers the TOTP device after confirming the first code
(doSetTwoFactorAuth). ALPS marks this `idempotent` → PUT.

SECURITY RESIDUAL (tracked — migration-status §4 "Outstanding work"
item 8, the Hard-ActionRedirect / 認証 cutover residual): this page is
reached PRE-AUTH (anonymous, login-context), so `$loginId` and the
candidate `$authKey` secret are taken from the request body rather than
a server-side pre-auth challenge. `enable()` overwrites the secret for
`$loginId` with no ownership check
({@see \MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface::enable}),
so a caller who passes another admin's `$loginId` could replace that
admin's 2FA device. The production cutover binds a server-generated
secret + the pending login identity into a pre-auth session/challenge
state at credential-verification time and consumes it here; until then
the route relies on CSRF + the documented contract. Do NOT widen this
surface (e.g. expose it post-auth for arbitrary `$loginId`) before the
challenge state lands.

Failure mapping:
  - Invalid CSRF                  → 403 (interceptor)
  - SemanticVariableException     → 400 (malformed code)
  - TwoFactorAuthFailedException  → 400 (first code mismatch)



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |
| authKey | string |  |  | Required |  |  |
| deviceToken | string |  |  | Required |  |  |


### Response

_Not available_