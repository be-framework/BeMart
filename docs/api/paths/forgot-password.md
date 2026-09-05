<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /forgot-password
EC-CUBE doRequestPasswordReset — パスワードリセット依頼 (Pilot 14).

Anti-enumeration: the response code (200) and body shape are
identical regardless of whether the supplied email is actually
registered. A real attacker cannot probe for valid emails by
watching for differences in status, body, or timing.

The `issued` flag in the body deliberately reports the same string
for both branches; callers that need to programmatically check
delivery must reach into the test-only FakeMailer (which records
actual dispatches).

Phase 3 — HTML FORM page. `onGet` renders the password-reset-request
form (EC-CUBE `Forgot/index.twig`): the resource builds a
{@see \ForgotForm} (Ray.WebFormModule AbstractForm) and exposes it as
`body['form']`. VALIDATION AUTHORITY STAYS WITH the Be Framework
Becoming chain. The JSON contexts ignore `body['form']`.




## GET
EC-CUBE goRequestPasswordReset — show the password-reset-request
form scaffolding.

Pure form-info endpoint: no Be Framework, no domain logic.
Anonymous-accessible (returns 200 regardless of session state).
`csrfToken` stays `null` — the EventListener mirrors the Symfony
token into the session for the subsequent POST (same as Login).

**ALPS**: `doRequestPasswordReset`



### Request

_No parameters required_

### Response

[Object: GET /forgot-password response](../schemas/get-forgot-password.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /forgot-password でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/forgot-password \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30d1\u30b9\u30ef\u30fc\u30c9\u518d\u8a2d\u5b9a\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /forgot-password のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/forgot-password \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doRequestPasswordReset | [<code>page://self/forgot-password</code>](/forgot-password.md) |
| goLogin | [<code>page://self/login</code>](/login.md) |
## POST
ALPS `doRequestPasswordReset` に対応する POST 操作。

**ALPS**: `doRequestPasswordReset`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Required | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |


### Response

[Object: POST /forgot-password response](../schemas/post-forgot-password.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | パスワード再設定メッセージ - /forgot-password のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |

#### Links

| Relation | URL |
|----------|-----|
| goLogin | [<code>page://self/login</code>](/login.md) |