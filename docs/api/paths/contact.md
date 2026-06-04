---
layout: default
title: "/contact"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /contact
EC-CUBE doSubmitContact — お問い合わせ送信 (Pilot 15).

Anonymous-accessible: no AUTHN, no AUTHZ. CSRF guard remains
(Slice 8 uniformity).

Phase 3 — HTML FORM page. The resource builds a {@see \ContactForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
the HTML port renders real `<input>` / `<textarea>` via
`{{ form.input(...) }}`. VALIDATION AUTHORITY STAYS WITH the Be
Framework Becoming chain. The JSON contexts ignore `body['form']`.




## GET
EC-CUBE goContactForm — show the contact form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). `csrfToken` carries the trusted reference
{@see \CsrfToken::$token} issues — the HTML port
renders it into the form's hidden `_token` input so the
subsequent POST passes CSRF validation.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| contactName01 | string | お問い合わせ姓 |  | Required |  |  |
| contactName02 | string | お問い合わせ名 |  | Required |  |  |
| contactEmail | string | お問い合わせメール |  | Required |  |  |
| contactContents | string | お問い合わせ内容 |  | Required |  |  |


### Response

_Not available_
{% endraw %}
