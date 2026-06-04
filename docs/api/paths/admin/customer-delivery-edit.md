<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-delivery-edit
EC-CUBE お届け先編集 — Customer Tier-2.

Thin GET renderer for `admin/Customer/delivery_edit.twig`, the customer
address-book entry editor. BeMart has no ALPS transition for persisting
a customer address in this wave, so the page exposes the empty
edit-form body shape for HTML rendering only — completing the Customer
section alongside the already-ported list/edit pages.

Admin-only — the AUTHZ guard rejects an anonymous admin with 403,
matching the sibling Setting/System Tier-2 renderers ({@see \System},
{@see \Security}, {@see \TwoFactorAuthEdit}).




## GET
The customer id comes from the admin UI (route param), so it is
user-controlled — same taint discipline as the sibling
{@see Customer} resource.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID |  | Optional |  |  |
| id | string |  |  | Optional |  |  |


### Response

_Not available_