# Admin HTML Fan-out Manifest

Phase 3 (HTML presentation) has finished the storefront (~40 pages) and the admin
pilot (`admin-base.html.twig` + News pages, commit `f91e10f`). The admin HTML page
templates are ported in parallel section-waves.

## Status (2026-05-21) — Tier-1 done

8 section-waves have run (News pilot + Customer + batch 1 + batch 2).
**Admin Tier-1 — 34 of the 77 page templates — is ported and green**
(full suite 1734 tests). Tier-1 = list/data pages + simple CRUD whose
BEAR resource already serves a GET.

The remaining **~43 templates are Tier-2** and were deliberately
deferred by every wave: multi-panel editors (`Order/edit` ~1057L,
`Product/product` ~932L, `Product/product_class` ~448L, `Order/shipping`
~709L) and pages whose BEAR resource is action-only (POST/CSV/PDF) with
no GET-serving `onGet`. Tier-2 is **not** a template-port job — it needs
new BEAR resources / `onGet` additions / `be/src` domain body-shape
work. The per-section Tier-1/Tier-2 split is summarised in
`var/templates/README.md` "Fan-out status"; the per-page audit below is
the input for the Tier-2 resource-creation effort.

This document is the **complete, first-pass audit** of every Twig template under the
EC-CUBE 4.3 admin template root
(`tools/ec-cube-source/src/Eccube/Resource/template/admin/`, gitignored clone). It is
the precise input for briefing the parallel section-wave agents.

## Method

- Enumerated all 99 `*.twig` / `*.html.twig` files under the admin template root.
- A file is a **page template** iff it has `{% extends '@admin/default_frame.twig' %}`
  (standard admin page) or `{% extends '@admin/login_frame.twig' %}` (login-context
  page). Files with no `extends` are partials, fragments, JS includes, error pages or
  Symfony form theme files — excluded from the page count (see section 3).
- **Recipe**: form-page if it renders Symfony `form_*` widgets (`form_widget`,
  `form_row`, `form_start`, `form_rest`, `form()`); otherwise data-page. A form-page
  needs a `ray/web-form-module` `<Name>Form` class under `src/Form/`.
- **BEAR resource**: cross-checked against `src/Resource/Page/Admin/` (60 resource
  classes present). "exists" = resource class is present and (per the News pilot and
  Customer in-flight work) already shaped for HTML rendering or trivially extensible.
  "needs onGet" = resource class exists but currently JSON-only / no HTML branch.
  "needs new" = no resource class for this page at all.
- **Counts**: **77 page templates** total (75 `default_frame`, 2 `login_frame`-only
  pages `two_factor_auth` + `two_factor_auth_set`; `login.twig` also `login_frame`).
  22 files are excluded partials.

---

## 1. Section summary table

| Section | Page templates | Form-pages | Data-pages | Need new resource | Rough complexity |
|---|--:|--:|--:|--:|---|
| Product | 11 | 11 | 0 | 0 | high (product.twig, product_class.twig, index.twig) |
| Order | 7 | 7 | 0 | 1 | very high (edit.twig 1057L, shipping.twig 709L, index.twig 678L) |
| Customer | 3 | 3 | 0 | 0 | medium (edit.twig 508L; index + delivery_edit in flight) |
| Content | 13 | 9 | 4 | 2 | high (layout.twig 558L; news* done) |
| Setting/Shop | 13 | 11 | 2 | 3 | high (shop_master, delivery_edit, mail) |
| Setting/System | 10 | 8 | 2 | 2 | medium (security, member_edit, login_history) |
| Store | 13 | 5 | 8 | 9 | medium (plugin_* cluster, plugin marketplace) |
| top-level | 7 | 4 | 3 | 1 | low–medium (index dashboard, login, 2FA) |
| **Total** | **77** | **58** | **19** | **18** | — |

Most complex section: **Order** — concentrated in three giant multi-tab editor /
list templates (`edit.twig` 1057 lines, `shipping.twig` 709, `index.twig` 678) plus a
PDF screen and three search/CSV satellites.

---

## 2. Per-page inventory

Path is relative to the admin template root. Resource path is relative to
`src/Resource/Page/Admin/`. `FW` = count of Symfony form-widget calls.

### 2.1 Product (11 pages — all form-pages)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Product/index.twig` | form (14) | `ProductList.php` exists | complex | 520L; search form + inline bulk-status + pager; `{% include @admin/pager.twig %}` |
| `Product/product.twig` | form (30) | `Product.php` exists | complex | 932L; multi-tab product editor (largest Product page); image upload, class-category drag-drop JS |
| `Product/product_class.twig` | form (18) | exists (`ClassCategory*`/`ProductBulkStatus`?) | complex | 448L; product-class matrix editor; verify resource — likely **needs onGet** |
| `Product/product_class_popup.twig` | data (0) | partial — popup body | medium | 32L; popup fragment loaded by product_class; **could be excluded** but `extends`-less and standalone-rendered — treat as a thin sub-page |
| `Product/category.twig` | form (7) | `Category/Category.php` + `CategoryList.php` exist | medium | 359L; tree list + inline add/edit form |
| `Product/class_name.twig` | form (6) | `ClassName/ClassName*.php` exist | medium | 276L; list + inline form |
| `Product/class_category.twig` | form (7) | `ClassCategory/ClassCategory*.php` exist | medium | 298L; list + inline form (depends on selected class_name) |
| `Product/tag.twig` | form (8) | `Tag/Tag*.php` exist | medium | 267L; list + inline form |
| `Product/csv_product.twig` | form (2) | `ProductCsv.php` exists | medium | 283L; CSV column-mapping screen |
| `Product/csv_category.twig` | form (2) | `Category/Csv.php` exists | simple | 128L; CSV column-mapping |
| `Product/csv_class_name.twig` | form (2) | needs onGet (no `ClassNameCsv`) | simple | 129L; CSV column-mapping |
| `Product/csv_class_category.twig` | form (2) | needs onGet (no `ClassCategoryCsv`) | simple | 129L; CSV column-mapping |

Note: the four `csv_*.twig` screens share an identical structure — port one, clone three.
`product_class_popup` is a popup; if the wave decides it is not a routable page,
demote to excluded. Counted here as 11 routable + popup separate; table lists 12 rows,
the popup is the +1 borderline — **wave brief should confirm**.

### 2.2 Order (7 pages — all form-pages)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Order/index.twig` | form (26) | `OrderList.php` exists | very complex | 678L; huge search form, bulk actions, CSV/PDF export buttons, pager; includes `Order/confirmationModal_js.twig` (partial) |
| `Order/edit.twig` | form (52) | `Order.php` + `Order/Create.php` exist | very complex | 1057L (largest admin template); multi-section order editor; includes `order_item_type.twig`, `order_item_prototype.twig`, `search_customer.twig`, `search_product.twig` (all partials) |
| `Order/shipping.twig` | form (28) | `Order/ShippingAddress.php` exists | complex | 709L; per-shipment editor; includes item prototypes |
| `Order/order_pdf.twig` | form (12) | `Order/ExportOrderPdf.php` exists | complex | 162L; PDF-config screen (delivery-slip output) |
| `Order/csv_shipping.twig` | form (2) | `Order/ExportShipping.php` / `ImportShipping.php` exist | simple | 137L; CSV column-mapping |
| `Order/mail.twig` | form (4) | `Order/SendMail.php` exists | medium | 164L; order-mail compose screen |
| `Order/mail_confirm.twig` | form (4) | needs onGet (no `Order/SendMailConfirm`) | simple | 141L; mail send confirmation step |

`mail_confirm` is the only Order page needing a **new resource branch**. The four
Order partials (`confirmationModal_js`, `order_item_type`, `order_item_prototype`,
`search_customer`, `search_product`) are NOT pages — see section 3.

