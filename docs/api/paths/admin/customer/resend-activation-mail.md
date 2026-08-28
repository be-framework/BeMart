<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer/resend-activation-mail
EC-CUBE doResendActivationMail — 認証メールを再送する (Phase 3
ALPS-audit remediation).

POST /admin/customer/resend-activation-mail

From the admin customer-list screen an ADMIN resends the email-
verification (full-registration) mail to a 仮会員 (provisional
customer) who never followed the original activation link. Derived
from EC-CUBE's `admin_customer_resend` route. The mail carries an
activation URL embedding the customer's `secretKey`; the customer
later promotes to a full member via `doActivateCustomer`. ALPS marks
it `unsafe` — POST is the matching verb, each call sends a fresh mail.

Failure mapping (cross-firewall AUTHZ → existence → state ladder):
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (email format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - CustomerNotFoundException             → 404 (no such email)
  - CustomerAlreadyActivatedException     → 409 (not a 仮会員)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which emails resolve
(same anti-enumeration discipline as goCustomer).




## POST
ALPS `doResendActivationMail` に対応する POST 操作。

**ALPS**: `doResendActivationMail`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Required | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |


### Response

[Object: POST /admin/customer/resend-activation-mail response](../schemas/post-admin-customer-resend-activation-mail.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 会員メッセージ - /admin/customer/resend-activation-mail のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| email | string|null | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |

#### Links

| Relation | URL |
|----------|-----|
| goCustomer | [<code>page://self/admin/customer</code>](/admin/customer.md) |