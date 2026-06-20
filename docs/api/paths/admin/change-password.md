<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/change-password
EC-CUBE admin パスワード変更 — top-level wave, Phase 3.

Thin renderer for the forced/voluntary admin password-change screen
(`admin/change_password.twig`). EC-CUBE's controller validates the
current password and applies the new one via the Symfony security
password hasher. There is no Be Framework `doChangeAdminPassword`
transition (no such id in `alps.json`, and the be/ domain layer is
frozen for this wave), so this resource is a THIN RENDERER: it
enforces the admin firewall and exposes an
{@see \AdminChangePasswordForm} as `body['form']` for the HTML page to
render via `{{ form.input(...) }}`.

Hard ActionRedirect completion: `onPost` now drives the Be
`doChangePassword` transition ({@see \ChangeAdminPasswordInput} →
{@see \AdminPasswordChanged}) — current-password verification +
re-hash over the admin storage, with the credential/CSRF/session
boundary enforced Be/BEAR-side.




## GET
Renders the admin password-change form.

Admin-only: returns 403 for an anonymous request — the same
firewall contract as the other admin pages, enforced at the
resource layer (there is no Be Final to raise
`UnauthorizedAdminAccessException`).

**ALPS**: `doChangePassword`



### Request

_No parameters required_

### Response

[Object: GET /admin/change-password response](../schemas/get-admin-change-password.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| fields | array|null | 静的表示フィールド - /admin/change-password でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/admin/change-password \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |

#### Links

| Relation | URL |
|----------|-----|
| doChangePassword | [<code>page://self/admin/change-password</code>](/admin/change-password.md) |
| goAdminHome | [<code>page://self/admin/index</code>](/admin/index.md) |
## POST
Applies the admin's own password change (doChangePassword).

Failure mapping:
- Invalid CSRF                         → 403 (interceptor)
- SemanticVariableException            → 400
- InvalidCurrentPasswordException      → 400
- PasswordConfirmationMismatchException→ 400
- PasswordPolicyViolationException     → 400
- UnauthorizedAdminAccessException     → 403 (no admin session)
- AdminNotFoundException               → 404 (stale session)

**ALPS**: `doChangePassword`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| currentPassword | string | フォーム文脈項目（入力） - /admin/change-password のフォーム文脈で使うフォーム文脈項目。入力保持、初期値、再表示に必要な補助値。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| changePasswordFirst | string | フォーム文脈項目（入力） - /admin/change-password のフォーム文脈で使うフォーム文脈項目。入力保持、初期値、再表示に必要な補助値。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| changePasswordSecond | string | フォーム文脈項目（入力） - /admin/change-password のフォーム文脈で使うフォーム文脈項目。入力保持、初期値、再表示に必要な補助値。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/change-password response](../schemas/post-admin-change-password.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/change-password のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goAdminHome | [<code>page://self/admin/index</code>](/admin/index.md) |