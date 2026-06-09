<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/mail-confirm
EC-CUBE 受注メール確認 — Order Tier-2 (`admin/Order/mail_confirm.twig`).

GET /admin/order/mail-confirm?orderNo=…

The confirmation step shown between the mail composer
({@see \SendMail}) and the actual send: a read-only preview of the
subject / body the admin is about to send. EC-CUBE renders the
composed mail content here; the Be domain re-sends the
order-confirmation mail keyed by orderNo only
({@see \MyVendor\BeMart\Be\Input\AdminSendOrderMailInput}), so this
page carries the orderNo through to the send action and renders the
confirm-and-send shell.

AUTHZ is a direct admin-session check (Pattern B — this is a
read-only preview page, no Be transition is invoked); a non-admin
firewall is refused with 403.




## GET
ALPS `goAdminOrderMailConfirm` に対応する GET 操作。

**ALPS**: `goAdminOrderMailConfirm`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/mail-confirm response](../schemas/get-admin-order-mail-confirm.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| doSendOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |
| goOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |