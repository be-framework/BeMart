# Migration Status — EC-CUBE 4.3 → BEAR.Sunday + Be Framework

> **Living document.** Update the relevant row/cell whenever a layer's status changes.
> Audited 2026-05-22 against `alps.json`, `be/src`, `src/Resource`, `var/templates`, and git history.

The migration runs through **5 layers**. ALPS is the source of truth; the lower
layers implement it. "Done / partial / pending" is judged against the ALPS spec,
not optimistically.

---

## 1. Summary

| Layer | Done | State |
|---|---|---|
| 1. ALPS spec (`alps.json`) | 144/144 transitions, 444 descriptors | Complete. 144 transitions (135 flow-tagged + 9 untagged), 276+ data descriptors. Post-Phase-A remediation added 5 transitions. |
| 2. Be domain (`be/src`) | 144/144 transitions | Complete. Phase A migrated 139 transitions; Phase-3 remediation implemented all 5 added transitions (`doSortNoMove` / `doToggleVisible` / `doUpdateTrackingNumber` / `doSendShippingNotifyMail` / `doResendActivationMail`). 7 Phase-A transitions remain functional **stubs**. 129 Final / 128 Input / 137 Semantic / 14 Being / 38 Entity. |
| 3. BEAR Resource | 139 Page resources | Every transition exposed as a resource (`src/Resource/Page/**`): 93 admin + 46 storefront. Phase 3 admin-HTML waves added page resources + GET renderers (`onGet`) to formerly action-only resources; the HTTP router (Phase B) maps every EC-CUBE route name ↔ URL path ↔ resource URI. |
| 4. SQL persistence (`be/src/Reason/Query/Sql*`) | 34/34 storage interfaces | Phase 2 complete. Every Fake storage has a SQL twin; prod context binds SQL Reasons; reproducible prod DB seed script exists. |
| 5. HTML presentation (`var/templates`) | 110 page templates | Phase 3 complete for the in-scope migration. **Storefront done** — 41 page templates (all storefront pages) + 2 Block widgets (logo/footer) + the `base.html.twig` frame. **Admin 63 of 77 page templates ported** — Tier-1 (34, 8 section-waves) + every Tier-2 editor: Order (edit/shipping/mail/mail_confirm/order_pdf/csv_shipping), Product (product/product_class/category/csv×4), Setting/System & Setting/Shop Tier-2, Customer delivery-edit, Store template_add. The ~14 unported admin pages are the **Store/Plugin install/search subtree — out of scope** (plug-ins are excluded from this migration). |

**Test count:** `vendor/bin/phpunit` → **1893 tests, 4002 assertions**. The non-SQL suites (`--testsuite bemart,bemart-be`) all pass without a database. The `bemart-sql` suite needs a local MariaDB — without one, 745 tests skip and 3 prod-DB-context tests fail (documented, MariaDB-dependent: `ProdModuleTest::testProdContextDoesNotWriteLogFileOnBecoming`, `AppEntryPointTest::testProdContextDoesNotWriteLogFile` / `testProdContextRejectsAnonymousCli`).

---

## 2. Feature matrix

Rows = `flow-*` feature areas (see `docs/tag.md` for the tag taxonomy). Columns = the 5 layers.
Counts are ALPS transitions per flow. `✓` done · `~` partial · `✗` pending.

| Feature area (flow) | ALPS | Be domain | BEAR Resource (JSON) | SQL | HTML |
|---|---|---|---|---|---|
| flow-browse (catalog browse) | ✓ 5 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-purchase (cart→checkout) | ✓ 17 | ✓ (`doCreateOrder` + `doCheckout` both: PurchaseFlow + `dtb_order_item` snapshot) | ✓ | ✓ | ✓ storefront |
| flow-register (customer signup) | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-account (mypage / address) | ✓ 14 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-favorite | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-inquiry (contact form) | ✓ 2 | ✓ | ✓ | ✓ (`Contact` has no table) | ✓ storefront |
| flow-admin-auth | ✓ 1 | ✓ | ✓ | ✓ | ✓ admin |
| flow-manage-product | ✓ 24 | ~ (CSV import stubs) | ✓ | ✓ | ✓ admin (list/tag/class + product/product_class/category/csv editors done) |
| flow-manage-order | ✓ 13 | ~ (PDF export, shipping-CSV stubs) | ✓ | ✓ | ✓ admin (list + edit/shipping/mail/mail_confirm/pdf/csv-shipping done) |
| flow-manage-customer | ✓ 6 | ✓ | ✓ | ✓ | ✓ admin (list/edit/delivery-edit done) |
| flow-manage-shop | ✓ 15 | ✓ | ✓ | ✓ | ✓ admin (payment/delivery/tax list + calendar/csv/order-status/tradelaw + payment/delivery edits + shop-master editors done) |
| flow-manage-content | ✓ 9 | ✓ | ✓ | ✓ | ✓ admin (news/page/file/css/js/cache/maintenance done) |
| flow-manage-cms (layout/block) | ✓ 8 | ✓ | ~ (Template list/add only) | ✓ | ✓ admin (layout/block/template list + template_add done) |
| flow-manage-system | ✓ 8 | ✓ | ✓ | ✓ | ✓ admin (member/login-history + system/log/security/masterdata/authority/2FA-edit done) |
| flow-manage-mail | ✓ 2 | ✓ | ✓ | ✓ | ✓ admin (mail-template editor done) |
| flow-manage-plugin *(out of scope)* | ✓ 6 | ~ (`doInstallPlugin` stub) | ✓ | ✓ | ~ admin (plugin list done; install/search out of scope) |
| (untagged transitions) | ✓ 9 | ✓ | n/a | n/a | n/a |

Layer-specific notes:

- **SQL: 34/34** — every storage interface listed in `be/src/Reason/Query/` has a `Sql*` implementation.
- **HTML: storefront ✓ / admin ✓ (in scope)** — `var/templates` holds **110 page `.html.twig` files**: 41 storefront page templates + 2 `Block/` widgets (logo/footer) + 64 admin page templates + the 3 frames (`base.html.twig`, `admin-base.html.twig`, `admin-login-base.html.twig`). All EC-CUBE storefront pages are ported. **63 of 77 admin page templates ported** — Tier-1 (34, across 8 section-waves) + every Tier-2 editor wave (flow-manage-system 6, Customer delivery-edit 1, Setting/Shop Tier-2 5 + edit-page 3, Order Tier-2 6, Product Tier-2 7, Store template_add 1). The remaining ~14 unported admin pages are the **Store/Plugin install/search subtree** — out of scope (plug-ins excluded). The render-diff fidelity tests (`tests/Resource/*HtmlRenderTest.php`) activate only when the gitignored `tools/ec-cube-source/` 4.3 clone is present.
- **flow-manage-cms Resource** — `Admin/Template/TemplateList.php` + `TemplateAdd` exist for the CMS template feature; layout/block resources are present but the CMS template-management surface is partial — *unverified* in full.

---

## 3. Phase log

| Phase | Scope | Key commits |
|---|---|---|
| **Phase A** | Be domain + BEAR JSON resources. Pilots 1–5 established the 8 Be patterns, then two parallel waves took transition coverage 45 → **139/139** (HANDOVER's count, pre-remediation). 7 transitions left as functional stubs (deferred to Phase 2). Phase B added Psalm taint setup, ProdModule, env-gated entry point. | Recorded in `docs/HANDOVER.md` (Last updated 2026-05-18) |
| **Phase 2 — SQL** | Fake → SQL for all 34 storage interfaces. Each migration follows the G-23 hypermedia-test-as-contract workflow. <br>**2a:** SQL smoke + framework (`SqlCustomerQuery`, Cart family, goCustomer end-to-end). <br>**2b:** the bulk — ~28 storages migrated, each with a Phase A (厳密移植 field alignment) + Phase B (Sql* + hypermedia) pair. <br>**2c:** production cutover — `SqlModule` binds SQL Reasons under prod; reproducible prod DB seed (`mtb_*` masters + setup script). | `3a439a2`, `0757f26`, `051d235`, `fd96242` (2a); `f6f22ee`…`9a9c89b` (2b); `f128ba6`, `6ed334d` (2c) |
| **Phase 3 — HTML** | BEAR resources rendered as HTML; templates are faithful ports of EC-CUBE's `default`-theme Twig (see `var/templates/README.md`). Storefront done in **7 waves** (~40 pages) + 2 Block widgets. `Ray.WebFormModule` adopted for form pages (Login pilot). Enrichment: re-derive thin resource bodies from EC-CUBE so HTML can be faithful (Cart, Mypage History, Shopping confirm/complete). Admin theme then ported — News pilot established the admin recipe (`admin-base.html.twig` + `EcCubeAdminStubLoader` + per-section ja-message split), then 8 section-waves took admin **Tier-1** to 34 pages, and the **Tier-2 editor waves** (system / Customer / Setting/Shop / Order / Product / Store) took admin to **63 of 77 pages** — every in-scope admin page (only the out-of-scope Store/Plugin install/search subtree remains). | `762a739`/`2525710`/`9d06ec3` (Cart pilot); `1507dc2` (wave 1) → `46b2a08` (wave 7); `5a95435` (WebFormModule); `f91e10f` (admin News pilot); `1e91e92` (per-section ja-split); `da48413` (Customer); section-waves batches 1–2; `2f59bb3`…`a455281` (Order Tier-2); `4eb93f3`…`0296306` (Product Tier-2); `571dd5b` (Store template_add); `f3df0d4` (Block widgets); `1177e0d`/`2f8d17a` (Shopping enrich); `5d9e6ba` (fidelity-test fixes); `f9c5580` (`doResendActivationMail`) |
| **Phase B — security / production hardening** | Psalm taint setup, `ProdModule`, env-gated CLI entry point, EC-CUBE static-asset deployment (`default` + `admin` themes), and the HTTP router — Aura.Router maps EC-CUBE route name ↔ URL path ↔ resource URI through route extras; the BEAR.Sunday `RouterInterface` adapter returns `RouterMatch`, while BEAR\Resource owns missing resource 404 and method 405; `BeMartTwigExtension::url()/path()` resolve through Aura's generator. | `a002097` (asset deploy); `53e587e`/`39f1117` (HTTP router); `16e8c9d` (asset-package-aware render stubs) |

ALPS remediation (`f01e1ae`, per `docs/phases/alps-audit-phase3.md`) happened during Phase 3:
it re-tagged Favorite and **added 5 transitions** that Phase A's domain never saw. All 5 are
now implemented in `be/src` — Be-domain coverage is **144/144** (`f9c5580`).

---

## 4. Outstanding work

Punch-list, roughly highest-effort first:

1. **Admin HTML Tier-2 — done (in scope).** ✓ Done. Admin Tier-1 (34 page templates) plus every Tier-2 editor wave is ported: flow-manage-system (6: system/log/security/masterdata/authority/2FA-edit), Customer delivery-edit (1), Setting/Shop Tier-2 (5) + edit-page (3), Order Tier-2 (6: edit/shipping/mail/mail_confirm/order_pdf/csv_shipping), Product Tier-2 (7: product/product_class/category/csv×4), Store template_add (1) — **63 of 77 admin page templates.** The ~14 unported pages are the **Store/Plugin install/search subtree, which is out of scope** (plug-ins excluded from this migration). Per-section history: `docs/phases/admin-fanout-plan.md` and `var/templates/README.md` "Fan-out status".
2. **HTML enrichment backlog.** Phase 3 flagged data pages whose resource bodies are too thin for a faithful EC-CUBE port; each needs the Cart-style re-derive (ALPS → Entity/SQL/Fake enrich → template wiring). Done: **Mypage History** (`a31f8d8`/`3c1b03d`), **Shopping confirm/complete** (`1177e0d`/`2f8d17a`). Still open: **Mypage dashboard**, **Favorite**, **Address**, **Contact**.
3. **`Block/*` widget templates — done.** ✓ Done. The `logo` and `footer` Block widgets are ported (`var/templates/Block/`, `f3df0d4`). The remaining Block regions (cart/login/search) stay EC-CUBE-runtime residuals; Block is intentionally not modelled in ALPS.
4. **Phase-3 remediation transitions — all implemented.** ✓ Done. All 5 transitions the Phase-3 ALPS remediation added are now implemented in `be/src` (`doSortNoMove`, `doToggleVisible`, `doUpdateTrackingNumber`, `doSendShippingNotifyMail`, `doResendActivationMail` — domain + storage/mailer + JSON resource + tests). Domain coverage is **144 of 144**.
5. **Phase-A stub / compatibility residuals.** `goExportOrderPdf` is now the Issue #24 compatibility pilot: Resource reachability, download headers, and `%PDF-` body generation are implemented through an isolated EC-CUBE/TCPDF service, but full EC-CUBE fidelity (delivery-note layout parity, `dtb_order_pdf` saved settings, multi-shipping template reproduction) is intentionally left as a tracked residual. **`doImportCategoryCsv` and `doImportShippingCsv` are now real** — `CategoryCsvImported` parses the 4-column EC-CUBE format and upserts/deletes via `CategoryStorageInterface` (+ `CategoryIdQueryInterface` for new ids); `AdminShippingCsvImported` resolves each row's order and writes the tracking number through the same `ShippingAddressStorageInterface::updateTrackingNumber` surface as the inline `doUpdateTrackingNumber` (durable persistence covered by the SQL suite; Fake writes are no-ops). **`doUpdateCsv` is now consumed end-to-end** — the four export Finals (`AdminOrderCsvExported` 1 / `AdminCustomerCsvExported` 2 / `AdminProductCsvExported` 3 / `AdminShippingCsvExported` 4) overlay the saved `dtb_csv` configuration on their default column vector via `CsvColumnLayout::resolve`: with no saved config every default column is emitted (Wave 9 behaviour), with a config only the `enabled` columns are emitted in `sortNo` order, restricted to columns the Final can encode (unknown columns dropped; a config that enables nothing known falls back to the full vector so an export is never empty). **`doCreateOrder` is now a full PurchaseFlow + snapshot write** — `AdminOrderCreated` projects the admin-posted line items into the cart shape, runs the shared `PurchaseFlowInterface` (the same recompute the storefront checkout uses) to derive subtotal / tax / base total / addPoint, applies the admin's charge & discount on top of that base total, registers the `dtb_order` row, then persists the `dtb_order_item` snapshot through the new `OrderItemCommandInterface::register` (resolves the parent by orderNo, fans the vector out via `JSON_TABLE`; durable persistence covered by `SqlOrderItemCommandTest`, Fake writes no-op). The **storefront checkout now freezes the same snapshot**: `order_by_pre_order_id` was enriched to aggregate the pre-order's cart lines (joined `dtb_cart` → `dtb_cart_item` → `dtb_product_class` → `dtb_product`, keyed by `pre_order_id`) into the `OrderEntity`'s items, so `FakePurchaseFlow`/SQL compute a real subtotal and `CheckoutCompleted` persists the `dtb_order_item` snapshot through the same `OrderItemCommandInterface::register` (display name resolved from `ProductClassQueryInterface`, exactly as `OrderConfirmed` composes the confirm screen). Both finalized-order paths — admin `doCreateOrder` and storefront `doCheckout` — now converge on one snapshot write surface (domain wiring pinned by `AdminOrderCreatedTest` / `CheckoutCompletedSnapshotTest`; durable round-trip by `SqlOrderItemCommandTest` / `SqlOrderQueryTest` / `CheckoutResourceSqlTest`). The `alps.json` contract was synced to match: `doCreateOrder` now declares its line-item input vocabulary (`OrderItemInput`) and both transitions' docs record the snapshot-freeze (HTML/SVG + `docs/` copies regenerated). Still stubbed / intentionally-not-modelled: `doImportProductCsv` (intentionally not migrated — the route is export-only), `doInstallPlugin` (plugin scope, out of scope).
6. **`#[Input]`-vs-Form refactor.** Noted from MyVendor.Cms issue #37 — reconcile Be Framework `#[Input]` with the `Ray.WebFormModule` `AbstractForm`. *Unverified here* — confirm against that issue before acting.
7. **Production DB bring-up.** Phase 2c shipped the seed script and prod `SqlModule` binding; an actual production database bring-up / cutover is still pending.
8. **Hard ActionRedirect routes — connected.** ✓ Done. The 22 Hard rows that Issue #24 parked on `ActionRedirect` (`docs/eccube-feature-alps-status.html`) are now wired to concrete Be/BEAR resources with the Be domain transition implemented: 認証/credential (doChangePassword / doVerifyTwoFactorAuth / doSetTwoFactorAuth / doUpdateSecurity), コンテンツ/file (doClearCache / doUpdateContentCss / doUpdateContentJs / doToggleMaintenance), マスタデータ (doSelectMasterData / doUpdateMasterData), 規格CSV (goExportClassName / goExportClassCategory / doImportClassNameCsv / doImportClassCategoryCsv), Store/template (doSelectTemplate / doDeleteTemplate / doDownloadTemplate / doInstallTemplate). Each side-effect (credential hash, TOTP, config/cache/asset files, CSV encode/parse, template zip/install) is isolated behind a `be/src/Reason/Service/*Interface` boundary with an `src/Compatibility/Eccube/` default + a Fake — the Issue #24 PDF-pilot pattern. **Full EC-CUBE fidelity for those side-effects (real file writes, byte-exact CSV, persisted TOTP secret) is the tracked production-cutover residual.** Difficulty stays Hard; the routes are now `実装済み`, so the Hard-ActionRedirect count is **0**.

---

## 5. Where things live

| You want… | Look at |
|---|---|
| The feature list (source of truth) | `alps.json` · `docs/alps.json.html` · `docs/alps.svg` |
| Tag taxonomy (`flow-*`, `src-*`) | `docs/tag.md` |
| Phase A detail (Be domain + JSON) | `docs/HANDOVER.md` |
| Phase 2 detail (SQL) | `sql/diff/entity-vs-eccube.md` · PR #2 |
| Phase 3 detail (HTML) | `docs/phases/alps-audit-phase3.md` · `var/templates/README.md` |
| Route/function status + migration difficulty | `docs/eccube-feature-alps-status.html` |
| Migration skills / lessons (G-14…G-25) | `docs/skills/` |
| Docs map / index | `docs/README.md` |
| Continuation guide | `docs/HOW_TO_CONTINUE.md` |
| Stale older trackers (Phase A era, do not trust for current state) | `docs/archive/progress.md` · `docs/archive/task_plan.md` |
