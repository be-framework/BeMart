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


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_