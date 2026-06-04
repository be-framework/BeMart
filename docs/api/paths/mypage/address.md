<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/address
EC-CUBE 配送先住所 — single-resource endpoint (Pilot 16).

- PUT    → doUpdateCustomerAddress  (edit existing row)
  - DELETE → doDeleteCustomerAddress  (remove existing row)

addressId is passed in the request payload (BEAR.Sunday's resource
client merges body and query into a single argument map; either
form reaches `$addressId` here). The collection endpoint
`page://self/mypage/address-list` handles GET / POST.

AUTHN + AUTHZ are enforced in the Be Final — the customerId is
pulled from CustomerSession and compared against the entity's
owner. A logged-in customer cannot mutate another customer's
address book by guessing addressIds.

Failure mapping:
  - SemanticVariableException             → 400 (input format)
  - UnauthenticatedException              → 401 (no session)
  - UnauthorizedAddressAccessException    → 403 (wrong owner)
  - AddressNotFoundException              → 404 (unknown id)
  - CSRF mismatch (PUT / DELETE)          → 403




## GET
EC-CUBE お届け先情報編集 — show the address add/edit form.

Pure form-info endpoint: no Be Framework, no domain logic. Maps
EC-CUBE's `mypage_delivery_new` (no `addressId`) and
`mypage_delivery_edit` (`addressId` given) screens. AUTHN +
ownership AUTHZ are enforced here directly (mirrors Withdraw::onGet
— a Resource-level guard on a no-domain form page):

  - no session                    → 401
  - addressId of an unknown row    → 404
  - addressId owned by another     → 403

Phase 3 — HTML FORM page. The resource builds an {@see \AddressForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']`.
For the edit screen the form is pre-populated from the stored
address; for the new screen it is empty. VALIDATION AUTHORITY
STAYS WITH the Be Framework Becoming chain (onPost). The JSON
contexts ignore `body['form']`.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID |  | Optional |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID |  | Required |  |  |
| name01 | string | 姓 |  | Optional |  |  |
| name02 | string | 名 |  | Optional |  |  |
| kana01 | string | セイ |  | Optional |  |  |
| kana02 | string | メイ |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |
| postalCode | string | 郵便番号 |  | Optional |  |  |
| pref | int | 都道府県 |  | Optional |  |  |
| addr01 | string | 市区町村 |  | Optional |  |  |
| addr02 | string | 番地・建物名 |  | Optional |  |  |
| phoneNumber | string | 電話番号 |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID |  | Required |  |  |


### Response

_Not available_