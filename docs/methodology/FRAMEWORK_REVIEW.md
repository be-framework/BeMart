# Framework review — Be Framework + BEAR.Sunday at 139-transition scale

written after migrating all 139 ALPS transitions of EC-CUBE 4.3 to Be Framework + BEAR.Sunday in a single orchestrated session. this document is the orchestrator's candid take, written for future engineers (and framework authors) who will read it cold.

> 2026-06-01 note: this is a historical framework review of the original 139-transition migration slice. the current BeMart profile is larger after ALPS route-gate additions and Ray.MediaQuery cutover; see [`../migration-status.md`](../migration-status.md) for current counts. the framework observations remain relevant as the original scale test.

session stats: 5 → 139/139 transitions (3.6% → 100%), 90 → 709 tests, 9 wave of worktree-isolated parallel subagents (28 agent invocations total), 11 skill gaps surfaced (G-14 〜 G-24), 10 of them externalized as `docs/skills/`.

## TL;DR

both frameworks delivered. **Be Framework gave a domain vocabulary that mapped 1:1 to real EC-CUBE flows; BEAR.Sunday gave a clean HTTP boundary that the Be layer plugged into without ceremony.** the friction we hit was almost entirely at the seams: `#[Be]` chain destinations being class-level fixed (G-17), AppModule single-file scaling under parallel agents (G-23), Psalm taint opacity through Becoming chains (Slice 9 honest finding). these are real concerns at this scale but they did not block the migration.

## Be Framework — what it gives you

### 1. pattern vocabulary that maps to reality

5 patterns covered 100% of EC-CUBE 4.3 transitions:

| pattern | example transitions | count |
|---|---|---|
| Direct (Input → Final) | most reads + simple CRUD | 130+ |
| Linear (Input → Being → Final) | Pilot 10 doUpdateCartItemQuantity | 1 |
| Multi-Reason Being | Pilot 4 doRegisterCustomer, Wave 5O doCreateCustomer, Wave 8α doCreateProduct | 3 |
| Diamond-Cascade | Pilot 2 doAddCartItem, Pilot 5 doCheckout, Pilot 12 doReorder | 3 |
| Branching Final | Pilot 3 doConfirmOrder | 1 |

once we had the pattern label, `agent briefings` could say "Wave 5O Multi-Reason mirror" and agents produced correctly-structured code on first try. this is a real productivity win that fewer frameworks deliver as cleanly.

### 2. Final-as-proof philosophy

「Final が存在するということは XYZ が成立した証明」. once you internalize this, AUTHZ ladders + state transitions stop being scattered and concentrate at the constructor. Pilot 5 `CheckoutCompleted`'s existence means "stock reserved + payment captured + order persisted + mail dispatched + cart cleared". one object names the whole transaction.

### 3. Semantic per-param-name auto-discovery

declare `string $email` on an Input, and `Be\Semantic\Email::validate(string $email)` runs automatically at metamorphosis. Pilot 8 widened `Name01` to nullable once, and partial-update on customer / admin member / contact form all consumed the change. validation lives next to the value object, not scattered across resources.

### 4. `#[Input]` / `#[Inject]` separation in Final constructors

reading a Final's constructor signature tells you immediately what's "data from the previous Being" vs "Reason injected at construction". this is a small thing but it accumulates: 139 Finals are skimmable in seconds because of it.

## Be Framework — where it bit us

### G-17: `#[Be]` chain destination is class-level fixed

`#[Be(NextClass::class)]` is on the Being class. so "the same pre-processing pointing at a different Final" cannot be expressed by reusing the Being — the Being must be duplicated. concretely: Wave 5O `AdminCustomerCreating` is ~95% the body of Pilot 4 `CustomerRegistering`, but `CustomerRegistering` has `#[Be(CustomerRegistered::class)]` baked in. couldn't reuse; had to copy + retarget.

**convention we adopted**: Input-per-intent + Being-per-shape. same Being shape, different intents → duplicate the class. logged as G-17 in `docs/skills/`.

this is a structural consequence of Be's commitment to compile-time-known chains. it's not a bug. but at 139-transition scale it cost us 3+ duplicated Beings.

