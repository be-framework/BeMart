<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/login
EC-CUBE doAdminLogin — 管理者ログイン (Wave 4).

Resource is the HTTP entry point: builds AdminLoginInput, hands it
to Becoming, and on success returns the authenticated adminId. The
Be layer pattern is Direct (Input → Final) — see AdminLoginInput.

Failure mapping:
  - SemanticVariableException     → 400 (loginId/password format invalid)
  - AdminLoginFailedException     → 401 (no such loginId OR wrong
                                           password — combined, no
                                           user enumeration)

Mirrors Pilot 6 customer {@see \MyVendor\BeMart\Resource\Page\Login}
but for the admin firewall — distinct namespace under `Page\Admin\`
(different URI prefix `page://self/admin/login`), and the response
body carries admin shape (adminId / loginId / name / authority)
rather than customer shape.

Password verification now establishes only a pre-auth 2FA login
challenge. The flat admin session key read by
{@see \HtmlAdminSessionAdapter} is written after the existing-device
challenge or first-device setup succeeds, so login-context 2FA
resources never need to trust client-supplied identity.

Source-of-truth gap: alps.json does not currently carry a
`doAdminLogin` transition id (only customer `doLogin`). Using the
conventional name to parallel the customer side; ALPS profile is
expected to gain a matching transition in a later sweep.




## GET
Show the admin login form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state) — the admin firewall guard is on the POST. The `csrfToken`
body field carries the trusted reference {@see \CsrfToken::$token}
issues, which the HTML port renders into the form's hidden
`_csrf_token` input so the subsequent POST passes CSRF validation.

**ALPS**: `doAdminLogin`



### Request

_No parameters required_

### Response

[Object: GET /admin/login response](../schemas/get-admin-login.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /admin/login でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/admin/login \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /admin/login のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/admin/login \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doAdminLogin | [<code>page://self/admin/login</code>](/admin/login.md) |
## POST
Wave 4 / Phase B Slice 9: every form field is user-controlled
input — same taint discipline as the customer login.

**ALPS**: `doAdminLogin`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |
| password | string | パスワード（入力） - 書き込み専用（ハッシュ化して保存） Fake観察文字長 50〜63; 観察値 '$2y$12$stXeC3GBw5uMLkgK/6Vb0.R7XLnwERRqWM/Hl7rtAhp4IcHoK8eWi', '$2y$10$deputyplaceholder.hash.never.verified.0123456789abcdef', '$2y$10$zyxwvutsrqponmlkjihgfedcbaZYXWVUTSRQPONMLKJIHGFEDCBA9876', '$2y$12$lbpzHVyv.ytzJju.4xk9Au5tPePdQe1WFH6sLWeWwcvIKbn0vMnE.', '$2y$10$shopownerplaceholder.hash.never.verified.0123456789ab', '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123', '$2y$10$0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRS', '$2y$12$placeholder.hash.never.verified.never.0123456789abcde'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | $2y$12$stXeC3GBw5uMLkgK/6Vb0.R7XLnwERRqWM/Hl7rtAhp4IcHoK8eWi |
| mode | string | フォーム送信モード - HTMLフォーム送信をResource/JSON境界と区別するための任意パラメータ。管理ログイン画面では browser form re-render 判定にだけ使う。 |  | Optional | {"minLength":0,"maxLength":32} | login |


### Response

[Object: POST /admin/login response](../schemas/post-admin-login.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name | string|null | 処理表示名 - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 | Required | {"minLength":0,"maxLength":32} | テスト管理者 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| authority | int|null | 権限 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 | Required | {"minimum":0,"maximum":9} | 1 |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goAdminTop | [<code>page://self/admin/index</code>](/admin/index.md) |