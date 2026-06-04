---
layout: default
title: "/forgot-password"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /forgot-password
EC-CUBE doRequestPasswordReset — パスワードリセット依頼 (Pilot 14).

Anti-enumeration: the response code (200) and body shape are
identical regardless of whether the supplied email is actually
registered. A real attacker cannot probe for valid emails by
watching for differences in status, body, or timing.

The `issued` flag in the body deliberately reports the same string
for both branches; callers that need to programmatically check
delivery must reach into the test-only FakeMailer (which records
actual dispatches).

Phase 3 — HTML FORM page. `onGet` renders the password-reset-request
form (EC-CUBE `Forgot/index.twig`): the resource builds a
{@see \ForgotForm} (Ray.WebFormModule AbstractForm) and exposes it as
`body['form']`. VALIDATION AUTHORITY STAYS WITH the Be Framework
Becoming chain. The JSON contexts ignore `body['form']`.




## GET
EC-CUBE goRequestPasswordReset — show the password-reset-request
form scaffolding.

Pure form-info endpoint: no Be Framework, no domain logic.
Anonymous-accessible (returns 200 regardless of session state).
`csrfToken` stays `null` — the EventListener mirrors the Symfony
token into the session for the subsequent POST (same as Login).



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Required |  |  |


### Response

_Not available_