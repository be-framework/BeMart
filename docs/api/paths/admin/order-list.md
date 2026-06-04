<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order-list
EC-CUBE goOrderList — 受注一覧 (Wave 7, admin order grid).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
{@see \UnauthorizedAdminAccessException} when
{@see \MyVendor\BeMart\Be\Reason\Service\AdminSession}
reports no admin session; we map that to 403. Distinct from the
customer-side 401: admin and customer firewalls are parallel (Wave 4
decision).

Failure mapping:
  - SemanticVariableException             → 400 (limit / offset format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Pagination knobs (`limit` + `offset`) mirror the Wave 6R OrderHistory
resource. The original EC-CUBE admin search form additionally supports
orderNo / customerName / dateRange / orderStatus / paymentMethod /
deliveryMethod filters — those are Phase 2 scope.

Hypermedia: links to the per-order detail and to the new-order create
affordance (doCreateOrder, deferred — same forward-declaration
convention as CustomerList).




## GET
Wave 7: pagination knobs are admin-form input. Same taint
discipline as Wave 5 / Wave 6 admin resources.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| limit | int |  | 50 | Optional |  |  |
| offset | int |  | 0 | Optional |  |  |


### Response

_Not available_