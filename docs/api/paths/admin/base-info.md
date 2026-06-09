<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/base-info
EC-CUBE doUpdateBaseInfo + goBaseInfo — 基本情報 (Wave 8 + Wave 9).

- GET  → goBaseInfo (safe read, admin AUTHZ, Wave 9ι)
  - POST → doUpdateBaseInfo (idempotent, admin AUTHZ + CSRF, Wave 8ε)

dtb_base_info is a single-row table; POST replaces the row wholesale
(no per-field PATCH semantic in EC-CUBE). Only the shopName is
required — null in other fields means "clear it".

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (shopName / address /
                                               phoneNumber / … format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Idempotency (ALPS `type=idempotent`): replaying the same body is a
no-op-equivalent — the Final reports `changed=false` and the row
is not rewritten.

Mass-assignment safety: only the shop-info columns are accepted.




## GET
Wave 9ι: goBaseInfo — admin views the shop base info form data.

Setting/Shop Tier-2 also renders `shop_master.twig` from this body;
the `form` key carries an {@see \AdminShopMasterForm} pre-filled
with the dtb_base_info row for the HTML editor.

**ALPS**: `goBaseInfo`



### Request

_No parameters required_

### Response

[Object: GET /admin/base-info response](../schemas/get-admin-base-info.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| phoneNumber | string|null | 電話番号 - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 | Optional | {"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13} | 0312345678 |
| shopKana | string|null | ショップ名フリガナ - ショップ名のカタカナ読み Fake観察文字長 12〜12; 観察値 'イーシーキューブショップ'。 | Required | {"minLength":0,"maxLength":32} | イーシーキューブショップ |
| businessHour | string|null | 営業時間 - ショップの営業時間。フリーフォーマット Fake観察文字長 11〜11; 観察値 '10:00-19:00'。 | Required | {"minLength":0,"maxLength":32} | 10:00-19:00 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 | Optional | {"minLength":0,"maxLength":32} | 神宮前1-1-1 |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 | Optional | {"pattern":"^\\d{3}-?\\d{4}$"} | 1500001 |
| pref | int|null | 都道府県 - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 | Optional | {"minimum":0,"maximum":47} | 13 |
| addr01 | string|null | 市区町村 - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 | Required | {"minLength":0,"maxLength":32} | 渋谷区 |
| shopEmail01 | string|null | 送信元/BCC メールアドレス - ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス。注文確認・会員登録・パスワードリセット等で使用 Fake観察文字長 16〜16; 観察値 'shop@example.com'。 | Required | {"format":"email","minLength":3,"maxLength":254} | shop@example.com |
| shopName | string|null | ショップ名 - ショップの表示名。フロント画面のヘッダやメールに表示 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |
| companyName | string|null | 会社名 - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 | Optional | {"minLength":0,"maxLength":32} | Acme Corp. |
| shopMessage | string|null | ショップメッセージ - 「当サイトについて」ページ（Help/about.twig）に表示する店舗からのメッセージ Fake観察文字長 20〜20; 観察値 'ようこそ、EC-CUBE SHOP へ。'。 | Required | {"minLength":0,"maxLength":2000} | ようこそ、EC-CUBE SHOP へ。 |
| shopNameEng | string|null | ショップ名英語 - ショップの英語名。多言語対応やメール署名等で使用 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateBaseInfo | [<code>page://self/admin/base-info</code>](/admin/base-info.md) |
## POST
Wave 8: every shop-info field is admin-form input.

**ALPS**: `doUpdateBaseInfo`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| shopName | string | ショップ名（入力） - ショップの表示名。フロント画面のヘッダやメールに表示 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | EC-CUBE SHOP |
| shopKana | string | ショップ名フリガナ（入力） - ショップ名のカタカナ読み Fake観察文字長 12〜12; 観察値 'イーシーキューブショップ'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | イーシーキューブショップ |
| shopNameEng | string | ショップ名英語（入力） - ショップの英語名。多言語対応やメール署名等で使用 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | EC-CUBE SHOP |
| companyName | string | 会社名（入力） - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Acme Corp. |
| postalCode | string | 郵便番号（入力） - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県（入力） - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村（入力） - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名（入力） - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| phoneNumber | string | 電話番号（入力） - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |
| businessHour | string | 営業時間（入力） - ショップの営業時間。フリーフォーマット Fake観察文字長 11〜11; 観察値 '10:00-19:00'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 10:00-19:00 |
| shopEmail01 | string | 送信元/BCC メールアドレス（入力） - ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス。注文確認・会員登録・パスワードリセット等で使用 Fake観察文字長 16〜16; 観察値 'shop@example.com'。 |  | Optional | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | shop@example.com |
| shopMessage | string | ショップメッセージ（入力） - 「当サイトについて」ページ（Help/about.twig）に表示する店舗からのメッセージ Fake観察文字長 20〜20; 観察値 'ようこそ、EC-CUBE SHOP へ。'。 |  | Optional | {"minLength":0,"maxLength":2000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ようこそ、EC-CUBE SHOP へ。 |


### Response

[Object: POST /admin/base-info response](../schemas/post-admin-base-info.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| phoneNumber | string|null | 電話番号 - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 | Optional | {"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13} | 0312345678 |
| shopKana | string|null | ショップ名フリガナ - ショップ名のカタカナ読み Fake観察文字長 12〜12; 観察値 'イーシーキューブショップ'。 | Required | {"minLength":0,"maxLength":32} | イーシーキューブショップ |
| changed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Required |  | 1 |
| businessHour | string|null | 営業時間 - ショップの営業時間。フリーフォーマット Fake観察文字長 11〜11; 観察値 '10:00-19:00'。 | Required | {"minLength":0,"maxLength":32} | 10:00-19:00 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 | Optional | {"minLength":0,"maxLength":32} | 神宮前1-1-1 |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 | Optional | {"pattern":"^\\d{3}-?\\d{4}$"} | 1500001 |
| pref | int|null | 都道府県 - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 | Optional | {"minimum":0,"maximum":47} | 13 |
| addr01 | string|null | 市区町村 - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 | Required | {"minLength":0,"maxLength":32} | 渋谷区 |
| shopEmail01 | string|null | 送信元/BCC メールアドレス - ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス。注文確認・会員登録・パスワードリセット等で使用 Fake観察文字長 16〜16; 観察値 'shop@example.com'。 | Required | {"format":"email","minLength":3,"maxLength":254} | shop@example.com |
| shopName | string|null | ショップ名 - ショップの表示名。フロント画面のヘッダやメールに表示 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |
| companyName | string|null | 会社名 - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 | Optional | {"minLength":0,"maxLength":32} | Acme Corp. |
| shopMessage | string|null | ショップメッセージ - 「当サイトについて」ページ（Help/about.twig）に表示する店舗からのメッセージ Fake観察文字長 20〜20; 観察値 'ようこそ、EC-CUBE SHOP へ。'。 | Required | {"minLength":0,"maxLength":2000} | ようこそ、EC-CUBE SHOP へ。 |
| shopNameEng | string|null | ショップ名英語 - ショップの英語名。多言語対応やメール署名等で使用 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/admin</code>](/admin.md) |
| goPaymentList | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |