<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /entry
EC-CUBE doRegisterCustomer —会員登録 (Entry/Register).

Resource is the HTTP entry point: it builds RegisterCustomerInput, hands
it to Becoming, and projects the resulting CustomerRegistered into the
response body. The 4 required EC-CUBE form fields (email / password /
name01 / name02) are positional; the 11 optional fields are passed
through unchanged with `null` defaults — see RegisterCustomerInput.

Pilot 4 implements the email-verification-OFF flow only
(customerStatus = 2 = Active). The OFF path lands on the
`CustomerRegistrationComplete` state, whose ALPS surface declares the
single transition `goTop`. The verification-ON branch (provisional →
email confirm → activate) is deferred to a future Branching pilot.

Phase 3 — HTML FORM page. The resource builds an {@see \EntryForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
the HTML port renders real `<input>`s via `{{ form.input(...) }}`. The
form is a field-definition + renderer only — VALIDATION AUTHORITY STAYS
WITH the Be Framework Becoming chain. On a domain rejection the
resource bridges the verdict onto the form (repopulated values + inline
error). The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.




## GET
EC-CUBE goCustomerRegistration — show the customer registration
form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). Fields mirror RegisterCustomerInput: 4 required + 11
optional. In the dev/html fake-CSRF environment we expose the fake
token into the hidden `_token` input so a real browser form submit
can exercise the POST path instead of failing at the boundary.



### Request

_No parameters required_

### Response

_Not available_
## POST
Phase B Slice 9: every form field is user-controlled input. Declared
as taint sources so Psalm can trace them. Semantic value objects
format-validate but do not universally escape — sinks downstream
still need their own defense (bound params, HTML escape on render).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス |  | Required |  |  |
| password | string | パスワード |  | Required |  |  |
| name01 | string | 姓 |  | Required |  |  |
| name02 | string | 名 |  | Required |  |  |
| kana01 | string | セイ |  | Optional |  |  |
| kana02 | string | メイ |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |
| phoneNumber | string | 電話番号 |  | Optional |  |  |
| postalCode | string | 郵便番号 |  | Optional |  |  |
| pref | int | 都道府県 |  | Optional |  |  |
| addr01 | string | 市区町村 |  | Optional |  |  |
| addr02 | string | 番地・建物名 |  | Optional |  |  |
| birth | string | 生年月日 |  | Optional |  |  |
| sex | int | 性別 |  | Optional |  |  |
| job | int | 職業 |  | Optional |  |  |


### Response

_Not available_