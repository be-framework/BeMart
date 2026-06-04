<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/bulk-delete
EC-CUBE doBulkDeleteOrder — 受注を一括削除する (Wave 9η).

POST /admin/order/bulk-delete

Soft-delete semantics: each targeted row's `orderStatus` flips to
CANCEL(3). ALPS doc says "物理削除" but EC-CUBE keeps the row for
downstream reporting — see {@see \AdminOrdersBulkDeleted} for the
full rationale.

Unknown orderNos are silently skipped; `requestedCount` vs
`changedCount` lets the UI surface stale-grid anomalies. Mirrors
Wave 8 {@see \MyVendor\BeMart\Resource\Page\Admin\ProductBulkStatus}.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (list size / element format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNos | array |  |  | Required |  |  |


### Response

_Not available_