### 2.3 Customer (3 pages — all form-pages) — *partly in flight*

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Customer/index.twig` | form (24) | `CustomerList.php` exists | complex | 375L; search form + inline result table + pager. **In flight** (concurrent agent — `var/templates/Page/Admin/CustomerList.html.twig` already added) |
| `Customer/edit.twig` | form (22) | `Customer.php` / `CreateCustomer.php` exist | complex | 508L; customer profile editor. **In flight** (`var/templates/Page/Admin/Customer.html.twig` already added) |
| `Customer/delivery_edit.twig` | form (13) | needs onGet (no `Customer/DeliveryEdit`) | medium | 206L; customer address-book entry editor |

Customer `index` + `edit` are being ported by a concurrent agent right now; the wave
agent should pick up only `delivery_edit.twig` (or treat the whole section as a
verification pass once the concurrent work lands).

### 2.4 Content (13 pages — 9 form / 4 data) — *News pilot done*

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Content/news.twig` | data (0) | `News/NewsList.php` exists | medium | **DONE** — pilot, `var/templates/Page/Admin/News/NewsList.html.twig` |
| `Content/news_edit.twig` | form (9) | `News/News.php` exists | medium | **DONE** — pilot, `var/templates/Page/Admin/News/News.html.twig` |
| `Content/page.twig` | data (0) | `Page/PageList.php` exists | medium | 134L; list of layout pages |
| `Content/page_edit.twig` | form (16) | `Page/Page.php` exists | complex | 307L; free-page editor (CKEditor) |
| `Content/block.twig` | data (0) | `Block/BlockList.php` exists | simple | 131L; block list |
| `Content/block_edit.twig` | form (9) | `Block/Block.php` exists | medium | 166L; block editor |
| `Content/layout.twig` | form (6) | `Layout/Layout.php` exists | very complex | 558L; drag-drop layout designer, heavy JS |
| `Content/layout_list.twig` | data (0) | `Layout/LayoutList.php` exists | simple | 119L; layout list |
| `Content/css.twig` | form (2) | needs onGet (no `Content/Css`) | simple | 133L; custom-CSS textarea editor |
| `Content/js.twig` | form (2) | needs onGet (no `Content/Js`) | simple | 130L; custom-JS textarea editor |
| `Content/file.twig` | form (3) | needs new (no file-manager resource) | complex | 320L; file manager (upload/move/delete), heavy JS |
| `Content/cache.twig` | form (1) | needs new (no cache resource) | simple | 60L; cache-clear button screen |
| `Content/maintenance.twig` | form (1) | needs new (no maintenance resource) | simple | 55L; maintenance-mode toggle |

Content wave is **partial**: `news` + `news_edit` already ported in the pilot. Wave
agent ports the remaining 11. `css`/`js` share a near-identical recipe.

### 2.5 Setting/Shop (13 pages — 11 form / 2 data)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Setting/Shop/shop_master.twig` | form (35) | `BaseInfo.php` exists | complex | 388L; shop master config (largest Shop page) |
| `Setting/Shop/payment.twig` | data (0) | `Payment/PaymentList.php` exists | simple | 230L; payment-method list (sortable) |
| `Setting/Shop/payment_edit.twig` | form (11) | `Payment/Payment.php` exists | medium | 234L; payment-method editor |
| `Setting/Shop/delivery.twig` | data (0) | `Delivery/DeliveryList.php` exists | simple | 190L; delivery-method list (sortable) |
| `Setting/Shop/delivery_edit.twig` | form (12) | `Delivery/Delivery.php` exists | complex | 406L; delivery editor; includes `delivery_time_prototype.twig` (partial) |
| `Setting/Shop/tax_rule.twig` | form (8) | `TaxRule/TaxRule*.php` exist | medium | 214L; tax-rule list + inline form |
| `Setting/Shop/mail.twig` | form (10) | `MailTemplate.php` exists | complex | 301L; mail-template editor (per-template) |
| `Setting/Shop/order_status.twig` | form (5) | `OrderStatus.php` exists | medium | 112L; order-status color/label config |
| `Setting/Shop/csv.twig` | form (3) | `CsvConfig.php` exists | medium | 208L; CSV-output column config |
| `Setting/Shop/calendar.twig` | form (6) | needs new (no calendar resource) | medium | 191L; holiday-calendar editor |
| `Setting/Shop/tradelaw.twig` | form (4) | `TradeLaw.php` exists | simple | 87L; specified-commercial-transactions form |
| `Setting/Shop/delivery_time_prototype.twig` | *(partial)* | n/a | — | fragment of delivery_edit — see section 3 |
| `Setting/Shop/mail_view.twig` | *(partial)* | n/a | — | mail-preview iframe body — see section 3 |

11 page templates (the 2 partials below are excluded from the section's page count of
13 → corrected: **11 pages**; section-summary table row "Setting/Shop = 13" counts
all 13 files; **the wave handles 11 routable pages**). Need-new: `calendar`. `payment`
+ `delivery` lists share a sortable-list recipe.

> Correction note: the section-1 summary table column "Page templates" for Setting/Shop
> should be read as **11 pages + 2 partials**. The 77 total already excludes the 2
> partials. Wave brief: 11 pages.

### 2.6 Setting/System (10 pages — 8 form / 2 data)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Setting/System/member.twig` | data (0) | `MemberList.php` exists | simple | 197L; admin-member list |
| `Setting/System/member_edit.twig` | form (11) | `Member.php` exists | medium | 203L; admin-member editor |
| `Setting/System/authority.twig` | form (1) | `AuthorityRole.php` exists | medium | 106L; authority-management list; includes `authority_prototype.twig` (partial) |
| `Setting/System/system.twig` | data (0) | needs new (no system-info resource) | simple | 85L; PHP/server info display |
| `Setting/System/log.twig` | form (6) | needs new (no log-viewer resource) | medium | 96L; log file viewer with filter form |
| `Setting/System/login_history.twig` | form (9) | `LoginHistory.php` exists | medium | 194L; login-history list + search + pager |
| `Setting/System/masterdata.twig` | form (6) | needs onGet (no masterdata resource) | medium | 107L; mtb_* master-data editor |
| `Setting/System/security.twig` | form (9) | needs onGet (no security-config resource) | complex | 232L; admin-security / IP-restriction config |
| `Setting/System/two_factor_auth_edit.twig` | form (3) | needs onGet (no 2FA-config resource) | medium | 121L; 2FA settings editor |
| `Setting/System/authority_prototype.twig` | *(partial)* | n/a | — | fragment — see section 3 |

9 routable pages + 1 partial. Need-new/onGet: `system`, `log`, `masterdata`,
`security`, `two_factor_auth_edit` are the gaps (resource side will need attention).

### 2.7 Store (13 pages — 5 form / 8 data)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `Store/plugin.twig` | data (0) | `PluginList.php` exists | medium | 62L; installed-plugin list; includes `plugin_table.twig`, `plugin_table_official.twig`, `unregisterd_plugin_table.twig` (partials) |
| `Store/plugin_install.twig` | form (2) | `Plugin.php` exists | simple | 48L; plugin upload/install form |
| `Store/plugin_handler.twig` | data (0) | needs new | medium | 97L; plugin handler-priority screen |
| `Store/plugin_search.twig` | form (6) | needs new | medium | 111L; marketplace search; includes `plugin_search_panel.twig` (partial); pager |
| `Store/plugin_confirm.twig` | data (0) | needs new | medium | 261L; plugin install-confirm; includes `plugin_confirm_panel.twig` (partial) |
| `Store/plugin_confirm_uninstall.twig` | data (0) | needs new | simple | 61L; uninstall-confirm; includes `plugin_confirm_uninstall_panel.twig` (partial) |
| `Store/authentication_setting.twig` | form (2) | needs new | simple | 207L; EC-CUBE.co auth-key config |
| `Store/template.twig` | form (2) | `Template/TemplateList.php` exists | medium | 131L; template list + select/delete |
| `Store/template_add.twig` | form (4) | needs onGet (no `Template/Add`) | simple | 98L; template upload form |
| `Store/plugin_table.twig` | *(partial)* | n/a | — | fragment of plugin.twig — section 3 |
| `Store/plugin_table_official.twig` | *(partial)* | n/a | — | fragment — section 3 |
| `Store/unregisterd_plugin_table.twig` | *(partial)* | n/a | — | fragment — section 3 |
| `Store/plugin_detail_info.twig` / `plugin_detail_modal.twig` / `plugin_search_panel.twig` / `plugin_confirm_panel.twig` / `plugin_confirm_uninstall_panel.twig` | *(partials)* | n/a | — | fragments / modals — section 3 |

8 routable pages (`plugin`, `plugin_install`, `plugin_handler`, `plugin_search`,
`plugin_confirm`, `plugin_confirm_uninstall`, `authentication_setting`, `template`,
`template_add` → 9 pages) + 8 partials/panels. **Store has the most missing
resources (most plugin-marketplace pages have no BEAR resource yet)** — this wave
needs the most resource-side scaffolding and is best done after the others or by an
agent comfortable adding thin renderers.

### 2.8 top-level (7 pages — 4 form / 3 data)

| Template | Recipe (FW) | BEAR resource | Complexity | Notes |
|---|---|---|---|---|
| `index.twig` | data (0) | needs new (no Admin dashboard resource) | complex | 321L; admin dashboard — sales charts, recent orders, KPIs; many widgets |
| `login.twig` | form (3) | `Login.php` exists | simple | 51L; `extends @admin/login_frame.twig` |
| `change_password.twig` | form (4) | needs onGet (no ChangePassword resource) | simple | 102L; forced password-change screen |
| `two_factor_auth.twig` | form (2) | needs onGet | simple | 54L; 2FA challenge; `extends @admin/login_frame.twig` |
| `two_factor_auth_set.twig` | form (3) | needs onGet | medium | 78L; 2FA device-setup; `extends @admin/login_frame.twig` |
| `empty_page.twig` | data (0) | n/a — extension placeholder | simple | 4L; near-empty `extends default_frame` stub (plugin slot). Borderline — could be excluded; keep as trivial page |
| `info.twig` / `notice_debug_mode.twig` / `nav.twig` / `default_frame.twig` / `login_frame.twig` / `alert.twig` / `snippet.twig` / `pager.twig` / `search_items.twig` / `error.twig` | *(partials/layout)* | n/a | — | see section 3 |

The dashboard (`index.twig`) is the single most complex top-level page and needs a
brand-new resource. `login` already has `Login.php`.

---

## 3. Templates excluded — NOT standalone pages

These 22 files have no `{% extends '@admin/default_frame.twig' %}` (or are layout
frames / form themes / mail-context). They are not page work — listed so they are
not mistaken for missing pages. Several are still **prerequisite shared partials**
that a wave must port before its pages render (flagged below).

| File | Type | Used by | Action |
|---|---|---|---|
| `default_frame.twig` | layout frame | every admin page | **already covered** by `var/templates/admin-base.html.twig` (pilot) |
| `login_frame.twig` | layout frame | login / 2FA pages | small login-context frame — port once before top-level wave |
| `nav.twig` | admin sidebar nav partial | `default_frame` | shared nav — likely already folded into `admin-base.html.twig`; **verify** |
| `pager.twig` | pagination partial | all list pages | **shared dependency** — port FIRST, blocks every list page |
| `alert.twig` | flash-message partial | `default_frame` | likely in `admin-base.html.twig`; verify |
| `snippet.twig` | head/asset snippet partial | `default_frame` | layout glue |
| `search_items.twig` | saved-search-chips partial | list pages | shared list helper |
| `info.twig` | maintenance-notice banner (8L) | `default_frame` | tiny conditional banner |
| `notice_debug_mode.twig` | debug-mode banner (8L) | `default_frame` | tiny conditional banner |
| `error.twig` | standalone error page (`<!doctype>`, no extends) | error handler | not a CRUD page — port with error handling, low priority |
| `Form/bootstrap_4_layout.html.twig` | Symfony form theme | form rendering engine | **infra, not a page** — exclude entirely |
| `Form/bootstrap_4_horizontal_layout.html.twig` | Symfony form theme | form rendering engine | **infra, not a page** — exclude entirely |
| `Order/confirmationModal_js.twig` | JS-only include (298L) | `Order/index.twig` | inline `<script>` partial — port with Order index |
| `Order/order_item_type.twig` | order-item form fragment | `Order/edit.twig` | shared — port with Order edit |
| `Order/order_item_prototype.twig` | JS form-collection prototype | `Order/edit.twig`/`shipping.twig` | shared — port with Order edit |
| `Order/search_customer.twig` | customer-search modal body | `Order/edit.twig` | modal partial — port with Order edit |
| `Order/search_product.twig` | product-search modal body (204L) | `Order/edit.twig` | modal partial — port with Order edit |
| `Content/layout_block.twig` | layout-designer block fragment | `Content/layout.twig` | shared — port with Content layout |
| `Product/product_class_popup.twig` | product-class popup body | `Product/product_class.twig` | popup — port with Product (borderline; see 2.1) |
| `Setting/Shop/delivery_time_prototype.twig` | delivery-time prototype | `Setting/Shop/delivery_edit.twig` | shared — port with delivery_edit |
| `Setting/Shop/mail_view.twig` | mail-preview iframe body | `Setting/Shop/mail.twig` | preview partial — port with mail |
| `Setting/System/authority_prototype.twig` | authority-row prototype | `Setting/System/authority.twig` | shared — port with authority |
| `Store/plugin_table.twig`, `plugin_table_official.twig`, `unregisterd_plugin_table.twig`, `plugin_detail_info.twig`, `plugin_detail_modal.twig`, `plugin_search_panel.twig`, `plugin_confirm_panel.twig`, `plugin_confirm_uninstall_panel.twig` | plugin-list / panel / modal fragments | `Store/plugin*.twig` pages | shared within Store wave — port alongside their parent pages |

**Critical shared dependency:** `pager.twig` is included by every list page across
every section (Product/Order/Customer/Content list pages all `{% include
"@admin/pager.twig" %}`). It must be ported (or confirmed already ported) **before
any list-page wave starts**. Same for the `default_frame` / `nav` / `alert` glue —
the pilot's `admin-base.html.twig` should already cover the frame, but `pager.twig`
is a standalone include and must be verified present.

---

## 4. Already done — News pilot

The Phase 3 admin pilot (commit `f91e10f`) already ported the Content/News pages:

| EC-CUBE template | Ported file |
|---|---|
| `admin/Content/news.twig` | `var/templates/Page/Admin/News/NewsList.html.twig` |
| `admin/Content/news_edit.twig` | `var/templates/Page/Admin/News/News.html.twig` |

Plus `var/templates/admin-base.html.twig` (the admin layout frame, ported from
`default_frame.twig`).

**In flight (concurrent agents, not yet committed at audit time):**
`var/templates/Page/Admin/Customer.html.twig` and `CustomerList.html.twig` — the
Customer `edit` + `index` pages. The Customer wave should treat those two as done
and only own `Customer/delivery_edit.twig`.

So the Content wave is partial (11 of 13 remain) and the Customer wave is partial
(1 of 3 remains, pending the concurrent commit).

---

## 5. Recommended wave grouping

8 sections → **7 recommended parallel waves** (Setting/Shop and Setting/System are
sized as separate waves; Customer is folded into a small "remainder" wave because two
of its three pages are already in flight). Each wave = one section, parallel-safe
because no two sections share an editable file (the only cross-wave artifact is the
shared `pager.twig`, handled in Wave 0).

### Wave 0 — shared prerequisites (do FIRST, blocks list pages)

- Port / verify `pager.twig` (pagination partial — included by every list page).
- Verify `nav.twig` / `alert.twig` / `info.twig` / `notice_debug_mode.twig` are
  covered by the existing `admin-base.html.twig`; port any gap.
- Port `login_frame.twig` (needed by top-level Wave).
- Effort: **small** (~0.5 day). Single agent, must complete before Waves 1, 4, 6.

### Wave 1 — Product (11 pages)

- Pages: `index`, `product`, `product_class`, `category`, `class_name`,
  `class_category`, `tag`, `csv_product`, `csv_category`, `csv_class_name`,
  `csv_class_category` (+ `product_class_popup` fragment).
- Effort: **large** — `product.twig` (932L) + `product_class.twig` (448L) +
  `index.twig` (520L) dominate; the 4 `csv_*` pages are clone-from-one.
- Cross-wave dep: `pager.twig` (Wave 0). All 11 resources exist; `product_class`
  may need an `onGet` HTML branch — flag for the agent.

### Wave 2 — Order (9 work items: 7 pages + 4 partials, but partials port with pages)

- Pages: `index`, `edit`, `shipping`, `order_pdf`, `csv_shipping`, `mail`,
  `mail_confirm`. Partials ported alongside: `confirmationModal_js`,
  `order_item_type`, `order_item_prototype`, `search_customer`, `search_product`.
