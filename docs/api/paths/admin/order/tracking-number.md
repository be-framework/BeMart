<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/tracking-number
EC-CUBE doUpdateTrackingNumber — 伝票番号を更新する (Phase 3
ALPS-audit remediation).

PUT /admin/order/tracking-number

Inline single-row update of an order's shipping tracking number,
derived from EC-CUBE's `admin_shipping_update_tracking_number` route.
ALPS marks it `idempotent`; PUT is the matching verb.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderNo / trackingNumber)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## PUT
ALPS `doUpdateTrackingNumber` に対応する PUT 操作。

**ALPS**: `doUpdateTrackingNumber`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |
| trackingNumber | string | 荷物追跡番号（入力） - 配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/order/tracking-number response](../schemas/put-admin-order-tracking-number.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 注文メッセージ - /admin/order/tracking-number のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| trackingNumber | string|null | 荷物追跡番号 - 配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成 | Required | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"} |  |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
| goOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |