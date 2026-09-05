---
layout: default
title: "G-23: Hypermedia tests are the migration contract — never write Final-direct \"integration\" tests"
---

# G-23: Hypermedia tests are the migration contract — never write Final-direct "integration" tests

## Context

Discovered during Phase 2a of the EC-CUBE → BEAR.Sunday + Be Framework migration, while swapping Fake Reasons (in-memory) for SQL-backed implementations (PDO over MariaDB 10.11). Steps 2-4 wrote per-storage unit tests AND "Sql integration tests" that constructed Finals directly (e.g. `AdminCustomerFetchedSqlIntegrationTest`). Step 5 retrofit deleted those and replaced them with Resource-layer hypermedia tests that rebind storage bindings via DI override.

## Problem

When migrating Fake → real storage, the obvious-looking pattern is an "integration test" that:

1. Instantiates the Final directly (`new AdminCustomerFetched($email, $session, $customerQuery, $orderQuery, $favorites)`)
2. Passes real SQL backends as constructor arguments
3. Asserts on the resulting projection

This looks end-to-end. It is not. It bypasses:

- The DI container (Ray.Di `Injector`)
- The Becoming chain (`Input → Being → Final` resolution)
- The Resource graph and `#[Link]` traversal
- The HTTP envelope (`Code::OK`, body shape, error transformation)

Any DI wiring bug — missing binding, wrong scope, singleton not shared, override mis-targeted — slips through. Production fails on the first request.

A second, deeper failure: per-storage unit tests + Final-direct "integration" tests *combined* still cannot prove **client-observable equivalence** between the Fake and SQL implementations. They are two test shapes against two different surfaces, so neither establishes "the contract did not change".

## Solution / Convention

Treat the Resource-layer hypermedia test as the **migration contract**.

1. `tests/Resource/*ResourceTest.php` (Fake-backed) defines the client-visible behavior. It must never need to change during a storage migration.
2. Add a SQL-backed sibling at `tests/Resource/Sql/*ResourceSqlTest.php` that:
   - Builds the same `Injector(new AppModule(...))`
   - Overrides ONLY storage bindings to SQL impls (+ shared test `PDO`)
   - Calls the same `$resource->get('page://self/...')` URIs
   - Asserts the same projection shapes
3. Both suites must stay green at every migration step. **Fake green AND SQL green = client-observable equivalence**.

The DI rebinding lives in an abstract base:

```php
abstract class AbstractResourceSqlTestCase extends TestCase
{
    use SqlFixturesTrait;

    protected ResourceInterface $resource;
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->bootSqlDatabase();          // drop + load schema + BEGIN
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($this->pdo, $this->extraOverride()) extends AbstractModule {
            // rebind PDO + each *QueryInterface / *StorageInterface → Sql* impl
        };
        $injector = new Injector($base->override($override), $this->varTmp());
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** Hook for per-test session/auth overrides (admin firewall, customer session). */
    protected function extraOverride(): AbstractModule
    {
        return new class extends AbstractModule { protected function configure(): void {} };
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();                          // per-test isolation
    }
}
```

What this catches that Final-direct tests miss:

- Singleton sharing across the Becoming chain
- `#[Link]` resolution and child-resource hydration
- Resource URI routing
- HTTP envelope (status, body, error transformation)
- Cross-binding interaction (e.g. `AdminSessionInterface` wired correctly alongside the SQL pipeline)

## Web+DB completion adjunct

The same rule applies when judging whether the web application is complete. Browser evidence alone is not enough if it cannot be projected back into a Resource/HTTP workflow.

For Web+DB completion work:

1. Start with one user story per `tests/Hypermedia/Flow*.php` file.
2. Keep the hardcoded URI to the entrypoint only; after that, follow `_links`, `Location`, HTML form action, or the ALPS rel.
3. Create business state through Web/HTTP transitions, not direct SQL seed or fixture boundary.
4. Add an HTTP projection for the same story before treating browser evidence as complete.
5. If a browser run exposes a missing affordance or unclear payload, record it as fail/follow-up. Do not add runner-only body construction, route inference, fake stores, or direct state injection to turn the row green.

The 20260610 Web+DB run deliberately leaves Admin unsafe CRUD/update operations as fail where only screen reachability was proven. Those failures are completion work, not test flakiness. They should be closed by adding Hypermedia/HTTP workflow evidence first, then implementation fixes, then browser re-run.

## Anti-pattern

Do NOT write `XxxSqlIntegrationTest` that constructs the Final directly:

