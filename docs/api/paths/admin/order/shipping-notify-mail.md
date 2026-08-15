<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/shipping-notify-mail
EC-CUBE doSendShippingNotifyMail — 出荷通知メールを送信する (Phase 3
ALPS-audit remediation).

POST /admin/order/shipping-notify-mail

Sends the "your order has shipped" mail for a finalized order,
derived from EC-CUBE's `admin_shipping_notify_mail` route. Distinct
from {@see \SendMail} (the order-received mail). ALPS marks it
`unsafe` — POST is the matching verb, each call sends a fresh mail.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## GET
Displays the shipping-notification confirmation form.

**ALPS**: `doSendShippingNotifyMail` - 出荷通知メールを送信する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","default":""} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/shipping-notify-mail response](../schemas/get-admin-order-shipping-notify-mail.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| orderNo | string|null | 出荷通知対象の注文番号 - 出荷通知メールを送信する対象注文の顧客向け注文番号。画面では確認表示とPOST hidden inputに使用する。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| customerId | string|null | 出荷通知対象の会員ID - 対象注文に紐づく会員ID。ゲスト購入または退会済み境界ではnullになり、画面ではゲスト購入として表示する。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| message | string | 出荷通知メール確認メッセージ - 管理者に出荷通知メール送信の確認を促す画面メッセージ。注文時のお問い合わせ本文ではない。 | Required | {"minLength":1,"maxLength":80} | 出荷通知メールを送信します。よろしいですか？ |
| csrfToken | string|null | 出荷通知メール送信フォームCSRFトークン - POST /admin/order/shipping-notify-mail のhidden inputで送るCSRFトークン。Resource bodyではnullでも、Twigが実フォーム用トークンを補う。 | Required | {"minLength":0,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]*$","$comment":"CSRF\u5024\u306fCsrfProtected\u5883\u754c\u306e\u8cac\u52d9\u3002GET body\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u6587\u8108\u3092\u793a\u3059\u305f\u3081\u306bnull\u3092\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| submitTo | object | 出荷通知メール送信リンク - 確認画面から出荷通知メール送信Resourceへ遷移するALPS unsafe遷移。 | Required | {"required":["method","href"],"additionalProperties":false,"properties":{"method":{"type":"string","enum":["POST"],"title":"\u9001\u4fe1HTTP\u30e1\u30bd\u30c3\u30c9","description":"\u51fa\u8377\u901a\u77e5\u30e1\u30fc\u30eb\u9001\u4fe1\u306f\u526f\u4f5c\u7528\u3092\u6301\u3064\u305f\u3081POST\u306e\u307f\u8a31\u53ef\u3059\u308b\u3002"},"href":{"type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"title":"\u51fa\u8377\u901a\u77e5\u30e1\u30fc\u30eb\u9001\u4fe1Resource URI","description":"POST\u5148\u306eBEAR Resource URI\u3002HTML\u3067\u306fcanonical path /admin/order/shipping-notify-mail \u306b\u5bfe\u5fdc\u3059\u308b\u3002","example":"page://self/admin/order/shipping-notify-mail"}}} |  |

#### Links

| Relation | URL |
|----------|-----|
| doSendShippingNotifyMail | [<code>page://self/admin/order/shipping-notify-mail</code>](/admin/order/shipping-notify-mail.md) |
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
## POST
ALPS `doSendShippingNotifyMail` に対応する POST 操作。

**ALPS**: `doSendShippingNotifyMail` - 出荷通知メールを送信する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: POST /admin/order/shipping-notify-mail response](../schemas/post-admin-order-shipping-notify-mail.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| trackingNumber | string|null | 荷物追跡番号 - 配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成 | Required | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"} |  |
| message | string|null | 注文メッセージ - /admin/order/shipping-notify-mail のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |