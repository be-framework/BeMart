---
layout: default
title: "/reset"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /reset
EC-CUBE doResetPassword — リセットキーを検証して新しいパスワードを
保存する (Pilot 15, consumer pair to Pilot 14 doRequestPasswordReset).

Failure mapping (both -> 400, same code on purpose):
  - SemanticVariableException  → 400 (resetKey or password malformed)
  - ResetKeyInvalidException   → 400 (wrong key / expired / already used)

Both failures collapse to the same HTTP status (400 rather than
404) so an attacker cannot distinguish "format-invalid" from
"value-invalid" by status alone — same anti-enumeration design as
the merged ResetKeyInvalid exception itself.

Single-use is enforced inside the Be Final (token consumed via
`PasswordResetTokenStorageInterface::delete()` immediately on
success); this resource only translates the failure modes.




## GET
EC-CUBE goResetPassword — show the new-password form scaffolding
(EC-CUBE `Forgot/reset.twig`).

Pure form-info endpoint: no Be Framework, no domain logic.
Anonymous-accessible (the reset-key check is the POST's job). The
`resetKey` arrives as a query param on the emailed reset link and
is carried into a hidden form field for the subsequent POST.
`csrfToken` stays `null` — the EventListener mirrors the Symfony
token into the session for the POST (same as Login).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| resetKey | string | リセットキー |  | Optional |  |  |


### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| resetKey | string | リセットキー |  | Required |  |  |
| password | string | パスワード |  | Required |  |  |


### Response

_Not available_