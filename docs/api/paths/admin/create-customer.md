<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

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

**ALPS**: `doCreateCustomer` - 会員を作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Required | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |
| password | string | パスワード（入力） - 書き込み専用（ハッシュ化して保存） Fake観察文字長 50〜63; 観察値 '$2y$12$stXeC3GBw5uMLkgK/6Vb0.R7XLnwERRqWM/Hl7rtAhp4IcHoK8eWi', '$2y$10$deputyplaceholder.hash.never.verified.0123456789abcdef', '$2y$10$zyxwvutsrqponmlkjihgfedcbaZYXWVUTSRQPONMLKJIHGFEDCBA9876', '$2y$12$lbpzHVyv.ytzJju.4xk9Au5tPePdQe1WFH6sLWeWwcvIKbn0vMnE.', '$2y$10$shopownerplaceholder.hash.never.verified.0123456789ab', '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123', '$2y$10$0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRS', '$2y$12$placeholder.hash.never.verified.never.0123456789abcde'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | $2y$12$stXeC3GBw5uMLkgK/6Vb0.R7XLnwERRqWM/Hl7rtAhp4IcHoK8eWi |
| name01 | string | 姓（入力） - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| name02 | string | 名（入力） - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| kana01 | string | セイ（入力） - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマダ |
| kana02 | string | メイ（入力） - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| companyName | string | 会社名（入力） - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Acme Corp. |
| phoneNumber | string | 電話番号（入力） - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |
| postalCode | string | 郵便番号（入力） - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県（入力） - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村（入力） - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名（入力） - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| birth | string | 生年月日（入力） - 会員の生年月日 Fake観察文字長 10〜10; 観察値 '1990-04-01', '1985-12-15'; null 18/28。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1990-04-01 |
| sex | int | 性別（入力） - 1=男性, 2=女性, 3=その他, 4=回答しない Fake観察数値 2〜2; 観察値 '2'; null 3/5。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |
| job | int | 職業（入力） - 1=公務員〜18=その他の18区分 Fake観察数値 7〜18; 観察値 '7', '18'; null 3/5。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 7 |


### Response

[Object: POST /admin/create-customer response](../schemas/post-admin-create-customer.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| customerStatus | int | 会員ステータス - 1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される Fake観察数値 1〜2; 観察値 '2', '1'。 | Required | {"enum":[1,2,3]} | 2 |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| initialPoint | int|null | 初期ポイント - Fake観察数値 100〜100; 観察値 '100'。 | Required | {"minimum":0,"maximum":2147483647} | 100 |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |

#### Links

| Relation | URL |
|----------|-----|
| goCustomer | [<code>page://self/admin/customer</code>](/admin/customer.md) |