### G-22: Semantic class proliferation per context

per-param-name auto-discovery means `int $limit` triggers `Be\Semantic\Limit`. when 3 different contexts wanted different caps (admin search 1-50, dashboard 1-50, full history 1-200), we ended up with `Limit`, `OrderLimit`, `HistoryLimit` — three Semantic classes for the same conceptual constraint. DRY-violating but structurally honest.

a `#[Validate(class: Foo::class)]` opt-in override would let context-specific Inputs name their validator explicitly without forcing 3 Semantic classes. potential framework improvement.

### Psalm `#[Be]` chain opacity (Slice 9 honest finding)

`BecomingInterface::__invoke()` returns `object`. so taint analysis breaks at every metamorphosis. we annotated boundaries (`@psalm-taint-source input`, `@psalm-taint-sink html / sql / network`) but the path between source and sink goes through `object`, killing flow tracking.

this is the single concrete area where Be Framework would gain real engineering value from upstream work: **a Psalm plugin that walks `#[Be]` annotations and propagates types + taint through the chain**. tracked as Slice 11.

### 0-arg Input feels awkward

doLogout works with a 0-arg `LogoutInput`. it builds fine, Becoming runs, Final emits — but constructing an object with no fields purely to start a chain feels like ceremony for ceremony's sake. an explicit "trigger" verb form (e.g. `becoming->trigger(LoggedOut::class)`) would name the intent better than wrapping nothing.

## BEAR.Sunday — what it gives you

### 1. URI ↔ class resolution is deterministic

`page://self/admin/customer-list` resolves to `MyVendor\BeMart\Resource\Page\Admin\CustomerList`. kebab → PascalCase, predictable, no surprises. agent briefings could say "place the Resource at `src/Resource/Page/Admin/Foo/Bar.php`" and agents got the URI right on first try.

### 2. file + directory coexistence

`Cart.php` and `Cart/Item.php` live in the same PHP namespace without collision. lets you grow a resource family without restructuring the root.

### 3. `#[Link]` for hypermedia declaration

every Resource method declares its onward transitions. didn't drive test infrastructure with this (HATEOAS-style discovery isn't tested), but it served as a design-time reference — "what's the next state from here?" was always visible in the resource file.

### 4. one Resource per HTTP method

`onGet` / `onPost` / `onPut` / `onDelete` co-located per URI gives clean REST. when a URI grew multiple methods (e.g. `Mypage/Favorite.php` has onPost + onDelete), the file stayed coherent.

## BEAR.Sunday — where it bit us

### G-14: `bind(Iface)->to(Impl)->in(Singleton)` doesn't share

Ray.Di's linked binding pattern creates a fresh `Impl` instance per resolution, ignoring `bind(Impl)->in(Scope::SINGLETON)` on the same class. tests that introspect via the concrete `Fake*` class see a different instance from the Becoming chain (which resolves via Interface). fix: `$obj = new Impl(); bind(Iface)->toInstance($obj); bind(Impl)->toInstance($obj);` on **both** bindings.

we hit this in Pilot 5, again in Wave 6P (cross-session rebind), then routinely in Wave 7-9 when Fakes held state. Ray.Di documents the rule but a runtime warning on the misuse pattern would save real time.

### G-23: AppModule single-file scaling

Wave 8 had 5 agents writing AppModule.php in parallel worktrees. cherry-pick produced textual conflicts on 4 of 5. git auto-merge resolved imports (different lines) but the `configure()` body conflicted when two agents added bindings adjacent to each other.

orchestrator-side resolution: each agent appended to the end of `configure()` under a unique `// Wave Nx:` comment block. this worked for 2-3 agents but broke down at 4-5.

**recommended structural fix**: split AppModule into per-domain Modules (CustomerModule, AdminModule, CmsModule, CatalogModule, OrderModule). Ray.Di supports module composition. would let multiple agents edit different modules without conflict. Phase 2 deferred.

### G-20: test session rebind requires manual storage sharing

`SessionInterface` rebind for cross-customer AUTHZ tests creates a new `Injector`, which discards the previous Singleton-scoped storage. fix: in `setUp()`, build the storage once and bind it via `toInstance` in both `Iface` and `Impl` bindings when rebinding.

related to G-14 — same root cause (Singleton scope not honored across linked bindings). but the test setup pattern is non-obvious until you've hit it.

### CSRF interface direction

`CsrfToken::isValid(?string $token): bool` is the only method. Slice 8 deliberately omitted `issue()` because Slice 7.2's EC-CUBE EventListener was supposed to mirror the active Symfony token into the session for the next POST.

result: `goLoginForm::onGet` returns `csrfToken: null` in the body. the API surface is honest (token is null until the EventListener runs) but the read endpoint can't help a fresh client bootstrap. **Phase 2 might want `issue()` on the interface** with the production adapter delegating to Symfony's CsrfTokenManager.

## the seam — Be Framework × BEAR.Sunday

the migration's hot path was:

```
HTTP request
  → BEAR Resource (onPost/onGet/...)
    → CSRF guard (Slice 8 boundary)
    → ($this->becoming)(new XxxInput(...))
      → Be Framework chain (Input → Being → Final)
        → Reasons (Storage, Mailer, Session, ...)
    ← catch domain exceptions → map to HTTP code
  ← project Final to response body + #[Link]
HTTP response
```

every one of the 139 transitions fits this shape. when the shape was right, an agent could implement a transition in one commit including the domain layer and the resource and the tests. when the shape was wrong (e.g. CSV import, plugin install, PDF export), we stubbed and documented Phase 2.

the only real friction at the seam is **catching `SemanticVariableException`** in every Resource onPost — it's repetitive ceremony. a Resource-level "default error mapper" attribute (`#[MapSemanticErrors(400)]`) would deduplicate the 50+ identical try/catch blocks across resources.

## what i would prioritize for the next iteration

if i were the framework author, in rough order:

1. **Be Framework Psalm plugin** (Slice 11). walks `#[Be]` chains, propagates types + taint. this is the single highest-leverage upstream work — it would make `composer psalm-taint` go from "clean but opaque" to "clean and actually verified end-to-end".

2. **AppModule splitting convention + tooling**. document Module composition for large apps; provide a code generator that splits an AppModule by domain prefix.

3. **`#[Validate(class:)]` opt-in override on Semantic**. let an Input field opt out of name-based wiring when context-specific bounds are wanted. removes G-22 force.

4. **`#[MapException]` on Resources**. attribute-driven exception → HTTP code mapping at the BEAR boundary. 50+ identical try/catch blocks across resources collapse to declarations on the class.

5. **Ray.Di Singleton-link warning**. detect `bind(Iface)->to(Impl)` + `bind(Impl)->in(SINGLETON)` and warn at boot. saves new users the G-14 / G-20 day.

6. **Be Framework "trigger" verb**. `becoming->trigger(LoggedOut::class)` for 0-arg start. zero-field Input objects feel like ceremony.

7. **Test infrastructure helper for HTTP request scope**. session rebind + storage sharing wrapped in a fluent `withSession($id)->resource($uri)->...` helper. removes the G-20 footgun for newcomers.

## what i would build on top

beyond framework changes, the **migration workflow itself** could be packaged:

- `.claude/workflows/migrate.json` — already exists, drove Pilot 1-5
- `.claude/prompts/` — alps-analyze, domain-implement, be-review, application-implement, integration-review, security-review
- `docs/skills/G-NN-*.md` — 10 surfaced rules

these form a reference implementation of **"orchestrator + worktree-isolated parallel subagents" for migrating a legacy app onto Be Framework + BEAR.Sunday**. with light packaging this could move out of this repo and become `be-framework-migration-toolkit` or similar.

## conclusion

at 139-transition scale, Be Framework + BEAR.Sunday delivered. the patterns are real, the seams are clean, the friction we hit was named (G-14 〜 G-24) and worked around within session. nothing about the stack made us reconsider the migration.

the upstream-work list above is what would make the **next** project of this scale lower-friction. nothing on the list is blocking — they're sandpaper, not load-bearing.

— orchestrator, after 9 waves and 28 agent invocations. 139 transitions, 709 tests, 2012 assertions, 100%.
