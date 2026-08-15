<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/two-factor-auth-set
EC-CUBE admin 2段階認証 デバイス登録 — top-level wave, Phase 3.

Thin renderer for the admin 2FA device-setup screen
(`admin/two_factor_auth_set.twig`, extends `login_frame.twig`). This
is a LOGIN-CONTEXT page reached after correct credentials when the
member has no 2FA device yet, so — like the admin login page — it is
anonymous-accessible (no admin-firewall guard).

EC-CUBE's controller generates a TOTP secret server-side, binds it to
the pending login identity, renders it as a QR code (the JS in the
template builds the `otpauth://` URI) and verifies the first token.
BeMart mirrors that boundary through a session-backed login challenge:
`onGet` exposes the server-generated secret only when the password
step has established pending setup state.

Hard ActionRedirect completion: `onPut` drives the Be
`doSetTwoFactorAuth` transition ({@see \SetTwoFactorAuthInput} →
{@see \TwoFactorAuthConfigured}) — register the secret, then confirm by
verifying the first device code.

Without pending setup state `onGet` keeps the empty `authKey`
placeholder for render-test fidelity, and `onPut` refuses to configure
a device. Client-supplied legacy `loginId` / `authKey` fields are
ignored; the trusted identity and secret come only from the challenge.




## GET
Renders the admin 2FA device-setup form.

Anonymous-accessible (login-context): returns 200 regardless of
session state.

**ALPS**: `doSetTwoFactorAuth` - 二要素認証を設定する



### Request

_No parameters required_

### Response

[Object: GET /admin/two-factor-auth-set response](../schemas/get-admin-two-factor-auth-set.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /admin/two-factor-auth-set でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/admin/two-factor-auth-set \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4e8c\u8981\u7d20\u8a8d\u8a3c\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| memberName | string|null | 管理者名 - 管理者メンバーの表示名 | Required | {"minLength":0,"maxLength":255} |  |
| shopName | string|null | ショップ名 - ショップの表示名。フロント画面のヘッダやメールに表示 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| authKey | string|null | 二要素認証キー - /admin/two-factor-auth-set のレスポンスで扱う二要素認証キー。数値演算対象ではなく、照合・URL・配送追跡などに使う不透明な文字列識別子。 | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"} |  |
| csrfToken | string |  | Optional | {"$ref":"#/$defs/csrfToken"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doSetTwoFactorAuth | [<code>page://self/admin/two-factor-auth-set</code>](/admin/two-factor-auth-set.md) |
| goAdminLogin | [<code>page://self/admin/login</code>](/admin/login.md) |
## PUT
Registers the TOTP device after confirming the first code
(doSetTwoFactorAuth). ALPS marks this `idempotent` → PUT.

The pending login identity and server-generated candidate secret are
read from {@see \HtmlAdminLoginChallengeAdapter}. Legacy client fields
(`loginId`, `authKey`) may still arrive from old forms/tests, but are
deliberately ignored and never forwarded to the Be transition.

Failure mapping:
  - Invalid CSRF                  → 403 (interceptor)
  - Missing pending setup         → 403
  - SemanticVariableException     → 400 (malformed code)
  - TwoFactorAuthFailedException  → 400 (first code mismatch)

**ALPS**: `doSetTwoFactorAuth` - 二要素認証を設定する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deviceToken | string | 二要素認証デバイストークン（入力） - /admin/two-factor-auth-set のレスポンスで扱う二要素認証デバイストークン。数値演算対象ではなく、照合・URL・配送追跡などに使う不透明な文字列識別子。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 この値は2FAログインチャレンジでは互換入力としてのみ受け取り、信頼しない。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |
| authKey | string | 二要素認証キー（入力） - /admin/two-factor-auth-set のレスポンスで扱う二要素認証キー。数値演算対象ではなく、照合・URL・配送追跡などに使う不透明な文字列識別子。 この値は2FA設定では互換入力としてのみ受け取り、信頼しない。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| mode | string | フォーム送信モード - HTMLフォーム送信をResource/JSON境界と区別するための任意パラメータ。2FAデバイス登録画面では browser の 303 リダイレクト判定にだけ使う。 |  | Optional | {"minLength":0,"maxLength":32} | set |


### Response

[Object: PUT /admin/two-factor-auth-set response](../schemas/put-admin-two-factor-auth-set.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 二要素認証メッセージ - /admin/two-factor-auth-set のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |

#### Links

| Relation | URL |
|----------|-----|
| goTwoFactorAuth | [<code>page://self/admin/two-factor-auth</code>](/admin/two-factor-auth.md) |
| goAdminHome | [<code>page://self/admin/index</code>](/admin/index.md) |