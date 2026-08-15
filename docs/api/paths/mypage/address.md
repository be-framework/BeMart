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

**ALPS**: `doUpdateCustomerAddress` - 配送先を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID（入力） - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | addr00000000000000000000000000a1 |


### Response

[Object: GET /mypage/address response](../schemas/get-mypage-address.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| submitTo | object|null | フォーム送信先リンク - /mypage/address のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/mypage/address \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| addressId | string|null | 配送先住所ID - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | addr00000000000000000000000000a1 |

#### Links

| Relation | URL |
|----------|-----|
| doCreateCustomerAddress | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |
| doUpdateCustomerAddress | [<code>page://self/mypage/address</code>](/mypage/address.md) |
| goCustomerAddressList | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |
## PUT
ALPS `doUpdateCustomerAddress` に対応する PUT 操作。

**ALPS**: `doUpdateCustomerAddress` - 配送先を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID（入力） - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | addr00000000000000000000000000a1 |
| name01 | string | 姓（入力） - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 |  | Optional | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| name02 | string | 名（入力） - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 |  | Optional | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| kana01 | string | セイ（入力） - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマダ |
| kana02 | string | メイ（入力） - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| companyName | string | 会社名（入力） - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Acme Corp. |
| postalCode | string | 郵便番号（入力） - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県（入力） - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村（入力） - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名（入力） - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| phoneNumber | string | 電話番号（入力） - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |


### Response

[Object: PUT /mypage/address response](../schemas/put-mypage-address.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| kana02 | string|null | メイ - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | アリス |
| phoneNumber | string|null | 電話番号 - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 | Optional | {"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13} | 0312345678 |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| kana01 | string|null | セイ - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | ヤマダ |
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 | Optional | {"minLength":0,"maxLength":32} | 神宮前1-1-1 |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 | Optional | {"pattern":"^\\d{3}-?\\d{4}$"} | 1500001 |
| pref | int|null | 都道府県 - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 | Optional | {"minimum":0,"maximum":47} | 13 |
| addr01 | string|null | 市区町村 - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 | Required | {"minLength":0,"maxLength":32} | 渋谷区 |
| companyName | string|null | 会社名 - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 | Optional | {"minLength":0,"maxLength":32} | Acme Corp. |
| addressId | string|null | 配送先住所ID - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | addr00000000000000000000000000a1 |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerAddressList | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |
## DELETE
ALPS `doUpdateCustomerAddress` に対応する DELETE 操作。

**ALPS**: `doUpdateCustomerAddress` - 配送先を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| addressId | string | 配送先住所ID（入力） - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | addr00000000000000000000000000a1 |


### Response

[Object: DELETE /mypage/address response](../schemas/delete-mypage-address.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| addressId | string|null | 配送先住所ID - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | addr00000000000000000000000000a1 |

#### Links

| Relation | URL |
|----------|-----|
| goFavoriteList | [<code>page://self/mypage/favorite-list</code>](/mypage/favorite-list.md) |
| goCustomerAddressList | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |