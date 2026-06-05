---
layout: default
title: "/admin/create-customer"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/create-customer
EC-CUBE doCreateCustomer — 会員を作成する (管理画面).

Admin-side counterpart of Pilot 4's {@see \MyVendor\BeMart\Resource\Page\Entry}.
Resource is the HTTP entry point: builds AdminCreateCustomerInput,
hands it to Becoming, and projects the resulting AdminCustomerCreated
into the response body. The 4 required form fields (email /
password / name01 / name02) match `doCreateCustomer.descriptor[]` in
alps.json; the 11 optional fields mirror the front-end self-service
form so the admin screen can reuse the same field set.

ALPS doc: 管理画面から会員を新規作成する。仮会員フラグなしで即時本会員として登録 —
the Being fixes customerStatus to 2 (Active) with no provisional path.

Failure mapping:
  - SemanticVariableException          → 400 (email/password/name format)
  - UnauthorizedAdminAccessException   → 403 (no admin session)
  - EmailAlreadyRegisteredException    → 409 (email already taken)

On success the response is 201 with a `Location` header pointing at
the admin Customer detail URL keyed by email — matching the
`goCustomer` ALPS transition surface (`#email` is its descriptor).




## POST
Wave 5: every form field is user-controlled input — same taint
discipline as the front-end entry. The admin AUTHZ check lives
inside the first Being (AdminCustomerCreating), so this method
can stay free of session lookups; we just map the exception.



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