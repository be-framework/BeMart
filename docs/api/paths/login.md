<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /login
EC-CUBE doLogin — 会員ログイン (Pilot 6).

Resource is the HTTP entry point: builds LoginInput, hands it to
Becoming, and on success returns the authenticated customerId. The
Be layer pattern is Direct (Input → Final) — see LoginInput.

Failure mapping:
  - SemanticVariableException → 400 (email/password format invalid)
  - LoginFailedException      → 401 (no such email OR wrong password
                                      — combined, no user enumeration)

In the html context, public/index.php starts a PHP session before
dispatch and this resource mirrors `customerId` into the flat session
key read by HtmlSessionAdapter. The write is guarded by
an html APP_CONTEXT and PHP_SESSION_ACTIVE so app/test/prod contexts
keep their existing session behaviour and are not polluted by direct
`$_SESSION` writes.

Phase 3 — HTML FORM page. The resource builds a {@see \LoginForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
the HTML port can render real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only — VALIDATION AUTHORITY
STAYS WITH the Be Framework Becoming chain. On a domain rejection the
resource bridges the verdict onto the form (repopulated email + inline
error) so the Login page re-renders with EC-CUBE's exact form UX. The
JSON contexts (`app`, `prod`, `test`) ignore `body['form']`; the 1445
JSON-context tests assert key-wise on `body` and are unaffected.

FormFactory is self-sufficient (no Ray.Di bindings needed), so the
resource builds the form in every context cheaply; only the `html`
context's TwigRenderer actually renders it.




## GET
EC-CUBE goLogin — show the login form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). The `csrfToken` body field carries the trusted reference
{@see \CsrfToken::$token} issues, which the HTML port
renders into the form's hidden `_csrf_token` input so the
subsequent POST passes CSRF validation.

**ALPS**: `goLogin`



### Request

_No parameters required_

### Response

[Object: GET /login response](../schemas/get-login.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /login でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/login \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /login のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/login \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doLogin | [<code>page://self/login</code>](/login.md) |
| goCustomerRegistration | [<code>page://self/entry</code>](/entry.md) |
| doRequestPasswordReset | [<code>page://self/forgot-password</code>](/forgot-password.md) |
## POST
Phase B Slice 9: every form field is user-controlled input.

**ALPS**: `doLogin`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Optional | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |
| password | string | パスワード（入力） - 書き込み専用（ハッシュ化して保存） Fake観察文字長 50〜63; 観察値 '$2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG', '$2y$10$deputyplaceholder.hash.never.verified.0123456789abcdef', '$2y$10$zyxwvutsrqponmlkjihgfedcbaZYXWVUTSRQPONMLKJIHGFEDCBA9876', '$2y$12$dC7U8xCHBGmNT2TjlWbv6.ho4y.Lcezn5PT0ywpUsaxk0x49tUune', '$2y$10$shopownerplaceholder.hash.never.verified.0123456789ab', '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123', '$2y$10$0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRS', '$2y$12$placeholder.hash.never.verified.never.0123456789abcde'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | $2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG |
| mode | string | フォーム送信モード - HTMLフォーム送信をResource/JSON境界と区別するための任意パラメータ。ログイン画面では browser form re-render 判定にだけ使う。 |  | Optional | {"minLength":0,"maxLength":32} | login |


### Response

[Object: POST /login response](../schemas/post-login.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| customerStatus | int | 会員ステータス - 1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される Fake観察数値 1〜2; 観察値 '2', '1'。 | Required | {"enum":[1,2,3]} | 2 |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |

#### Links

| Relation | URL |
|----------|-----|
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |