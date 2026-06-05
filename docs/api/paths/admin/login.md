---
layout: default
title: "/admin/login"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/login
EC-CUBE doAdminLogin — 管理者ログイン (Wave 4).

Resource is the HTTP entry point: builds AdminLoginInput, hands it
to Becoming, and on success returns the authenticated adminId. The
Be layer pattern is Direct (Input → Final) — see AdminLoginInput.

Failure mapping:
  - SemanticVariableException     → 400 (loginId/password format invalid)
  - AdminLoginFailedException     → 401 (no such loginId OR wrong
                                           password — combined, no
                                           user enumeration)

Mirrors Pilot 6 customer {@see \MyVendor\BeMart\Resource\Page\Login}
but for the admin firewall — distinct namespace under `Page\Admin\`
(different URI prefix `page://self/admin/login`), and the response
body carries admin shape (adminId / loginId / name / authority)
rather than customer shape.

In the html context, public/index.php starts a PHP session before
dispatch and this resource mirrors `adminId` into the flat session
key read by HtmlAdminSessionAdapter. The write is guarded by
an html APP_CONTEXT and PHP_SESSION_ACTIVE so app/test/prod contexts
keep their existing session behaviour and are not polluted by direct
`$_SESSION` writes.

Source-of-truth gap: alps.json does not currently carry a
`doAdminLogin` transition id (only customer `doLogin`). Using the
conventional name to parallel the customer side; ALPS profile is
expected to gain a matching transition in a later sweep.




## GET
Show the admin login form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state) — the admin firewall guard is on the POST. The `csrfToken`
body field carries the trusted reference {@see \CsrfToken::$token}
issues, which the HTML port renders into the form's hidden
`_csrf_token` input so the subsequent POST passes CSRF validation.



### Request

_No parameters required_

### Response

_Not available_
## POST
Wave 4 / Phase B Slice 9: every form field is user-controlled
input — same taint discipline as the customer login.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID |  | Required |  |  |
| password | string | パスワード |  | Required |  |  |


### Response

_Not available_