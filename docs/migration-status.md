# Migration Status — EC-CUBE 4.3 → BEAR.Sunday + Be Framework

> **Living document.** Update the relevant row/cell whenever a layer's status changes.
> Audited 2026-05-21 against `alps.json`, `be/src`, `src/Resource`, `var/templates`, and git history.

The migration runs through **5 layers**. ALPS is the source of truth; the lower
layers implement it. "Done / partial / pending" is judged against the ALPS spec,
not optimistically.

---

## 1. Summary

| Layer | Done | State |
|---|---|---|
| 1. ALPS spec (`alps.json`) | 144/144 transitions, 444 descriptors | Complete. 144 transitions (135 flow-tagged + 9 untagged), 276+ data descriptors. Post-Phase-A remediation added 5 transitions. |
| 2. Be domain (`be/src`) | 143/144 transitions | Phase A migrated 139 transitions; Phase-3 remediation added 4 (`doSortNoMove` / `doToggleVisible` / `doUpdateTrackingNumber` / `doSendShippingNotifyMail`). 1 ALPS-only transition (`doResendActivationMail`) still has **no domain impl**; 7 Phase-A transitions are functional **stubs**. 128 Final / 127 Input / 137 Semantic / 14 Being / 38 Entity. |
| 3. BEAR Resource | 126 Page resources | All Phase-A transitions exposed as resources (`src/Resource/Page/**`): 80 admin + 46 storefront. Phase 3 admin-HTML waves added ~20 page resources (dashboard, login, change-password, 2FA, CMS file/css/js/cache, system/log/security/masterdata, customer-delivery-edit, calendar, …); the Setting/Shop Tier-2 wave also added GET renderers (`onGet`) to the action-only csv-config/mail-template/order-status/trade-law resources, and the Setting/Shop edit-page wave added `onGet` editors to the action-only payment/delivery resources plus a shop-master form to base-info. |
| 4. SQL persistence (`be/src/Reason/Query/Sql*`) | 34/34 storage interfaces | Phase 2 complete. Every Fake storage has a SQL twin; prod context binds SQL Reasons; reproducible prod DB seed script exists. |
| 5. HTML presentation (`var/templates`) | ~90/~118 page templates | Phase 3 in progress. Storefront done (41 templates, all pages). Admin **Tier-1 done** — 34 of 77 admin page templates ported across 8 section-waves (list/data pages + simple CRUD whose BEAR resource already serves GET). The **flow-manage-system Tier-2 wave** then ported 6 more (system/log/security/masterdata/authority/2FA-edit) with new resources + forms, the **Customer delivery-edit Tier-2 page** completed the Customer section, the **Setting/Shop Tier-2 wave** ported 5 more (calendar/csv/mail-template/order-status/trade-law), and the **Setting/Shop edit-page wave** ported 3 more (payment-edit/delivery-edit/shop-master) — completing the Setting/Shop section. Admin **Tier-2 backlog** (~28 templates — multi-panel editors + pages needing new resources) open. Enrichment backlog open. |

**Test count:** `vendor/bin/phpunit` → **1796 tests, 5889 assertions, OK** (3 deprecations from `aura/html` form rendering; "issues" = deprecations only — no failures). The 1356 non-SQL tests (`--testsuite bemart,bemart-be`) run without a database; the `bemart-sql` suite needs a local MariaDB.

---

## 2. Feature matrix

Rows = `flow-*` feature areas (see `docs/tag.md` for the tag taxonomy). Columns = the 5 layers.
Counts are ALPS transitions per flow. `✓` done · `~` partial · `✗` pending.

| Feature area (flow) | ALPS | Be domain | BEAR Resource (JSON) | SQL | HTML |
|---|---|---|---|---|---|
| flow-browse (catalog browse) | ✓ 5 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-purchase (cart→checkout) | ✓ 17 | ~ (`doCreateOrder` stub) | ✓ | ✓ | ✓ storefront |
| flow-register (customer signup) | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-account (mypage / address) | ✓ 14 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-favorite | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-inquiry (contact form) | ✓ 2 | ✓ | ✓ | ✓ (`Contact` has no table) | ✓ storefront |
| flow-admin-auth | ✓ 1 | ✓ | ✓ | ✓ | ✓ admin |
| flow-manage-product | ✓ 24 | ~ (CSV import stubs; `doSortNoMove`/`doToggleVisible` ALPS-only) | ✓ | ✓ | ~ admin (list/tag/class done; product/category editors Tier-2) |
| flow-manage-order | ✓ 13 | ~ (PDF export, shipping-CSV stubs; tracking/notify ALPS-only) | ✓ | ✓ | ~ admin (order list done; edit/shipping/mail Tier-2) |
| flow-manage-customer | ✓ 6 | ~ (`doResendActivationMail` ALPS-only) | ✓ | ✓ | ✓ admin (list/edit/delivery-edit done) |
| flow-manage-shop | ✓ 15 | ✓ | ✓ | ✓ | ✓ admin (payment/delivery/tax list + calendar/csv/order-status/tradelaw + payment/delivery edits + shop-master editors done) |
| flow-manage-content | ✓ 9 | ✓ | ✓ | ✓ | ~ admin (news/page/file/css/js/cache done) |
| flow-manage-cms (layout/block) | ✓ 8 | ✓ | ~ (Template list only) | ✓ | ~ admin (layout/block/template list done) |
| flow-manage-system | ✓ 8 | ✓ | ✓ | ✓ | ~ admin (member/login-history + system/log/security/masterdata/authority/2FA-edit done) |
| flow-manage-mail | ✓ 2 | ✓ | ✓ | ✓ | ✓ admin (mail-template editor done) |
| flow-manage-plugin | ✓ 6 | ~ (`doInstallPlugin` stub) | ✓ | ✓ | ~ admin (plugin list done; install/search Tier-2) |
| (untagged transitions) | ✓ 9 | ✓ | n/a | n/a | n/a |

Layer-specific notes:

- **SQL: 34/34** — every storage interface listed in `be/src/Reason/Query/` has a `Sql*` implementation.
- **HTML: storefront ✓ / admin Tier-1 ✓ / admin Tier-2 ~** — storefront has 41 page `.html.twig` files + `base.html.twig`; all EC-CUBE storefront pages ported. Admin has 49 page `.html.twig` files + the `admin-base.html.twig` / `admin-login-base.html.twig` frames; 49 of 77 EC-CUBE admin page templates ported (Tier-1 = 34 across 8 section-waves + the flow-manage-system Tier-2 wave = 6: system/log/security/masterdata/authority/2FA-edit + the Customer delivery-edit Tier-2 page = 1 + the Setting/Shop Tier-2 wave = 5: calendar/csv/mail-template/order-status/trade-law + the Setting/Shop edit-page wave = 3: payment-edit/delivery-edit/shop-master). The remaining ~28 Tier-2 templates (multi-panel editors + action-only-resource pages) are not ported. No `Block/*` widget templates exist yet.
- **flow-manage-cms Resource** — only `Admin/Template/TemplateList.php` exists for the CMS template feature; layout/block resources are present but the CMS template-management surface is partial — *unverified* in full.

---

## 3. Phase log

| Phase | Scope | Key commits |
|---|---|---|
| **Phase A** | Be domain + BEAR JSON resources. Pilots 1–5 established the 8 Be patterns, then two parallel waves took transition coverage 45 → **139/139** (HANDOVER's count, pre-remediation). 7 transitions left as functional stubs (deferred to Phase 2). Phase B added Psalm taint setup, ProdModule, env-gated entry point. | Recorded in `docs/HANDOVER.md` (Last updated 2026-05-18) |
| **Phase 2 — SQL** | Fake → SQL for all 34 storage interfaces. Each migration follows the G-23 hypermedia-test-as-contract workflow. <br>**2a:** SQL smoke + framework (`SqlCustomerQuery`, Cart family, goCustomer end-to-end). <br>**2b:** the bulk — ~28 storages migrated, each with a Phase A (厳密移植 field alignment) + Phase B (Sql* + hypermedia) pair. <br>**2c:** production cutover — `SqlModule` binds SQL Reasons under prod; reproducible prod DB seed (`mtb_*` masters + setup script). | `3a439a2`, `0757f26`, `051d235`, `fd96242` (2a); `f6f22ee`…`9a9c89b` (2b); `f128ba6`, `6ed334d` (2c) |
| **Phase 3 — HTML** | BEAR resources rendered as HTML; templates are faithful ports of EC-CUBE's `default`-theme Twig (see `var/templates/README.md`). Storefront done in **7 waves** (~40 pages). `Ray.WebFormModule` adopted for form pages (Login pilot). Enrichment pilot underway: re-derive thin resource bodies from EC-CUBE so HTML can be faithful (Cart done; Mypage History done). Admin theme then ported — News pilot established the admin recipe (`admin-base.html.twig` + `EcCubeAdminStubLoader` + per-section ja-message split), then 8 section-waves (parallel agents) took admin **Tier-1** to 34 of 77 pages. | `762a739`/`2525710`/`9d06ec3` (Cart pilot); `1507dc2` (wave 1) → `46b2a08` (wave 7); `5a95435` (WebFormModule); `a44f296`/`9d06ec3` (Cart enrichment); `a31f8d8`/`3c1b03d` (Mypage History enrichment); `f91e10f` (admin News pilot); `1e91e92` (per-section ja-split); `da48413` (Customer); `64e5e03`/`f65279b`/`855c412`/`dff64ca` (section-waves batch 1); `3b3b42f`/`9261b8e`/`1b122af`/`3acfc6a`/`974b233`/`5a112a1` (batch 2) |

ALPS remediation (`f01e1ae`, per `docs/phases/alps-audit-phase3.md`) happened during Phase 3:
it re-tagged Favorite and **added 5 transitions** that Phase A's domain never saw.

---

## 4. Outstanding work

Punch-list, roughly highest-effort first:

1. **Admin HTML Tier-2 — ~28 templates (biggest remaining chunk).** Admin Tier-1 (34 of 77 page templates — list/data pages + simple CRUD whose BEAR resource already serves GET) is done; the **flow-manage-system Tier-2 wave** then ported 6 more (system/log/security/masterdata/authority/2FA-edit — new resources + forms + render tests), the **Customer delivery-edit page** added 1, the **Setting/Shop Tier-2 wave** added 5 (calendar/csv/mail-template/order-status/trade-law), and the **Setting/Shop edit-page wave** added 3 (payment-edit/delivery-edit/shop-master) — completing the Setting/Shop section. Tier-2 is the rest: multi-panel editors (`Order/edit` ~1057L, `Product/product` ~932L, `Product/product_class` ~448L, `Order/shipping` ~709L) and pages whose BEAR resource is **action-only** (POST/CSV/PDF) with no GET-serving `onGet`. Porting Tier-2 needs new resources / `onGet` additions / `be/src` domain body-shape work — a resource-creation effort, not another template-port wave. Per-section deferred lists: `docs/phases/admin-fanout-plan.md` and `var/templates/README.md` "Fan-out status".
2. **HTML enrichment backlog.** Phase 3 flagged data pages whose resource bodies are too thin for a faithful EC-CUBE port; each needs the Cart-style re-derive (ALPS → Entity/SQL/Fake enrich → template wiring). Flagged: **Mypage History** (done — `a31f8d8`/`3c1b03d`), **Shopping confirm/complete**, **Mypage dashboard**, **Favorite**, **Address**, **Contact**.
3. **`Block/*` → widget sub-step.** No `Block/*` templates exist. Block regions (header/footer/cart/login/search) are EC-CUBE-runtime residuals today; they need a widget rendering sub-step. Block is intentionally not modelled in ALPS.
4. **1 ALPS-only transition — no domain impl.** Of the 5 transitions the Phase-3 ALPS remediation added, 4 are now implemented in `be/src` (`doSortNoMove`, `doToggleVisible`, `doUpdateTrackingNumber`, `doSendShippingNotifyMail` — domain + storage + JSON resource + tests). `doResendActivationMail` is still pending. (This is why domain coverage is **143 of 144**.)
5. **7 Phase-A stub transitions.** Functional but stubbed in Phase A, deferred to Phase 2, still open (see PR #2 / HANDOVER Wave 8-9): `doImportProductCsv`, `doImportCategoryCsv`, `doImportShippingCsv`, `doInstallPlugin`, `goExportOrderPdf`, `doCreateOrder`, `doUpdateCsv`.
6. **`#[Input]`-vs-Form refactor.** Noted from MyVendor.Cms issue #37 — reconcile Be Framework `#[Input]` with the `Ray.WebFormModule` `AbstractForm`. *Unverified here* — confirm against that issue before acting.
7. **Production DB bring-up.** Phase 2c shipped the seed script and prod `SqlModule` binding; an actual production database bring-up / cutover is still pending.

---

## 5. Where things live

| You want… | Look at |
|---|---|
| The feature list (source of truth) | `alps.json` · `docs/alps.json.html` · `docs/alps.svg` |
| Tag taxonomy (`flow-*`, `src-*`) | `docs/tag.md` |
| Phase A detail (Be domain + JSON) | `docs/HANDOVER.md` |
| Phase 2 detail (SQL) | `sql/diff/entity-vs-eccube.md` · PR #2 |
| Phase 3 detail (HTML) | `docs/phases/alps-audit-phase3.md` · `var/templates/README.md` |
| Migration skills / lessons (G-14…G-23) | `docs/skills/` |
| Docs map / index | `docs/README.md` |
| Continuation guide | `docs/HOW_TO_CONTINUE.md` |
| Stale older trackers (Phase A era, do not trust for current state) | `docs/archive/progress.md` · `docs/archive/task_plan.md` |
