---
layout: default
title: "Why hypermedia (Resource-layer) tests are the underrated foundation"
---

# Why hypermedia (Resource-layer) tests are the underrated foundation

The companion essay to `docs/skills/G-23-hypermedia-test-is-migration-contract.md`. G-23 is the operational rule for storage migration. This document captures **why** the rule exists — the underlying argument for treating hypermedia (Resource-layer) tests as the central test abstraction.

It was written after Phase 2a of the EC-CUBE → BeMart migration. The Steps 2-5 sequence taught the operational lesson (G-23). The conversation that followed surfaced the broader principle, recorded here so the principle survives outside the Phase 2a context.

## Terminology: workflow evidence

The name "hypermedia test" is useful history, but it is too narrow for what BeMart now demonstrates. The durable abstraction is a workflow/state-transition contract.

One scenario pins a transition described by ALPS and is then projected across boundaries:

1. PHP Resource projection: `tests/Hypermedia/Flow*.php` traverses the resource graph in process.
2. HTTP projection: `tests/Http/Flow*.php` extends the same workflow and swaps only the `ResourceInterface` implementation to `HttpResource`, crossing the real HTTP / cookie boundary.
3. HTML projection: render tests, HTML semantic-link lookup, and Web E2E evidence confirm that the same transition remains available as `rel` / `class` / `href` / `form action` affordance.

That is why this layer is stronger than a single test category. A change that preserves the workflow across PHP, HTTP, and HTML has much better evidence than a change that only satisfies one representation.

## Completion gate and stop rule

For BeMart, workflow evidence is also the completion gate for Web+DB. "The page opens" is not enough. A feature is complete only when the affordance exposed by Web/HTTP can create or change business state, and that state can be read back through another page, another role, or another projection.

The loop is fixed:

1. Express the business story as a Hypermedia workflow.
2. Project the same story through real HTTP.
3. Exercise the browser/Web+DB route and NG cases.
4. If the browser finds a bug, add or adjust the Hypermedia/HTTP regression first.
5. Confirm the regression is red, fix the implementation, then rerun Hypermedia -> HTTP -> browser.

The stop rule matters as much as the green path. If an unsafe operation cannot be reached from `_links`, `Location`, an HTML form action, or an ALPS-described transition, do not invent a runner-only shortcut. If the request body cannot be derived from the form/profile with enough confidence, do not synthesize a body just to make a row pass. Record it as fail or targetOut with the missing affordance and follow-up.

The `20260610-web-db-all-routes` run applies this rule. Storefront purchase, order history detail, reorder, profile maintenance, contact, password request, downloads, NG form cases, admin product create/update/copy/bulk status/delete, admin category create/update/delete, admin tag create/delete, and admin payment create/update/delete are green through Web/HTTP evidence. Other Admin CRUD/update operations remain fail where only page reachability exists. That is intentional: green without workflow evidence would be weaker than a visible fail.

## The underrated-ness

Hypermedia (Resource-layer) tests sit between unit tests and E2E:

- They build the real DI container and resolve the real resource graph
- They make real `resource->get(...)` / `resource->post(...)` calls
- They assert on real response envelopes (status code, body shape, link relations)
- They do all of this in milliseconds, deterministically, without a browser

This is a sweet spot. And yet: in practice, teams reach past it. Most projects skip directly from unit tests to E2E (Cypress / Playwright / Selenium). The Resource layer barely gets a name.

The pattern is not that teams considered hypermedia tests and rejected them. The pattern is that the layer is invisible to them. Hence "underrated" understates the situation — it is mostly **unobserved**.

Three plausible causes:

1. **REST is misread as "JSON over HTTP"**. Once REST is reduced to "the API is HTTP and returns JSON", HATEOAS and link relations look like ornamentation. ALPS-style specs look like overhead. The hypermedia-as-engine-of-application-state premise — which is what makes the Resource layer testable as a navigation graph — has no place in this reading.
2. **No spec abstraction**. Many projects have no machine-readable spec. They may have OpenAPI, but the OpenAPI is treated as documentation rather than truth. With no spec, "what the system should do" is whatever the implementation does — and the test layer that mirrors spec-driven navigation (hypermedia testing) has nothing to mirror.
3. **The "running browser is the only ground truth" reflex**. If the DOM is visible, the system is real. If the system is a constructed graph in memory, it is suspect. This is empiricism without the discipline of theory — the resource graph is just as real, but it does not look like anything.

## The incoherence of accepting E2E while rejecting hypermedia

The position "hypermedia tests are overengineering" combined with the position "E2E tests are necessary" is internally inconsistent.

E2E tests are massively heavier than hypermedia tests:

| dimension | hypermedia (Resource) | E2E (browser) |
|---|---|---|
| latency per test | ms | seconds |
| determinism | full | flaky (async, DOM races, network) |
| infra | none (process-local) | browser binary + driver + screen, often Docker |
| maintenance | assertions follow spec | selectors rot, waits proliferate |
| coverage of protocol semantics | direct | indirect, often missed |
| coverage of visual regression | none | direct |
| coverage of real-browser JS | none | direct |

