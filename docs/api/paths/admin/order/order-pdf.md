<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/order-pdf
EC-CUBE 帳票出力 — Order Tier-2 (`admin/Order/order_pdf.twig`).

GET /admin/order/order-pdf?orderNo=…

The delivery-note (納品書) PDF options screen: the admin sets a
title / greeting / note / print date, then submits to the PDF
exporter. The actual PDF generation lives at the sibling
{@see \ExportOrderPdf} resource (a Phase 2 stub that streams
`application/pdf`); this resource is the options FORM only.

AUTHZ is a direct admin-session check (Pattern B — this GET serves
the form shell, no Be transition is invoked); a non-admin firewall
is refused with 403. The options form renders blank so the page is
faithful with empty JSON-backed fake storage.




## GET
ALPS `goAdminOrderOrderPdf` に対応する GET 操作。

**ALPS**: `goAdminOrderOrderPdf`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/order-pdf response](../schemas/get-admin-order-order-pdf.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goExportOrderPdf | [<code>page://self/admin/order/export-order-pdf</code>](/admin/order/export-order-pdf.md) |
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |