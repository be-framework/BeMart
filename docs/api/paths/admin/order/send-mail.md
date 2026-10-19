<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/send-mail
EC-CUBE doSendOrderMail — 受注メールを送信する (Wave 9η).

POST /admin/order/send-mail

Reuses {@see \MyVendor\BeMart\Be\Reason\Service\MailerInterface::sendOrderConfirmation}
(Pilot 5) — the same call that fires after a customer-driven
checkout. The custom subject / body overrides ALPS surfaces on
`doSendOrderMail.descriptor` are not wired in Wave 9η (the Mailer
interface only takes the order entity); Phase 2 will extend the
Mailer contract.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## GET
EC-CUBE 受注メール送信 — Order Tier-2.

Thin GET renderer for `admin/Order/mail.twig` — the order-mail
composition screen. The POST below re-sends the confirmation
mail; this GET serves the composition form keyed by the order.
AUTHZ is a direct admin-session check (Pattern B — no Be
transition is invoked on the GET path). The composition fields
render blank so the page is faithful with empty JSON-backed fake storage.

**ALPS**: `doSendOrderMail`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/send-mail response](../schemas/get-admin-order-send-mail.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| doSendOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |
| goOrderMailConfirm | [<code>page://self/admin/order/mail-confirm</code>](/admin/order/mail-confirm.md) |
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
## POST
ALPS `doSendOrderMail` に対応する POST 操作。

**ALPS**: `doSendOrderMail`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: POST /admin/order/send-mail response](../schemas/post-admin-order-send-mail.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| mailBody | string|null | メール本文 - 送信済みメールのプレーンテキスト本文 Fake観察文字長 43〜43; 観察値 'この度はご注文いただきありがとうございます。\n商品の発送まで今しばらくお待ちください。'。 | Required | {"minLength":0,"maxLength":128} | この度はご注文いただきありがとうございます。
商品の発送まで今しばらくお待ちください。 |
| message | string|null | 注文メッセージ - /admin/order/send-mail のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| sendDate | string | 送信日時 - メールの送信日時 Fake観察文字長 19〜19; 観察値 '2026-04-01 10:05:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-04-01 10:05:00 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| mailSubject | string|null | メール件名 - メールの件名。テンプレート変数を含む場合あり Fake観察文字長 13〜13; 観察値 'ご注文ありがとうございます'。 | Required | {"minLength":0,"maxLength":32} | ご注文ありがとうございます |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
| goExportOrderPdf | [<code>page://self/admin/order/export-order-pdf</code>](/admin/order/export-order-pdf.md) |
| goExportOrder | [<code>page://self/admin/order/export-order</code>](/admin/order/export-order.md) |