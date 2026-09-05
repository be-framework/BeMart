<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /logout
EC-CUBE doLogout — 会員ログアウト (Pilot — Direct, idempotent).

Resource is the HTTP entry point: builds LogoutInput, hands it to
Becoming, and on success returns whether there was anyone to log out
along with their customerId. The Be layer pattern is Direct
(Input → Final) — see LogoutInput.

Failure mapping (intentionally narrow):
  - missing/invalid CSRF token       → 403 (Slice 8 uniform CSRF guard)
  - SemanticVariableException        → 400 (defensive; LogoutInput has
                                             no semantically-validated
                                             fields, so this is unreachable
                                             today but kept uniform with
                                             the rest of Slice 8/9)

Notably absent: 401. Per ALPS `type=idempotent`, logging out an
anonymous client is a no-op success — the response body simply
carries `wasLoggedIn=false`. The resource MUST NOT treat the absence
of a session as an error.

In the html context the session-writer port ends the browser session behind
this resource. Non-html contexts bind a no-op writer, so Resource code does
not branch on environment or touch PHP session storage.




## POST
Phase B Slice 9: the CSRF token is user-controlled input.

**ALPS**: `doLogout`



### Request

_No parameters required_

### Response

[Object: POST /logout response](../schemas/post-logout.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /logout のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| wasLoggedIn | boolean|null | ログイン済み結果 - /logout の処理状態を示すログイン済み結果。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |