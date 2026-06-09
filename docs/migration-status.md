---
layout: default
title: "Migration Status — EC-CUBE 4.3 → BEAR.Sunday + Be Framework"
---

# Migration Status — EC-CUBE 4.3 → BEAR.Sunday + Be Framework

> **Living document.** Update the relevant row/cell whenever a layer's status changes.
> Updated 2026-06-04 JST against `alps.json`, `be/src`, `src/Resource`, `var/templates`, `var/sql`, and current git worktree.

The migration runs through **5 layers**. ALPS is the source of truth; the lower
layers implement it. "Done / partial / pending" is judged against the ALPS spec,
route-gate coverage, and the explicit residual boundary, not optimistically.

---

## 1. Summary

| Layer | Done | State |
|---|---|---|
| 1. ALPS spec (`alps.json`) | 532 descriptors, 207 transition descriptors | Complete as current contract. 207 transitions = 147 behavioral migration transitions + 60 `alps-route-gate` descriptors. The gate descriptors make formerly implicit route connection / safety-retreat decisions explicit. |
| 2. Be domain (`be/src`) | 147 Input / 148 Final / 155 Semantic / 14 Being / 39 Reason Entity | Complete for the migrated behavioral contract and connected hard-route surfaces. Remaining work is not unknown domain coverage; it is named compatibility residuals (order-item SQL target-engine verification, product CSV import, plugin lifecycle, export fidelity). |
| 3. BEAR Resource | 147 Page/support resource files | Aura route extras map EC-CUBE route name ↔ URL path ↔ resource URI. Resource files include page resources plus support/fallback/action resources, so this metric is broader than older "139 page resource" snapshots. |
| 4. SQL persistence (`var/sql` + Ray.MediaQuery) | 51 query interfaces, 143 `#[DbQuery]`, 143 SQL files | Phase 2 has been cut over from concrete `Sql*` adapter classes to Ray.MediaQuery direct proxies. Prod SQL binding is `SqlModule` → `MediaQueryRuntimeModule`; reproducible prod DB seed script exists. |
| 5. HTML presentation (`var/templates`) | 131 Twig templates | Phase 3 complete for the in-scope migration. Current inventory: 42 storefront/non-admin page templates, 71 admin page/partial templates, 15 shared Block templates, 3 frames. Storefront is covered; admin in-scope editor waves are covered; Store/Plugin install/search subtree remains out of scope. |

**Test baseline:** the current PHPUnit suites are `fake`, `sql`, `http`, and `smoke`. `2026-06-04 JST` non-SQL verification (`--testsuite fake,http,smoke`) passes: **1339 tests, 24832 assertions, 222 skipped, 5 deprecations, 2 notices**. SQL verification uses `malt start` + `DATABASE_URL`; the current malt DB is MySQL 8.0.46, so the MariaDB-target SQL suite exits cleanly as **754 skipped, 0 assertions**. A green SQL execution still requires a MariaDB 10.11 target-engine run or removal of MariaDB-incompatible SQL such as `JSON_TABLE`.

---

## 2. Feature matrix

Rows = `flow-*` feature areas (see `docs/tag.md` for the tag taxonomy). Columns = the 5 layers.
Counts are ALPS transitions per flow. `✓` done · `~` partial · `✗` pending.

| Feature area (flow) | ALPS | Be domain | BEAR Resource (JSON) | SQL | HTML |
|---|---|---|---|---|---|
| flow-browse (catalog browse) | ✓ 5 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-purchase (cart→checkout) | ✓ 19 | ✓ (`doCreateOrder` + `doCheckout` both: PurchaseFlow + `dtb_order_item` snapshot) | ✓ | ~ (order-item snapshot SQL caveat in §4) | ✓ storefront |
| flow-register (customer signup) | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-account (mypage / address) | ✓ 15 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-favorite | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-inquiry (contact form) | ✓ 2 | ✓ | ✓ | ✓ (`Contact` has no table) | ✓ storefront |
| flow-admin-auth | ✓ 1 | ✓ | ✓ | ✓ | ✓ admin |
| flow-manage-product | ✓ 24 | ~ (product CSV import intentionally not migrated; category/class CSV paths connected) | ✓ | ✓ | ✓ admin (list/tag/class + product/product_class/category/csv editors done) |
| flow-manage-order | ✓ 13 | ~ (PDF fidelity residual; shipping CSV persistence connected) | ✓ | ✓ | ✓ admin (list + edit/shipping/mail/mail_confirm/pdf/csv-shipping done) |
| flow-manage-customer | ✓ 6 | ✓ | ✓ | ✓ | ✓ admin (list/edit/delivery-edit done) |
| flow-manage-shop | ✓ 15 | ✓ | ✓ | ✓ | ✓ admin (payment/delivery/tax list + calendar/csv/order-status/tradelaw + payment/delivery edits + shop-master editors done) |
| flow-manage-content | ✓ 9 | ✓ | ✓ | ✓ | ✓ admin (news/page/file/css/js/cache/maintenance done) |
| flow-manage-cms (layout/block) | ✓ 8 | ✓ | ~ (Template list/add only) | ✓ | ✓ admin (layout/block/template list + template_add done) |
| flow-manage-system | ✓ 8 | ✓ | ✓ | ✓ | ✓ admin (member/login-history + system/log/security/masterdata/authority/2FA-edit done) |
| flow-manage-mail | ✓ 2 | ✓ | ✓ | ✓ | ✓ admin (mail-template editor done) |
| flow-manage-plugin *(out of scope)* | ✓ 6 | ~ (`doInstallPlugin` stub) | ✓ | ✓ | ~ admin (plugin list done; install/search out of scope) |
| route-gate transitions | ✓ 60 | ~ (connected surfaces; some fidelity residuals) | ✓ | n/a | n/a |
| non-flow behavioral transitions | ✓ 9 | ✓ | n/a | n/a | n/a |

Layer-specific notes:

- **ALPS: 207 transitions** — the older 144-transition snapshot has been superseded by `alps-route-gate` additions and several behavioral descriptors that make route/fallback decisions explicit.
- **SQL: 143/143** — every `#[DbQuery]` id registered through `MediaQueryRuntimeModule::queryClasses()` has a matching SQL file under `var/sql/`, and the smoke coverage test enforces this pairing.
- **HTML: storefront ✓ / admin ✓ (in scope)** — `var/templates` holds **131 `.html.twig` files**: 42 storefront/non-admin pages, 71 admin pages/partials, 15 Block widgets, and 3 frames (`base.html.twig`, `admin-base.html.twig`, `admin-login-base.html.twig`). The remaining admin Store/Plugin install/search subtree is out of scope because the plugin runtime is excluded. The render-diff fidelity tests (`tests/Resource/*HtmlRenderTest.php`) activate only when the gitignored `tools/ec-cube-source/` 4.3 clone is present.
- **flow-manage-cms Resource** — `Admin/Template/TemplateList.php` + `TemplateAdd` exist for the CMS template feature; layout/block resources are present but the CMS template-management surface is partial — *unverified* in full.

---

## 3. Phase log

| Phase | Scope | Key commits |
|---|---|---|
| **Phase A** | Be domain + BEAR JSON resources. Pilots 1–5 established the Be patterns, then parallel waves took the original behavioral transition set to completion. Later ALPS remediation and route-gate additions expanded the contract, but the original Phase-A figure remains historical. Phase B added Psalm taint setup, ProdModule, env-gated entry point. | Recorded in `docs/HANDOVER.md` (historical log) |
| **Phase 2 — SQL** | Fake → SQL, then SQL → Ray.MediaQuery boundary cleanup. The original storage-interface migration used the G-23 hypermedia-test-as-contract workflow; the current form is 51 MediaQuery interfaces and 143 SQL files with prod context bound through `SqlModule` / `MediaQueryRuntimeModule`. Reproducible prod DB seed (`mtb_*` masters + setup script) exists. | `3a439a2`, `0757f26`, `051d235`, `fd96242` (2a); `f6f22ee`…`9a9c89b` (2b); `f128ba6`, `6ed334d` (2c); later Ray.MediaQuery cutover |
| **Phase 3 — HTML** | BEAR resources rendered as HTML; templates are faithful ports of EC-CUBE's `default` and admin Twig themes (see `var/templates/README.md`). Storefront done in waves plus shared Block widgets. `Ray.WebFormModule` adopted for form pages. Enrichment re-derived thin resource bodies from EC-CUBE so HTML can be faithful (Cart, Mypage History, Shopping confirm/complete). Admin theme then ported through Tier-1 and in-scope Tier-2 editor waves; Store/Plugin install/search subtree remains out of scope. Current template inventory is 131 Twig files. | `762a739`/`2525710`/`9d06ec3` (Cart pilot); `1507dc2` (wave 1) → `46b2a08` (wave 7); `5a95435` (WebFormModule); `f91e10f` (admin News pilot); `1e91e92` (per-section ja-split); `da48413` (Customer); section-waves batches 1–2; `2f59bb3`…`a455281` (Order Tier-2); `4eb93f3`…`0296306` (Product Tier-2); `571dd5b` (Store template_add); `f3df0d4` (Block widgets); `1177e0d`/`2f8d17a` (Shopping enrich); `5d9e6ba` (fidelity-test fixes); `f9c5580` (`doResendActivationMail`) |
| **Phase B — security / production hardening** | Psalm taint setup, `ProdModule`, env-gated CLI entry point, EC-CUBE static-asset deployment (`default` + `admin` themes), and the HTTP router — Aura.Router maps EC-CUBE route name ↔ URL path ↔ resource URI through route extras; the BEAR.Sunday `RouterInterface` adapter returns `RouterMatch`, while BEAR\Resource owns missing resource 404 and method 405; `BeMartTwigExtension::url()/path()` resolve through Aura's generator. | `a002097` (asset deploy); `53e587e`/`39f1117` (HTTP router); `16e8c9d` (asset-package-aware render stubs) |

ALPS remediation (`f01e1ae`, per `docs/phases/alps-audit-phase3.md`) happened during Phase 3:
it re-tagged Favorite and added transitions that Phase A's domain never saw. Later route-gate work made hard route/fallback decisions explicit in ALPS, so the current profile is larger than the Phase-A/Phase-3 snapshots.

---

## 4. Outstanding work

Punch-list, roughly highest-effort first:

1. **Admin HTML Tier-2 — done (in scope).** ✓ Done. Admin Tier-1 plus every in-scope Tier-2 editor wave is ported: flow-manage-system, Customer delivery-edit, Setting/System, Setting/Shop, Order, Product, and Store template_add. Current admin inventory is 71 admin page/partial templates. The remaining Store/Plugin install/search subtree is out of scope because plug-ins are excluded from this migration. Per-section history: `docs/phases/admin-fanout-plan.md` and `var/templates/README.md` "Fan-out status".
2. **HTML enrichment backlog.** Phase 3 flagged data pages whose resource bodies are too thin for a faithful EC-CUBE port; each needs the Cart-style re-derive (ALPS → Entity/SQL/Fake enrich → template wiring). Done: **Mypage History** (`a31f8d8`/`3c1b03d`), **Shopping confirm/complete** (`1177e0d`/`2f8d17a`). Still open: **Mypage dashboard**, **Favorite**, **Address**, **Contact**.
3. **`Block/*` widget templates — done.** ✓ Done. The `logo` and `footer` Block widgets are ported (`var/templates/Block/`, `f3df0d4`). The remaining Block regions (cart/login/search) stay EC-CUBE-runtime residuals; Block is intentionally not modelled in ALPS.
4. **Phase-3 remediation transitions — all implemented.** ✓ Done. The named transitions the Phase-3 ALPS remediation added are implemented in `be/src` (`doSortNoMove`, `doToggleVisible`, `doUpdateTrackingNumber`, `doSendShippingNotifyMail`, `doResendActivationMail` — domain + storage/mailer + JSON resource + tests). Later route-gate descriptors are tracked separately from this remediation set.
5. **Phase-A stub / compatibility residuals.** `goExportOrderPdf` is now the Issue #24 compatibility pilot: Resource reachability, download headers, and `%PDF-` body generation are implemented through an isolated EC-CUBE/TCPDF service, but full EC-CUBE fidelity (delivery-note layout parity, `dtb_order_pdf` saved settings, multi-shipping template reproduction) is intentionally left as a tracked residual. **`doImportCategoryCsv` and `doImportShippingCsv` are real** — `CategoryCsvImported` parses the 4-column EC-CUBE format and upserts/deletes via `CategoryStorageInterface` (+ `CategoryIdQueryInterface` for new ids); `AdminShippingCsvImported` resolves each row's order and writes the tracking number through the same `ShippingAddressStorageInterface::updateTrackingNumber` surface as the inline `doUpdateTrackingNumber` (durable persistence covered by the SQL suite; Fake writes are no-ops). **`doUpdateCsv` is consumed end-to-end** — the export Finals overlay saved `dtb_csv` configuration on their default column vector via `CsvColumnLayout::resolve`, preserving default output when config is absent or enables no known columns. **`doCreateOrder` and `doCheckout` now converge on PurchaseFlow + `dtb_order_item` snapshot writes** — admin order creation and storefront checkout both freeze line items through `OrderItemCommandInterface::register`, with domain wiring pinned by `AdminOrderCreatedTest` / `CheckoutCompletedSnapshotTest`. **⚠ SQL verification caveat:** the durable order-item SQL path still needs a green target-engine run of `DATABASE_URL=... vendor/bin/phpunit --testsuite sql`; `order_item_register.sql` uses `JSON_TABLE` while the target is documented as MariaDB 10.11, so portability must be confirmed or the INSERT rewritten without `JSON_TABLE`. The `alps.json` contract was synced to match (`OrderItemInput`, regenerated HTML/SVG artifacts). Still stubbed / intentionally-not-modelled: `doImportProductCsv` (intentionally not migrated — the route is export-only), `doInstallPlugin` (plugin scope, out of scope).
6. **`#[Input]`-vs-Form refactor.** Noted from MyVendor.Cms issue #37 — reconcile Be Framework `#[Input]` with the `Ray.WebFormModule` `AbstractForm`. *Unverified here* — confirm against that issue before acting.
7. **Production DB bring-up.** Phase 2c shipped the seed script and prod `SqlModule` binding; an actual production database bring-up / cutover is still pending.
8. **Hard ActionRedirect routes — connected.** ✓ Done. The 22 Hard rows that Issue #24 parked on `ActionRedirect` (`docs/eccube-feature-alps-status.html`) are now wired to concrete Be/BEAR resources with the Be domain transition implemented: 認証/credential (doChangePassword / doVerifyTwoFactorAuth / doSetTwoFactorAuth / doUpdateSecurity), コンテンツ/file (doClearCache / doUpdateContentCss / doUpdateContentJs / doToggleMaintenance), マスタデータ (doSelectMasterData / doUpdateMasterData), 規格CSV (goExportClassName / goExportClassCategory / doImportClassNameCsv / doImportClassCategoryCsv), Store/template (doSelectTemplate / doDeleteTemplate / doDownloadTemplate / doInstallTemplate). Each side-effect (credential hash, TOTP, config/cache/asset files, CSV encode/parse, template zip/install) is isolated behind a `be/src/Reason/Service/*Interface` boundary with an `src/Compatibility/Eccube/` default + a Fake — the Issue #24 PDF-pilot pattern. **Full EC-CUBE fidelity for those side-effects (real file writes, byte-exact CSV, persisted TOTP secret) is the tracked production-cutover residual.** Difficulty stays Hard; the routes are now `実装済み`, so the Hard-ActionRedirect count is **0**. **⚠ 2FA setup pre-auth residual (PR #28 review — `TwoFactorAuthSet::onPut`):** the device-setup page is reached PRE-AUTH (anonymous login-context), so it currently takes `loginId` + the candidate TOTP secret from the request body, and `TwoFactorAuthInterface::enable()` overwrites the secret for that `loginId` with no ownership check — a caller who passes another admin's `loginId` could replace that admin's 2FA device. The production cutover must bind a server-generated secret + the pending login identity into a pre-auth session/challenge state at credential-verification time and consume it here instead of trusting the client values; until then the route relies on CSRF + the documented `enable()` contract. **Do not widen this surface (e.g. expose it post-auth for an arbitrary `loginId`) before the challenge state lands.**

---

## 5. Where things live

| You want… | Look at |
|---|---|
| The feature list (source of truth) | `alps.json` · `docs/alps.json.html` · `docs/alps.svg` |
| Tag taxonomy (`flow-*`, `src-*`) | `docs/tag.md` |
| Phase A detail (Be domain + JSON) | `docs/HANDOVER.md` |
| Phase 2 detail (SQL) | `sql/diff/entity-vs-eccube.md` · `var/sql/` · `src/Module/MediaQueryRuntimeModule.php` |
| Phase 3 detail (HTML) | `docs/phases/alps-audit-phase3.md` · `var/templates/README.md` |
| Route/function status + migration difficulty | `docs/eccube-feature-alps-status.html` |
| Migration skills / lessons (G-14…G-25) | `docs/skills/` |
| Docs map / index | `docs/README.md` |
| Continuation guide | `docs/HOW_TO_CONTINUE.md` |
| Stale older trackers (Phase A era, do not trust for current state) | `docs/archive/progress.md` · `docs/archive/task_plan.md` |
