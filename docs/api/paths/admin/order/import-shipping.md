<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/import-shipping
EC-CUBE doImportShippingCsv — 配送CSVをインポートする (Wave 9η,
**Phase 2 stub**).

POST /admin/order/import-shipping

Mirrors the Wave 8 {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Csv::onPost}
stub — accepts the CSV body as a plain string, returns 202 +
`accepted=false` with a notice so callers cannot mistake the stub
for a real import. The full parser (tracking-number column,
shipDate parsing, dry-run preview) is Phase 2.

Failure mapping:
  - Invalid CSRF                          → 403
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
EC-CUBE 出荷CSV登録 — Order Tier-2.

Thin GET renderer for `admin/Order/csv_shipping.twig` — the
shipping-CSV upload form. The POST below accepts the uploaded
CSV; this GET serves the upload-form shell. AUTHZ is a direct
admin-session check (Pattern B — no Be transition is invoked on
the GET path); a non-admin firewall is refused with 403.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string |  |  | Required |  |  |


### Response

_Not available_