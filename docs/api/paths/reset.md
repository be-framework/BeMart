<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /reset
EC-CUBE doResetPassword — リセットキーを検証して新しいパスワードを
保存する (Pilot 15, consumer pair to Pilot 14 doRequestPasswordReset).

Failure mapping (both -> 400, same code on purpose):
  - SemanticVariableException  → 400 (resetKey or password malformed)
  - ResetKeyInvalidException   → 400 (wrong key / expired / already used)

Both failures collapse to the same HTTP status (400 rather than
404) so an attacker cannot distinguish "format-invalid" from
"value-invalid" by status alone — same anti-enumeration design as
the merged ResetKeyInvalid exception itself.

Single-use is enforced inside the Be Final (token consumed via
`PasswordResetTokenStorageInterface::delete()` immediately on
success); this resource only translates the failure modes.




## GET
EC-CUBE goResetPassword — show the new-password form scaffolding
(EC-CUBE `Forgot/reset.twig`).

Pure form-info endpoint: no Be Framework, no domain logic.
Anonymous-accessible (the reset-key check is the POST's job). The
`resetKey` arrives as a query param on the emailed reset link and
is carried into a hidden form field for the subsequent POST.
`csrfToken` stays `null` — the EventListener mirrors the Symfony
token into the session for the POST (same as Login).

**ALPS**: `doResetPassword`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| resetKey | string | パスワードリセットキー（入力） - パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア Fake観察文字長 32〜34; 観察値 'valid-reset-key-pilot15-aaaa1111', 'expired-token-key-pilot15-aaaa1111'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | valid-reset-key-pilot15-aaaa1111 |


### Response

[Object: GET /reset response](../schemas/get-reset.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /reset でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/reset \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30d1\u30b9\u30ef\u30fc\u30c9\u518d\u8a2d\u5b9a\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| resetKey | string|null | パスワードリセットキー - パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア Fake観察文字長 32〜34; 観察値 'valid-reset-key-pilot15-aaaa1111', 'expired-token-key-pilot15-aaaa1111'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@-]+$","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"} | valid-reset-key-pilot15-aaaa1111 |
| submitTo | object|null | フォーム送信先リンク - /reset のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/reset \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doResetPassword | [<code>page://self/reset</code>](/reset.md) |
| goLogin | [<code>page://self/login</code>](/login.md) |
## POST
ALPS `doResetPassword` に対応する POST 操作。

**ALPS**: `doResetPassword`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| resetKey | string | パスワードリセットキー（入力） - パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア Fake観察文字長 32〜34; 観察値 'valid-reset-key-pilot15-aaaa1111', 'expired-token-key-pilot15-aaaa1111'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | valid-reset-key-pilot15-aaaa1111 |
| password | string | パスワード（入力） - 書き込み専用（ハッシュ化して保存） Fake観察文字長 50〜63; 観察値 '$2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG', '$2y$10$deputyplaceholder.hash.never.verified.0123456789abcdef', '$2y$10$zyxwvutsrqponmlkjihgfedcbaZYXWVUTSRQPONMLKJIHGFEDCBA9876', '$2y$12$dC7U8xCHBGmNT2TjlWbv6.ho4y.Lcezn5PT0ywpUsaxk0x49tUune', '$2y$10$shopownerplaceholder.hash.never.verified.0123456789ab', '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123', '$2y$10$0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRS', '$2y$12$placeholder.hash.never.verified.never.0123456789abcde'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | $2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG |


### Response

[Object: POST /reset response](../schemas/post-reset.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goLogin | [<code>page://self/login</code>](/login.md) |