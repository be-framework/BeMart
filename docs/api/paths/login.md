---
layout: default
title: "/login"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /login
EC-CUBE doLogin — 会員ログイン (Pilot 6).

Resource is the HTTP entry point: builds LoginInput, hands it to
Becoming, and on success returns the authenticated customerId. The
Be layer pattern is Direct (Input → Final) — see LoginInput.

Failure mapping:
  - SemanticVariableException → 400 (email/password format invalid)
  - LoginFailedException      → 401 (no such email OR wrong password
                                      — combined, no user enumeration)

In the html context, public/index.php starts a PHP session before
dispatch and this resource mirrors `customerId` into the flat session
key read by HtmlSessionAdapter. The write is guarded by
an html APP_CONTEXT and PHP_SESSION_ACTIVE so app/test/prod contexts
keep their existing session behaviour and are not polluted by direct
`$_SESSION` writes.

Phase 3 — HTML FORM page. The resource builds a {@see \LoginForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
the HTML port can render real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only — VALIDATION AUTHORITY
STAYS WITH the Be Framework Becoming chain. On a domain rejection the
resource bridges the verdict onto the form (repopulated email + inline
error) so the Login page re-renders with EC-CUBE's exact form UX. The
JSON contexts (`app`, `prod`, `test`) ignore `body['form']`; the 1445
JSON-context tests assert key-wise on `body` and are unaffected.

FormFactory is self-sufficient (no Ray.Di bindings needed), so the
resource builds the form in every context cheaply; only the `html`
context's TwigRenderer actually renders it.




## GET
EC-CUBE goLogin — show the login form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). The `csrfToken` body field carries the trusted reference
{@see \CsrfToken::$token} issues, which the HTML port
renders into the form's hidden `_csrf_token` input so the
subsequent POST passes CSRF validation.



### Request

_No parameters required_

### Response

_Not available_
## POST
Phase B Slice 9: every form field is user-controlled input.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Required |  |  |
| password | string | パスワード |  | Required |  |  |


### Response

_Not available_
{% endraw %}
