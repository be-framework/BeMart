<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-order-pdf
EC-CUBE goExportOrderPdf — 帳票PDFをエクスポートする.

GET /admin/order/export-order-pdf?orderNos[]=…

The Resource only normalizes EC-CUBE's `ids[]`/legacy `orderNo`
request shape, then calls the Be Final. TCPDF/FPDI rendering is kept
behind OrderPdfCompatibilityInterface.

Failure mapping:
  - SemanticVariableException             → 400 (orderNos format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo; all-or-nothing)




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNos |  |  | array () | Optional |  |  |
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_