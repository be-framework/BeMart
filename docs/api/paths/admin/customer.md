<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer
EC-CUBE goCustomer — 会員詳細を見る（管理画面）.

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when the admin session is empty,
which this resource maps to 403. Aggregates full profile + complete
order history + favorites list into a flat admin detail projection.

Failure mapping (cross-firewall AUTHZ → existence ladder):
  - SemanticVariableException            → 400 (email format invalid)
  - UnauthorizedAdminAccessException     → 403 (no admin session)
  - CustomerNotFoundException            → 404 (no such email)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which emails resolve
(same anti-enumeration discipline as the customer-side Pilot 8 /
Pilot 12 AUTHN-first ladders).

Unlike the customer's own goMypage, this surface is the FULL profile
(birth, sex, job, full address, point balance, registrationDate
analogue), FULL order history (capped at 50 with derived totalSpent),
and FULL favorites list (not just the count). The admin back-office
needs the richer projection — drill-downs into individual orders /
favorites are deferred to dedicated admin endpoints.

Mirrors {@see \Login} / {@see \Logout} for the admin firewall —
distinct namespace under `Page\Admin\` (URI prefix
`page://self/admin/...`). Coexists with a potential future
`Page\Admin\Customer\` sibling directory: PHP allows a file and a
sibling directory of the same name to share a namespace prefix
(same as `Resource\Page\Mypage.php` + `Resource\Page\Mypage\`).




## GET
Wave 5: the email comes from the admin UI (typed input or query
string), so it is user-controlled — same taint discipline as the
customer-side LoginResource.

**ALPS**: `goCustomer` - 会員詳細を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Optional | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |
| customerId | string | 会員ID（入力） - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | customer-001 |
| id | string | ID（入力） - Fake観察文字長 13〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ad000000000000000000000000000001 |


### Response

[Object: GET /admin/customer response](../schemas/get-admin-customer.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| sex | int|null | 性別 - 1=男性, 2=女性, 3=その他, 4=回答しない Fake観察数値 2〜2; 観察値 '2'; null 3/5。 | Optional | {"minimum":0} | 2 |
| kana02 | string|null | メイ - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | アリス |
| phoneNumber | string|null | 電話番号 - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 | Optional | {"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13} | 0312345678 |
| initialPoint | int|null | 初期ポイント - Fake観察数値 100〜100; 観察値 '100'。 | Required | {"minimum":0,"maximum":2147483647} | 100 |
| totalSpent | int | 累計購入金額 - /admin/customer のレスポンスで返す累計購入金額。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |
| kana01 | string|null | セイ - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | ヤマダ |
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| favorites | array|null | お気に入り商品一覧 - /admin/customer のレスポンスで扱うお気に入り商品一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u304a\u6c17\u306b\u5165\u308a\u5546\u54c1","description":"/admin/customer \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u304a\u6c17\u306b\u5165\u308a\u5546\u54c1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `favorites` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":["string","null"],"minLength":0,"maxLength":64,"example":"sample-001"},"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"price02":{"title":"\u8ca9\u58f2\u4fa1\u683c","description":"\u5b9f\u969b\u306e\u8ca9\u58f2\u4fa1\u683c\uff08\u7a0e\u629c\uff09\u3002\u7a0e\u8a08\u7b97\u30fb\u5c0f\u8a08\u8a08\u7b97\u306e\u30d9\u30fc\u30b9 Fake\u89b3\u5bdf\u6570\u5024 800\u301c28000\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":3500},"mainImage":{"title":"\u30e1\u30a4\u30f3\u753b\u50cfURI","description":"/admin/customer \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u30e1\u30a4\u30f3\u753b\u50cfURI\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002","type":["string","null"],"format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":1200},"fileName":{"type":["string","null"],"minLength":1,"maxLength":255,"title":"\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u5546\u54c1\u753b\u50cf\u306e\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c15; \u89b3\u5bdf\u5024 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'\u3002","example":"Mail/order.twig"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 | Optional | {"pattern":"^\\d{3}-?\\d{4}$"} | 1500001 |
| addr01 | string|null | 市区町村 - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 | Required | {"minLength":0,"maxLength":32} | 渋谷区 |
| birth | string|null | 生年月日 - 会員の生年月日 Fake観察文字長 10〜10; 観察値 '1990-04-01', '1985-12-15'; null 18/28。 | Optional | {"format":"date"} | 1990-04-01 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 | Optional | {"minLength":0,"maxLength":32} | 神宮前1-1-1 |
| customerStatus | int | 会員ステータス - 1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される Fake観察数値 1〜2; 観察値 '2', '1'。 | Required | {"enum":[1,2,3]} | 2 |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| pref | int|null | 都道府県 - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 | Optional | {"minimum":0,"maximum":47} | 13 |
| favoriteCount | int|null | お気に入り件数 - /admin/customer のレスポンスで返すお気に入り件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| companyName | string|null | 会社名 - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 | Optional | {"minLength":0,"maxLength":32} | Acme Corp. |
| job | int|null | 職業 - 1=公務員〜18=その他の18区分 Fake観察数値 7〜18; 観察値 '7', '18'; null 3/5。 | Optional | {"minimum":0} | 7 |
| orders | array|null | 注文一覧 - /admin/customer のレスポンスで扱う注文一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u6ce8\u6587\u6982\u8981","description":"/admin/customer \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u6982\u8981\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orders` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"orderNo":{"type":["string","null"],"minLength":0,"maxLength":64,"title":"\u6ce8\u6587\u756a\u53f7","description":"\u9867\u5ba2\u5411\u3051\u306e\u6ce8\u6587\u756a\u53f7\u3002\u30d5\u30a9\u30fc\u30de\u30c3\u30c8\u306f\u30ab\u30b9\u30bf\u30de\u30a4\u30ba\u53ef\u80fd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'past0000000000000000000000000001'\u3002","example":"past0000000000000000000000000001"},"orderStatus":{"title":"\u53d7\u6ce8\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u65b0\u898f\u53d7\u4ed8, 3=\u6ce8\u6587\u53d6\u6d88, 4=\u5bfe\u5fdc\u4e2d, 5=\u767a\u9001\u6e08\u307f, 6=\u5165\u91d1\u6e08\u307f, 7=\u6c7a\u6e08\u51e6\u7406\u4e2d, 8=\u8cfc\u5165\u51e6\u7406\u4e2d, 9=\u8fd4\u54c1\u3002Symfony Workflow\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u3067\u9077\u79fb\u3092\u5236\u5fa1\u3002\u8a31\u53ef\u3055\u308c\u308b\u9077\u79fb: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)\u30027\u30688\u306fPurchaseFlow\u5185\u3067\u76f4\u63a5\u30bb\u30c3\u30c8\u3055\u308c\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u9077\u79fb\u306e\u5bfe\u8c61\u5916 Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 '1'\u3002","type":["integer","null"],"minimum":1,"maximum":9,"example":1},"orderDate":{"title":"\u6ce8\u6587\u65e5","description":"\u6ce8\u6587\u78ba\u5b9a\u65e5\u6642 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":["string","null"],"example":"2026-04-01 10:00:00","pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"paymentTotal":{"type":["integer","null"],"title":"\u652f\u6255\u5408\u8a08","description":"\u5b9f\u969b\u306e\u652f\u6255\u91d1\u984d\u3002\u521d\u671f\u5024\u306ftotal\u3068\u540c\u5024\u3067\u3001PointProcessor\u304c\u30dd\u30a4\u30f3\u30c8\u5024\u5f15\u304d\u306eOrderItem\uff08type=POINT_DISCOUNT\u3001\u4e0d\u8ab2\u7a0e\uff09\u3092\u8ffd\u52a0\u5f8c\u306bPurchaseFlow.calculateTotal()\u3067\u518d\u8a08\u7b97\u3055\u308c\u308b\u3002\u8a08\u7b97\u5f0f: total - (\u5229\u7528\u30dd\u30a4\u30f3\u30c8 x pointConversionRate) Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"total":{"type":["integer","null"],"title":"\u53d7\u6ce8\u5408\u8a08","description":"\u53d7\u6ce8\u5408\u8a08\u91d1\u984d\u3002\u8a08\u7b97\u5f0f: subtotal(\u5546\u54c1\u7a0e\u8fbc\u5408\u8a08) + deliveryFeeTotal(\u9001\u6599) + charge(\u624b\u6570\u6599) - discount(\u5024\u5f15\u304d)\u3002\u30ab\u30fc\u30c8\u306etotalPrice\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"itemCount":{"type":["integer","null"],"minimum":0,"maximum":10000,"title":"\u660e\u7d30\u4ef6\u6570","description":"/admin/customer \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8fd4\u3059\u660e\u7d30\u4ef6\u6570\u3002\u4e00\u89a7\u30fb\u96c6\u8a08\u30fb\u51e6\u7406\u7d50\u679c\u306e\u898f\u6a21\u3092\u8868\u3059\u975e\u8ca0\u6574\u6570\u3002","example":1}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| orderCount | int|null | 注文件数 - /admin/customer のレスポンスで返す注文件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |