# Migration Status — EC-CUBE 4.3 → BEAR.Sunday + Be Framework

> **Living document.** Update the relevant row/cell whenever a layer's status changes.
> Audited 2026-05-20 against `alps.json`, `be/src`, `src/Resource`, `var/templates`, and git history.

The migration runs through **5 layers**. ALPS is the source of truth; the lower
layers implement it. "Done / partial / pending" is judged against the ALPS spec,
not optimistically.

---

## 1. Summary

| Layer | Done | State |
|---|---|---|
| 1. ALPS spec (`alps.json`) | 144/144 transitions, 444 descriptors | Complete. 144 transitions (135 flow-tagged + 9 untagged), 276+ data descriptors. Post-Phase-A remediation added 5 transitions. |
| 2. Be domain (`be/src`) | 143/144 transitions | Phase A migrated 139 transitions; Phase-3 remediation added 4 (`doSortNoMove` / `doToggleVisible` / `doUpdateTrackingNumber` / `doSendShippingNotifyMail`). 1 ALPS-only transition (`doResendActivationMail`) still has **no domain impl**; 7 Phase-A transitions are functional **stubs**. 128 Final / 127 Input / 137 Semantic / 14 Being / 38 Entity. |
| 3. BEAR Resource (JSON) | 105 Page resources | All Phase-A transitions exposed as JSON resources (`src/Resource/Page/**`): 60 admin + 45 storefront. |
| 4. SQL persistence (`be/src/Reason/Query/Sql*`) | 34/34 storage interfaces | Phase 2 complete. Every Fake storage has a SQL twin; prod context binds SQL Reasons; reproducible prod DB seed script exists. |
| 5. HTML presentation (`var/templates`) | ~41/~140 page templates | Phase 3 in progress. Storefront ported (41 page templates, all storefront pages). Admin (~100 templates) **not started**. Enrichment backlog open. |

**Test count:** `vendor/bin/phpunit` → **1574 tests, 5006 assertions, OK** (3 deprecations; "issues" = deprecations only — no failures).

---

## 2. Feature matrix

Rows = `flow-*` feature areas (see `tag.md` for the tag taxonomy). Columns = the 5 layers.
Counts are ALPS transitions per flow. `✓` done · `~` partial · `✗` pending.

| Feature area (flow) | ALPS | Be domain | BEAR Resource (JSON) | SQL | HTML |
|---|---|---|---|---|---|
| flow-browse (catalog browse) | ✓ 5 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-purchase (cart→checkout) | ✓ 17 | ~ (`doCreateOrder` stub) | ✓ | ✓ | ✓ storefront |
| flow-register (customer signup) | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-account (mypage / address) | ✓ 14 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-favorite | ✓ 3 | ✓ | ✓ | ✓ | ✓ storefront |
| flow-inquiry (contact form) | ✓ 2 | ✓ | ✓ | ✓ (`Contact` has no table) | ✓ storefront |
| flow-admin-auth | ✓ 1 | ✓ | ✓ | ✓ | ✗ admin |
| flow-manage-product | ✓ 24 | ~ (CSV import stubs; `doSortNoMove`/`doToggleVisible` ALPS-only) | ✓ | ✓ | ✗ admin |
| flow-manage-order | ✓ 13 | ~ (PDF export, shipping-CSV stubs; tracking/notify ALPS-only) | ✓ | ✓ | ✗ admin |
| flow-manage-customer | ✓ 6 | ~ (`doResendActivationMail` ALPS-only) | ✓ | ✓ | ✗ admin |
| flow-manage-shop | ✓ 15 | ✓ | ✓ | ✓ | ✗ admin |
| flow-manage-content | ✓ 9 | ✓ | ✓ | ✓ | ✗ admin |
| flow-manage-cms (layout/block) | ✓ 8 | ✓ | ~ (Template list only) | ✓ | ✗ admin |
| flow-manage-system | ✓ 8 | ✓ | ✓ | ✓ | ✗ admin |
| flow-manage-mail | ✓ 2 | ✓ | ✓ | ✓ | ✗ admin |
| flow-manage-plugin | ✓ 6 | ~ (`doInstallPlugin` stub) | ✓ | ✓ | ✗ admin |
| (untagged transitions) | ✓ 9 | ✓ | n/a | n/a | n/a |

Layer-specific notes:

- **SQL: 34/34** — every storage interface listed in `be/src/Reason/Query/` has a `Sql*` implementation.
- **HTML: storefront ✓ / admin ✗** — storefront has 41 page `.html.twig` files (`var/templates/Page/**`) + `base.html.twig` + a `Mypage/navi.html.twig` partial; all ~40 EC-CUBE storefront pages are ported. Admin has ~100 EC-CUBE templates, **none ported**. No `Block/*` widget templates exist yet.
- **flow-manage-cms Resource** — only `Admin/Template/TemplateList.php` exists for the CMS template feature; layout/block resources are present but the CMS template-management surface is partial — *unverified* in full.

---

## 3. Phase log

| Phase | Scope | Key commits |
|---|---|---|
| **Phase A** | Be domain + BEAR JSON resources. Pilots 1–5 established the 8 Be patterns, then two parallel waves took transition coverage 45 → **139/139** (HANDOVER's count, pre-remediation). 7 transitions left as functional stubs (deferred to Phase 2). Phase B added Psalm taint setup, ProdModule, env-gated entry point. | Recorded in `HANDOVER.md` (Last updated 2026-05-18) |
| **Phase 2 — SQL** | Fake → SQL for all 34 storage interfaces. Each migration follows the G-23 hypermedia-test-as-contract workflow. <br>**2a:** SQL smoke + framework (`SqlCustomerQuery`, Cart family, goCustomer end-to-end). <br>**2b:** the bulk — ~28 storages migrated, each with a Phase A (厳密移植 field alignment) + Phase B (Sql* + hypermedia) pair. <br>**2c:** production cutover — `SqlModule` binds SQL Reasons under prod; reproducible prod DB seed (`mtb_*` masters + setup script). | `3a439a2`, `0757f26`, `051d235`, `fd96242` (2a); `f6f22ee`…`9a9c89b` (2b); `f128ba6`, `6ed334d` (2c) |
| **Phase 3 — HTML** | BEAR resources rendered as HTML; templates are faithful ports of EC-CUBE's `default`-theme Twig (see `var/templates/README.md`). Storefront done in **7 waves** (~40 pages). `Ray.WebFormModule` adopted for form pages (Login pilot). Enrichment pilot underway: re-derive thin resource bodies from EC-CUBE so HTML can be faithful (Cart done; Mypage History done). | `762a739`/`2525710`/`9d06ec3` (Cart pilot); `1507dc2` (wave 1) → `46b2a08` (wave 7); `5a95435` (WebFormModule); `a44f296`/`9d06ec3` (Cart enrichment); `a31f8d8`/`3c1b03d` (Mypage History enrichment) |

ALPS remediation (`f01e1ae`, per `docs/alps-audit-phase3.md`) happened during Phase 3:
it re-tagged Favorite and **added 5 transitions** that Phase A's domain never saw.

---

## 4. Outstanding work

Punch-list, roughly highest-effort first:

1. **Admin HTML — ~100 templates (biggest chunk).** No admin `.html.twig` exists. EC-CUBE's admin theme has ~100 templates; `docs/alps-audit-phase3.md` audited only ~14 by sampling — the remaining ~50+ are *unaudited* and need per-page hypermedia checks before porting.
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
| Tag taxonomy (`flow-*`, `src-*`) | `tag.md` |
| Phase A detail (Be domain + JSON) | `HANDOVER.md` |
| Phase 2 detail (SQL) | `sql/diff/entity-vs-eccube.md` · PR #2 |
| Phase 3 detail (HTML) | `docs/alps-audit-phase3.md` · `var/templates/README.md` |
| Migration skills / lessons (G-14…G-23) | `docs/skills/` |
| Stale older trackers (Phase A era, do not trust for current state) | `progress.md` · `task_plan.md` · `HOW_TO_CONTINUE.md` |
