---
layout: default
title: "/mypage/change"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/change
EC-CUBE doUpdateCustomer — マイページから会員情報を更新 (Pilot 8).

AUTHZ via the Be layer: the customerId for the update target is
the CustomerSession's value — never the request body — so an
authenticated customer cannot edit another customer's record by
tampering with form fields (Pilot 5 F-2 lesson).

Failure mapping:
  - SemanticVariableException        → 400 (field format invalid)
  - UnauthenticatedException         → 401 (no session)
  - EmailAlreadyRegisteredException  → 409 (email change collides)




## GET
goMypageChange — show the change-customer-info form pre-populated
with the logged-in customer's current values.

Safe read. No CSRF (read-only). AUTHN in the Be layer maps null
session → 401.

Phase 3 — HTML FORM page. The resource builds a {@see \ChangeForm}
(Ray.WebFormModule AbstractForm), pre-populates it with the
fetched profile, and exposes it as `body['form']` so the HTML port
renders real `<input>`s via `{{ form.input(...) }}`. VALIDATION
AUTHORITY STAYS WITH the Be Framework Becoming chain (onPost). The
JSON contexts ignore `body['form']`; the flat profile keys stay on
`body` for the JSON-context tests.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Required |  |  |
| name01 | string | 姓 |  | Optional |  |  |
| name02 | string | 名 |  | Optional |  |  |
| kana01 | string | セイ |  | Optional |  |  |
| kana02 | string | メイ |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |
| phoneNumber | string | 電話番号 |  | Optional |  |  |
| postalCode | string | 郵便番号 |  | Optional |  |  |
| pref | int | 都道府県 |  | Optional |  |  |
| addr01 | string | 市区町村 |  | Optional |  |  |
| addr02 | string | 番地・建物名 |  | Optional |  |  |


### Response

_Not available_
{% endraw %}