For most assertions a team actually writes — "POST returns 201 with the created resource", "GET 404s when missing", "the form rejects this input with this error message" — the hypermedia layer covers them faster and more reliably than E2E. E2E's genuine domain is narrow: visual diffs, complex JS interaction, cookie / redirect / auth flows that depend on the browser's HTTP behavior.

A team that does E2E but skips hypermedia is paying 10-100x for the assertions that overlap, while still missing the narrower assertions that only E2E can cover (because they have not narrowed their E2E suite to those). The result is slow, flaky, expensive coverage of the wrong layer.

Calling the cheaper, faster, more rigorous solution "overengineering" while accepting the more expensive, slower, more fragile one is not a defensible engineering position. It is a category error: the label "overengineering" is being assigned to the layer that minimizes engineering cost, and withheld from the layer that maximizes it.

## Property-based equivalence proof

The most underappreciated property of Resource-layer tests is what happens when you have **two** of them with shared assertions.

If `tests/Resource/CartResourceTest.php` (Fake-backed) and `tests/Resource/Sql/CartResourceSqlTest.php` (SQL-backed) both pass with the **same** assertions, then for every input both suites cover, the system is observably identical to a client between the two storage backends.

This is a property-based proof of equivalence, established by construction. Not a probabilistic confidence built from a hundred unit tests; an actual proof over the input set the tests enumerate.

That property is what makes storage migration tractable:

- "Replace the in-memory Fake with PDO" becomes "the client cannot tell the difference, by test"
- "Replace PDO with Doctrine" would be the same trick
- "Replace MariaDB with PostgreSQL" would be the same trick
- "Reimplement the entire service in another language" would, in principle, still be the same trick, provided the spec (ALPS) and the contract layer (Resource) are preserved

The migration plan stops being "do dangerous swap, hope nothing breaks". It becomes "make the new test sibling green; the old test sibling is already green; therefore equivalence". The psychological load drops by an order of magnitude. The number of failure paths drops by an order of magnitude.

This is not a property unit tests have. Unit tests live inside one implementation; they cannot witness the boundary between two implementations. This is not a property E2E tests have either, in practice — E2E suites are usually too slow and flaky to maintain *two parallel* versions, so teams write one E2E suite against the "real" backend and lose the contrast.

## Spec alignment

ALPS + Resource-layer tests share a structure:

- ALPS describes a graph of descriptors with transitions
- Resource-layer tests traverse that graph by URI

The mapping is mechanical. A transition in ALPS corresponds to a `resource->get(URI, args)` or `resource->post(URI, body)` in the test. Assertions on the response body are assertions on the ALPS-described representation.

When the spec changes, the tests need to change in obvious, local ways. When the spec does not change, the tests do not change — and any implementation that holds the spec is interchangeable with any other.

By contrast, unit tests are aligned with implementation structure (classes, methods). They change every time the implementation changes, even when the spec does not. E2E tests are aligned with UI structure (selectors, page flows). They change every time the UI changes, even when the spec does not. Only Resource-layer tests are aligned with spec structure.

This is why ALPS without hypermedia testing leaves money on the table, and why hypermedia testing without ALPS is harder than it should be. The pair is more than the sum.

## The "minimum discipline" framing

The Phase 2a retrofit (Step 5) re-stated the principle in operational terms: **never edit the existing Resource test to "make it work with the new storage"**. If the Fake-backed Resource test needs to change during a storage swap, the contract has changed and clients will see it. That is a different conversation than a storage swap.

This rule is not extra discipline. It is the minimum discipline that lets the equivalence proof exist at all. Without it, "Fake test green" and "SQL test green" are two facts about two different test programs, and equivalence collapses to a coincidence.

Calling this discipline "overengineering" mistakes the floor for the ceiling. Hypermedia testing is not the most rigorous form of system testing. It is closer to the least rigorous form that still lets you migrate without rewriting your test suite.

## What E2E should actually be for

A team that uses hypermedia testing well can shrink E2E to its actual domain:

- Visual regression (was this button always that color)
- Real browser JS behavior (does this React hook work in Safari)
- Auth / cookie / CORS / CSP flows where the browser's HTTP behavior is the variable under test
- Workflow tests that span multiple full pages with real navigation

That narrowed E2E suite is small, slow only because each test is intrinsically slow, and worth its cost. The bulk of "does the API behave correctly" coverage moves down to Resource-layer tests where it belongs.

A team that does not use hypermedia testing pushes all of "does the API behave correctly" up into E2E. The E2E suite then grows beyond its useful domain, becomes the bottleneck, becomes the source of flakes, becomes the reason CI is slow, becomes the reason coverage is uneven — and the team learns to fear the test suite. None of which is E2E's fault. It is the absence of the layer below it.

## Related

- `docs/skills/G-23-hypermedia-test-is-migration-contract.md` — operational rule: how to apply this in storage migration
- `docs/skills/G-14`, `G-20` — DI binding patterns that hypermedia tests depend on (shared singletons via `toInstance`)
- `docs/skills/G-19` — the parallel firewall pattern, which only becomes testable cleanly at the Resource layer
