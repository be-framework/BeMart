<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-delivery-edit
EC-CUBE お届け先編集 — Customer Tier-2.

Renders + persists the admin customer address-book entry editor
(`admin/Customer/delivery_edit.twig`):

  - GET    → goCustomerDeliveryEdit          (render the edit form)
  - POST   → doCreateCustomerDeliveryAddress  (addressId empty → create)
           / doUpdateCustomerDeliveryAddress  (addressId present → update)
  - DELETE → doDeleteCustomerDeliveryAddress  (drop one address row)

Unlike the storefront Mypage flow, the admin acts on a customer keyed by
the route-param `customerId` (the admin firewall has no CustomerSession),
so the write transitions land on admin-specific Be Inputs/Finals that
carry the target customerId explicitly and guard it with the AdminSession
(403 when absent — checked FIRST). Writes redirect back to the member
edit page (`/admin/customer?customerId=...`), mirroring EC-CUBE's
CustomerDeliveryEditController redirect.

Admin-only — the AUTHZ guard rejects an anonymous admin with 403,
matching the sibling Setting/System Tier-2 renderers ({@see \System},
{@see \Security}, {@see \TwoFactorAuthEdit}).

KNOWN UI LIMITATION (#143): the rendered form has no `addressId` hidden
field and no existing-address list, so `onPost` can only ever take the
addressId-empty (create) branch and `onDelete` cannot be reached at all
from this page today — both are exercised directly in
{@see \MyVendor\BeMart\Tests\Resource\AdminCustomerDeliveryEditResourceTest}
but have no click path. Wiring an edit/delete affordance per existing
address (e.g. from `/admin/customer`'s address list) is a separate,
larger UI slice than the dead-form fix #143 scoped.




## GET
The customer id comes from the admin UI (route param), so it is
user-controlled — same taint discipline as the sibling
{@see Customer} resource.

**ALPS**: `goCustomerDeliveryEdit`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID（入力） - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | customer-001 |
| id | string | ID（入力） - Fake観察文字長 13〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ad000000000000000000000000000001 |


### Response

[Object: GET /admin/customer-delivery-edit response](../schemas/get-admin-customer-delivery-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| csrfToken | string | CSRFトークン - フォーム送信元を検証するトークン。Fake環境では deterministic な値を使う。 | Required | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |
| doCreateCustomerDeliveryAddress | [<code>page://self/admin/customer-delivery-edit</code>](/admin/customer-delivery-edit.md) |
| doUpdateCustomerDeliveryAddress | [<code>page://self/admin/customer-delivery-edit</code>](/admin/customer-delivery-edit.md) |
| doDeleteCustomerDeliveryAddress | [<code>page://self/admin/customer-delivery-edit</code>](/admin/customer-delivery-edit.md) |
## POST
Persist a customer delivery address. Empty `addressId` creates a new
row (doCreateCustomerDeliveryAddress); a present `addressId` updates
the existing row in place (doUpdateCustomerDeliveryAddress). The Be
Final guards the AdminSession (403 first) then verifies the target
customer / address ownership before writing.

**ALPS**: `doUpdateCustomerDeliveryAddress`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID - 対象会員の不透明ID（route param）。 |  | Required | {"minLength":1,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]+$","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0123456789abcdef0123456789abcdef |
| name01 | string | 姓 - 配送先の姓。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 山田 |
| name02 | string | 名 - 配送先の名。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 太郎 |
| postalCode | string | 郵便番号 - 日本の郵便番号。 |  | Required | {"minLength":0,"maxLength":8,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県ID - 1=北海道〜47=沖縄県。未選択初期値の0も許容。 |  | Required | {"minimum":0,"maximum":47,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村 - 市区町村名。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名 - 番地・建物名。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| phoneNumber | string | 電話番号 - 配送先の電話番号。 |  | Required | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |
| addressId | string | 配送先ID - 更新対象の配送先ID。空文字なら新規作成。 |  | Optional | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | addr00000000000000000000000000a1 |
| kana01 | string | セイ - 姓のカナ。null 許容。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマダ |
| kana02 | string | メイ - 名のカナ。null 許容。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | タロウ |
| companyName | string | 会社名 - 会社名。null 許容。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Acme Corp. |


### Response

[Object: POST /admin/customer-delivery-edit response](../schemas/post-admin-customer-delivery-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| addressId | string|null | 配送先ID - dtb_customer_address.id の不透明な文字列ハンドル。サーバ採番。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$"} | addr00000000000000000000000000a1 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。管理画面では route param 由来。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$"} | 0123456789abcdef0123456789abcdef |
| name01 | string|null | 姓 - 配送先の姓。 | Required | {"minLength":0,"maxLength":255} | 山田 |
| name02 | string|null | 名 - 配送先の名。 | Required | {"minLength":0,"maxLength":255} | 太郎 |
| kana01 | string|null | セイ - 姓のカタカナ読み。null 許容。 | Optional | {"minLength":0,"maxLength":255} | ヤマダ |
| kana02 | string|null | メイ - 名のカタカナ読み。null 許容。 | Optional | {"minLength":0,"maxLength":255} | タロウ |
| companyName | string|null | 会社名 - 配送先の会社名。null 許容。 | Optional | {"minLength":0,"maxLength":255} | Acme Corp. |
| phoneNumber | string|null | 電話番号 - 配送先の電話番号。 | Required | {"minLength":0,"maxLength":13} | 0312345678 |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。 | Required | {"minLength":0,"maxLength":8} | 1500001 |
| pref | int|null | 都道府県ID - 1=北海道〜47=沖縄県。 | Required | {"minimum":0,"maximum":47} | 13 |
| addr01 | string|null | 市区町村 - 市区町村名。 | Required | {"minLength":0,"maxLength":255} | 渋谷区 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等。 | Required | {"minLength":0,"maxLength":255} | 神宮前1-1-1 |

#### Links

| Relation | URL |
|----------|-----|
| goCustomer | [<code>page://self/admin/customer</code>](/admin/customer.md) |
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |
## DELETE
Remove one customer delivery address. The Be Final guards the
AdminSession (403 first) then verifies the address is owned by the
route-param customer before deleting.

**ALPS**: `doDeleteCustomerDeliveryAddress`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID - 対象会員の不透明ID（route param）。 |  | Required | {"minLength":1,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]+$","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0123456789abcdef0123456789abcdef |
| addressId | string | 配送先ID - 削除対象の配送先ID。 |  | Required | {"minLength":1,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]+$","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | addr00000000000000000000000000a1 |


### Response

[Object: DELETE /admin/customer-delivery-edit response](../schemas/delete-admin-customer-delivery-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| addressId | string|null | 配送先ID - dtb_customer_address.id の不透明な文字列ハンドル。サーバ採番。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$"} | addr00000000000000000000000000a1 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。管理画面では route param 由来。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$"} | 0123456789abcdef0123456789abcdef |

#### Links

| Relation | URL |
|----------|-----|
| goCustomer | [<code>page://self/admin/customer</code>](/admin/customer.md) |
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |