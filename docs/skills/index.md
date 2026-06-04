# BeMart skill gaps — discovered patterns

This directory captures conventions discovered during the EC-CUBE -> Be Framework + BEAR.Sunday migration of BeMart. Each entry is a self-contained rule that future projects building on Be Framework should know.

Source repo: `be-framework/BeMart`. The full discovery narrative is in `docs/HANDOVER.md`; each file links back to the Pilot or Wave that surfaced the rule.

Every entry follows the same shape: **Context -> Problem -> Solution -> Code example -> Anti-pattern -> Where this matters -> Related**. Files are intended to be readable in isolation, without prior project context.

## Index

| ID | Rule | Affects |
|---|---|---|
| [G-14](G-14-raydi-bind-iface-to-impl.md) | Ray.Di `bind(Iface)->to(Impl)` does NOT consult `bind(Impl)->in(SINGLETON)` — stateful Fakes need `toInstance` on both keys | Ray.Di + Fake Reasons |
| [G-15](G-15-multi-side-effect-final.md) | Multi-side-effect Final (Complex Convergence) judgment criteria — collapse only when side-effects don't read each other's results | Final design |
| [G-16](G-16-server-derived-semantic-registration.md) | Server-derived Semantic registration omission — register every public property, including ones generated server-side, to keep NOTICE count at zero | Semantic vocabulary |
| [G-17](G-17-be-chain-class-level-fixed.md) | `#[Be]` chain destination is class-level fixed — Input-per-intent + Being-per-shape | Be Framework chain design |
| [G-18](G-18-alps-absent-transition-protocol.md) | ALPS-absent transition discovery protocol — agent implements with conventional name, orchestrator backfills `alps.json` | Multi-agent orchestration + ALPS coherence |
| [G-19](G-19-admin-aaa-parallel-firewall.md) | Admin AAA is a parallel firewall — separate `AdminSessionInterface` from `SessionInterface` | AAA design |
| [G-20](G-20-cross-session-singleton-rebind.md) | Cross-session rebind requires explicit singleton storage sharing via `toInstance` on both Iface + Impl | AUTHZ tests + Ray.Di |
| [G-21](G-21-idempotent-delete-styles.md) | Idempotent DELETE has two styles — silent (200 + `alreadyAbsent`) vs 404-on-miss, route by caller expectation | REST API convention |
| [G-22](G-22-context-specific-pagination-semantic.md) | Pagination Semantic is context-specific — `Limit` (admin) vs `OrderLimit` (dashboard) vs `HistoryLimit` (full history) | Semantic naming |
| [G-23](G-23-hypermedia-test-is-migration-contract.md) | Hypermedia (Resource) tests are the storage-migration contract — never write Final-direct integration tests; ALPS gap-fill precedes SQL impl | Storage migration + test strategy |
| [G-24](G-24-ray-media-query-boundary.md) | SQL境界はRay.MediaQuery interface + SQLファイルにする — 新規Query/CommandでPHP実クラスにPDOを書かない | Storage migration + Ray.MediaQuery |
| [G-25](G-25-bdr-domain-noun-values.md) | BDRはdomain noun + readonly propertyで表す — `Result` postfix / getter-only / `Generated*` を避ける | Ray.MediaQuery BDR + value naming |

## Contribution candidates

These are intended for upstream contribution to:

- **`be-framework-skills`** — for rules that govern Be Framework usage:
  - G-14 (Ray.Di binding patterns for Fakes)
  - G-15 (Final pattern judgment)
  - G-16 (Semantic registration completeness)
  - G-17 (chain destination is class-level)
  - G-22 (Semantic naming under per-name wiring)
- **`alps-skills`** — for rules that govern ALPS-driven workflows:
  - G-18 (ALPS-absent transition protocol)
  - G-19 (parallel firewall in ALPS taxonomy / Session interfaces)
- **Either** (Be Framework + ALPS both relevant):
  - G-20 (cross-session test wiring; touches DI + AAA)
  - G-21 (idempotent DELETE; touches REST + ALPS unsafe/idempotent typing)
  - G-23 (hypermedia-as-contract + ALPS gap-fill; touches DI + Resource testing + ALPS descriptor structure)
  - G-24 (Ray.MediaQuery boundary; touches storage, DI, SQL file layout)
  - G-25 (BDR naming and value shape; touches Ray.MediaQuery, Be composite values, and semantic vocabulary)

## How these were discovered

The migration ran across roughly two phases:

1. **Pilots 1–15** — sequential, one-transition-at-a-time learning of patterns. G-14, G-15, G-16 surfaced in Pilot 5 (the first Complex-Convergence Final); G-17 surfaced in Pilot 10 (the first chain-divergence case).
2. **Waves 1–6** — parallel multi-agent orchestration. G-18, G-19 surfaced in Wave 4 (admin AAA bootstrap, found ALPS-missing transitions). G-20, G-21, G-22 surfaced in Wave 6 (AUTHZ tests across sessions, two DELETE styles in close proximity, third pagination cap forcing the naming question).
3. **Ray.MediaQuery cutover** — direct proxy / BDR migration. G-23 captured the test-contract lesson, G-24 captured the SQL boundary rule, and G-25 captured the BDR naming/value-shape cleanup after removing concrete SQL adapters.

Each gap was promoted from "ad-hoc note in HANDOVER" to "named G-NN" once the same shape of problem showed up twice or once a single instance had clearly transferable lessons.
