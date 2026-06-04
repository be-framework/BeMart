{% raw %}
<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/layout/layout
EC-CUBE doUpdateLayout — single-row endpoint (Wave 9 CMS). Only PUT
is exposed to the domain; layouts can be neither created nor deleted
via the admin UI (system-managed).

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminLayoutForm}
(Ray.WebFormModule AbstractForm) as `body['form']` so the admin layout
editor (`Content/layout.twig` port) can render the real layout-name
`<input>` via `{{ form.input(...) }}`.

NOTE — single-row prefill: the Be domain exposes no
`GetAdminLayoutInput` / `AdminLayoutFetched` (single-row fetch), so
`onGet` renders the NEW-layout form (the `admin_content_layout_new`
case — the layout designer with an empty block canvas). Pre-filling an
existing layout + its block positions would need a Be fetch Input — a
`be/src/` change out of this Phase 3 HTML wave's scope. FLAGGED:
follow-up to add `GetAdminLayoutInput` for existing-layout edit prefill.




## GET
Renders the layout editor form (new-layout case).

The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.



### Request

_No parameters required_

### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| layoutId | string | レイアウトID |  | Required |  |  |
| layoutName | string | レイアウト名 |  | Optional |  |  |


### Response

_Not available_
{% endraw %}