```php
// BAD — bypasses DI, proves nothing about the contract
public function testHappyPath(): void
{
    $customerQuery = new SqlCustomerQuery($this->pdo);
    $orderQuery    = new SqlOrderQuery($this->pdo);
    $favorites     = new SqlFavoriteStorage($this->pdo);
    $session       = new class implements AdminSessionInterface { /* ... */ };

    $result = new AdminCustomerFetched(
        'alice@example.com', $session, $customerQuery, $orderQuery, $favorites,
    );

    $this->assertSame(3, $result->orderCount);
}
```

This passes when production fails. Delete it. Write the Resource-layer sibling instead.

A related anti-pattern: editing `tests/Resource/*ResourceTest.php` (the Fake-backed test) to "make it work with the new storage". If the Fake test must change, the contract has changed and clients will see it — that is a different conversation than a storage swap. Storage swaps must leave Resource tests untouched.

## ALPS adjunct: gap-fill before impl, not after

A second pattern surfaced alongside this one. When an ALPS descriptor is missing a child field used by the SQL impl, **add the descriptor before writing the SQL**, not retrofitted after. Inline child descriptors under a parent (template flavor) can be promoted to top-level `src-entity` when first needed as storage source-of-truth.

Example: Step 5 promoted `CartItem` and `ProductClass` from inline-only to top-level descriptors, and added `Favorite` as a new top-level descriptor. None required schema changes — only ALPS structure changes. Once promoted, the SQL impl reads its field list from ALPS directly, and any future iteration starts from the same source.

This avoids the trap of "read EC-CUBE source to discover semantics, write SQL, never reflect back". That trap makes the same discoveries on every iteration. ALPS is the durable source of truth; the source code is a derivation.

## Pre-flight checklist (efficient next iteration)

For each storage migration batch (e.g. one Reason interface family):

1. **ALPS first** — does the target descriptor exist and have every field the Entity / SQL needs?
   - Missing top-level descriptor → add as `src-entity`
   - Inline child needed at top-level → promote
   - New atomic fields needed → add field descriptors
2. **Schema check** — confirm in `sql/schema/bemart-schema.sql`:
   - Column names + types
   - FK constraints (especially to empty `mtb_*` tables — nullable defaults are usually safe; document where you defaulted to NULL because the master table is empty in the structure-only dump)
   - UNIQUE indexes (or their absence — record `customer_id + product_code` cases as "app-level guard required")
3. **Implement** — PDO impl in `be/src/Reason/Query/Sql*.php`. Distinct named placeholders per branch. Cast decimals to `int` for JPY money. Transaction boundary explicit; nested → use savepoints.
4. **Unit test** (storage layer) — `be/tests/Sql/Sql*Test.php`. Per-method coverage including miss / empty / boundary.
5. **Hypermedia test** (Resource layer) — `tests/Resource/Sql/*ResourceSqlTest.php`. Mirror an existing `tests/Resource/*ResourceTest.php`. Same URIs, same assertions, only `extraOverride()` differs (per-test auth state).
6. **Both suites green** — Fake-backed + SQL-backed. If only SQL is green and Fake is broken, you have changed the contract by accident.

Do NOT in this iteration:

- Write `*SqlIntegrationTest` with Final-direct instantiation
- Touch `AppModule` production bindings (SQL impls remain unbound in production until cutover phase)
- Modify existing Fake reasons
- Read EC-CUBE source to "discover" what ALPS could have told you

## Related

- G-14, G-20 — `toInstance` for shared singletons. The same subtleties apply when rebinding SQL impls: pass the shared `PDO` via `toInstance` rather than container scope.
- G-18 — ALPS-absent transition protocol. The same backfill rule extends from transitions to data descriptors. Inline-to-top-level promotion is the descriptor analogue.
- G-19 — parallel firewall. SQL hypermedia tests rebind `AdminSessionInterface` (or `SessionInterface`) per case to exercise AUTHZ states (anonymous, authenticated) without rebuilding the whole `AppModule`.

## Where surfaced

EC-CUBE → BeMart Phase 2a (commits `3a439a2` → `fd96242`). Steps 2-4 added the Final-direct anti-pattern. Step 5 retrofit (3 ResourceSql test files, 7 hypermedia cases) replaced them; all 7 passed on first run, retroactively validating that Steps 2-4 SQL impls were correct under the DI envelope. The lesson: that validation only existed once the hypermedia test layer was in place. Two extra steps would have been avoided by writing the hypermedia test from Step 2.
