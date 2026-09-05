<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/logout
EC-CUBE doAdminLogout — 管理者ログアウト (Wave 4, Direct, idempotent).

Resource is the HTTP entry point: builds AdminLogoutInput, hands it
to Becoming, and on success returns whether there was an admin to
log out along with their adminId. The Be layer pattern is Direct
(Input → Final) — see AdminLogoutInput.

Failure mapping (intentionally narrow):
  - missing/invalid CSRF token  → 403 (Slice 8 uniform CSRF guard)
  - SemanticVariableException   → 400 (defensive; AdminLogoutInput has
                                         no semantically-validated
                                         fields, so this is unreachable
                                         today but kept uniform with
                                         the rest of Slice 8/9)

Notably absent: 401/403 for "no admin session". Per ALPS
`type=idempotent`, logging out an admin-anonymous client is a no-op
success — the response body simply carries `wasLoggedIn=false`.

In the html context the session-writer port ends the browser session behind
this resource, including the pre-auth 2FA challenge state. Non-html contexts
bind a no-op writer, so Resource code does not branch on environment or
touch PHP session storage.

Source-of-truth gap: alps.json does not currently carry a
`doAdminLogout` transition id; using the conventional name to
parallel `doLogout` for the customer side.




## POST
Wave 4 / Phase B Slice 9: the CSRF token is user-controlled
input — same taint discipline as the customer logout.

**ALPS**: `doAdminLogout`



### Request

_No parameters required_

### Response

[Object: POST /admin/logout response](../schemas/post-admin-logout.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/logout のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| wasLoggedIn | boolean|null | ログイン済み結果 - /admin/logout の処理状態を示すログイン済み結果。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goAdminLogin | [<code>page://self/admin/login</code>](/admin/login.md) |