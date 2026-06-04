<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-order-pdf
EC-CUBE goExportOrderPdf — 帳票PDFをエクスポートする (Wave 9η,
**Phase 2 stub**).

GET /admin/order/export-order-pdf?orderNo=…

Real PDF generation (EC-CUBE: TCPDF + a configurable layout) is
Phase 2 scope. The stub returns a text/plain placeholder body keyed
by the targeted orderNo so the AUTHZ + URL surface can be exercised
in isolation. Despite the stubbed body, the response surfaces
`Content-Type: application/pdf` so downstream clients can wire the
download affordance ahead of the real renderer.

Failure mapping:
  - SemanticVariableException             → 400 (orderNo format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_