- Effort: **very large** — the heaviest wave. `edit.twig` 1057L, `shipping.twig`
  709L, `index.twig` 678L. Consider splitting into 2a (`edit` + `shipping` +
  partials) and 2b (`index` + `order_pdf` + `csv_shipping` + `mail` + `mail_confirm`)
  if a single agent's budget is tight.
- New resource needed: `mail_confirm` (1).

### Wave 3 — Content (11 remaining pages — News pilot done)

- Pages: `page`, `page_edit`, `block`, `block_edit`, `layout`, `layout_list`,
  `css`, `js`, `file`, `cache`, `maintenance` (+ `layout_block` fragment).
- Effort: **large** — `layout.twig` (558L drag-drop designer) + `file.twig` (320L
  file manager) are the spikes. `css`/`js` are clone-from-one.
- New resources needed: `file`, `cache`, `maintenance` (3); `css`/`js` need onGet.

### Wave 4 — Setting/Shop (11 pages)

- Pages: `shop_master`, `payment`, `payment_edit`, `delivery`, `delivery_edit`,
  `tax_rule`, `mail`, `order_status`, `csv`, `calendar`, `tradelaw` (+
  `delivery_time_prototype`, `mail_view` fragments).
- Effort: **large** — `shop_master` (388L), `delivery_edit` (406L), `mail` (301L).
- New resource needed: `calendar` (1). Cross-wave dep: `pager.twig` (Wave 0) only if
  any list page paginates — `payment`/`delivery` are sortable lists, low pager use.

### Wave 5 — Setting/System (9 pages)

- Pages: `member`, `member_edit`, `authority`, `system`, `log`, `login_history`,
  `masterdata`, `security`, `two_factor_auth_edit` (+ `authority_prototype`
  fragment).
- Effort: **medium** — `security` (232L) is the spike; rest are small/medium.
- Resource gaps: `system`, `log` need new; `masterdata`, `security`,
  `two_factor_auth_edit` need onGet (5 items) — resource-heavy wave.

### Wave 6 — Store (9 pages)

- Pages: `plugin`, `plugin_install`, `plugin_handler`, `plugin_search`,
  `plugin_confirm`, `plugin_confirm_uninstall`, `authentication_setting`,
  `template`, `template_add` (+ 8 plugin panel/modal/table fragments).
- Effort: **medium** on the HTML side but **resource-heavy**: 9 of these have no
  BEAR resource (`plugin_handler`, `plugin_search`, `plugin_confirm`,
  `plugin_confirm_uninstall`, `authentication_setting` need new; `template_add`
  needs onGet). Best assigned to an agent comfortable scaffolding thin renderers.
- Cross-wave dep: `pager.twig` (Wave 0) for `plugin_search`.

### Wave 7 — top-level (6 pages)

- Pages: `index` (dashboard), `login`, `change_password`, `two_factor_auth`,
  `two_factor_auth_set`, `empty_page`.
- Effort: **medium** — `index.twig` (321L dashboard with charts/widgets) is the
  spike and needs a new Admin-dashboard resource; the rest are small login-context
  pages.
- Cross-wave dep: `login_frame.twig` (Wave 0).

### Wave summary

| Wave | Section | Pages | Effort | Key risk |
|---|---|--:|---|---|
| 0 | shared prereqs | — | small | blocks list waves; do first |
| 1 | Product | 11 | large | product.twig 932L |
| 2 | Order | 7 | very large | edit.twig 1057L — split if needed |
| 3 | Content | 11 | large | layout.twig designer, file manager |
| 4 | Setting/Shop | 11 | large | 3 templates >300L |
| 5 | Setting/System | 9 | medium | 5 resource gaps |
| 6 | Store | 9 | medium | 9 missing resources (resource-heavy) |
| 7 | top-level | 6 | medium | dashboard needs new resource |
| — | Customer | 1 (+2 in flight) | small | folded into a remainder pass |

Total page templates: **77** (Customer's 3 included; 2 in flight). Recommended
**7 parallel waves** + Wave 0 prereq + a small Customer remainder pass.
