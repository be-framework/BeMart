<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/address-list
EC-CUBE 配送先住所一覧 — collection endpoint (Pilot 16).

Two responsibilities at one URI per BEAR.Sunday REST convention:

  - GET  → goCustomerAddressList       (list the book — safe read)
  - POST → doCreateCustomerAddress     (add a new row)

Single-resource operations (PUT / DELETE) live at
`page://self/mypage/address` (see Address resource).

Failure mapping:
  - SemanticVariableException → 400 (parameter format invalid)
  - UnauthenticatedException  → 401 (no / stale session)

GET is safe and skips CSRF; POST is unsafe and validates CSRF.
customerId is NEVER taken from the request body — the Be Final
pulls it from CustomerSession (Pilot 5 F-2 / Pilot 8 lesson).




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name01 | string | 姓 |  | Required |  |  |
| name02 | string | 名 |  | Required |  |  |
| postalCode | string | 郵便番号 |  | Required |  |  |
| pref | int | 都道府県 |  | Required |  |  |
| addr01 | string | 市区町村 |  | Required |  |  |
| addr02 | string | 番地・建物名 |  | Required |  |  |
| phoneNumber | string | 電話番号 |  | Required |  |  |
| kana01 | string | セイ |  | Optional |  |  |
| kana02 | string | メイ |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |


### Response

_Not available_