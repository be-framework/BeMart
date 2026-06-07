<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/empty-page
EC-CUBE admin プラグイン拡張用スロット — top-level wave, Phase 3.

Thin renderer for `admin/empty_page.twig` — EC-CUBE's near-empty
`{% extends default_frame %}` stub. It carries no content of its own;
it exists as a routable extension SLOT that plugins fill via template
events. The fan-out plan (`docs/phases/admin-fanout-plan.md`) lists it
as a borderline page kept as a trivial port.

No domain logic, no body data: the resource enforces the admin
firewall (so the slot is admin-only, like every other admin page) and
renders the admin frame with an empty `main` block. There is nothing
to enrich and no missing-body-field — the page IS empty by design.




## GET
Renders the empty admin extension-slot page.

Admin-only: returns 403 for an anonymous request — the same
firewall contract as the other admin pages.

**ALPS**: `goAdminEmptyPage`



### Request

_No parameters required_

### Response

[Object: GET /admin/empty-page response](../schemas/get-admin-empty-page.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
