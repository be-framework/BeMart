<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/withdraw
EC-CUBE doWithdrawCustomer — マイページから自分の会員アカウントを退会する.

The Be Final converges four side-effects (capture → replace →
cart-clear → mail). This resource adds the AUTHN-via-Session and
CSRF guards on the HTTP boundary; session-clear after the response
is the EC-CUBE EventListener's job (Slice 7.2 contract).

Failure mapping:
  - SemanticVariableException → 400 (sessionPrefix format invalid)
  - UnauthenticatedException  → 401 (no session)
  - missing/invalid csrfToken → 403




## GET
EC-CUBE goMypageWithdraw — show the withdrawal confirmation page.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Authenticated (mirrors Pilot 8 behavior): returns 401
directly from the Resource when no session is present.

Surfaces the current customer's email + name01/name02 so the
confirm page can render "退会されるアカウント: name01 name02
(email)". `csrfToken` body field stays `null` — EventListener
mirrors the Symfony token into the session for the subsequent
POST.

**ALPS**: `goMypageWithdraw`



### Request

_No parameters required_

### Response

[Object: GET /mypage/withdraw response](../schemas/get-mypage-withdraw.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| fields | array|null | 静的表示フィールド - /mypage/withdraw でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/mypage/withdraw \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |
| submitTo | object|null | フォーム送信先リンク - /mypage/withdraw のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/mypage/withdraw \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| doWithdrawCustomer | [<code>page://self/mypage/withdraw</code>](/mypage/withdraw.md) |
## POST
ALPS `doWithdrawCustomer` に対応する POST 操作。

**ALPS**: `doWithdrawCustomer`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| sessionPrefix | string | セッション接頭辞（入力） - 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。 Fake観察文字長 16〜23; 観察値 'session-prefix-1', 'session-checkout-pilot5'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | session-prefix-1 |


### Response

[Object: POST /mypage/withdraw response](../schemas/post-mypage-withdraw.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 会員メッセージ - /mypage/withdraw のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| cleared | boolean|null | 処理状態フラグ - /mypage/withdraw の処理状態を示す処理状態フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| dummyEmail | string | フォーム文脈項目 - /mypage/withdraw のフォーム文脈で使うフォーム文脈項目。入力保持、初期値、再表示に必要な補助値。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |

#### Links

| Relation | URL |
|----------|-----|
| goMypageWithdrawComplete | [<code>page://self/mypage/withdraw-complete</code>](/mypage/withdraw-complete.md) |
| goTop | [<code>page://self/</code>](/.md) |