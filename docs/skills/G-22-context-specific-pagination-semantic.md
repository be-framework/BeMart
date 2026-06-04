---
layout: default
title: "G-22: Pagination Semantic is context-specific — `Limit` vs `OrderLimit` vs `HistoryLimit`"
---

# G-22: Pagination Semantic is context-specific — `Limit` vs `OrderLimit` vs `HistoryLimit`

## Context

Discovered during Wave 6R (`goOrderHistory`) of the EC-CUBE -> Be Framework migration. By Wave 6, BeMart had three list-projection transitions that each wanted a "max rows" parameter: admin customer grid (`goCustomerList`), customer dashboard recent-orders (`goMypage`), and customer full order history (`goOrderHistory`). The naive question was "do we reuse one `Limit` Semantic for all three, or create one Semantic per use site?". Both choices are defensible; Wave 6R picked the second deliberately.

## Problem

Be Framework wires Semantics by **parameter name**. When you write `public function __construct(int $limit, ...)`, the validator looks up a Semantic class registered under the name `limit`. If two transitions both use `int $limit`, they get the **same Semantic**, with the same bounds.

If the project tries to share one `Limit` Semantic across contexts, it runs into a real design tension:

- A customer-dashboard "recent orders" panel wants 1–50 (UI can't render more usefully).
- An admin grid wants 1–50 too, but for a different reason (storage-layer ceiling on `search()`).
- A full-history view wants 1–200 because customers with long histories legitimately exceed 50.

Coalescing them all to "1–50" cripples full history; widening to "1–200" lets a tampered admin query pull 200 rows when the storage layer assumed 50; and adding `if ($context === 'history') ...` defeats the validator's per-name lookup philosophy.

## Solution / Convention

**Create one Semantic class per (parameter name, context) pair.** DRY here is a vice, not a virtue: the duplication is the documentation.

For BeMart, three classes coexist:

| Class | Parameter name | Range | Used by |
|---|---|---|---|
| `Limit` | `limit` | 1–50 | admin grid (`goCustomerList`) |
| `OrderLimit` | `orderLimit` | 1–50 | customer dashboard (`goMypage` recent-orders panel) |
| `HistoryLimit` | `historyLimit` | 1–200 | customer full history (`goOrderHistory`) |

The bounds happen to overlap in two cases, but the reasons differ — and any future tightening of one bound should not silently affect the others. Naming the parameter for the context (`historyLimit` rather than `limit`) makes the Semantic selection unambiguous.

Rule statement:

> When two callers want the same numeric "shape" of value but with bounds that derive from **different upstream constraints** (UI capacity, storage ceiling, user-facing limit, regulatory cap), create separate Semantic classes named for the context.

This is the Semantic-side mirror of **G-17**'s Being-per-shape-and-destination: same logical shape, different governing reasons, distinct classes.

## Code example

```php
// be/src/Semantic/Limit.php  — admin grid cap (1–50, storage ceiling)
final class Limit
{
    #[Validate]
    public function validate(int $limit): void
    {
        if ($limit < 1 || $limit > 50) {
            throw new LimitFormatException();
        }
    }
}

// be/src/Semantic/OrderLimit.php  — dashboard panel cap (1–50, UI capacity)
final class OrderLimit
{
    #[Validate]
    public function validate(int $orderLimit): void
    {
        if ($orderLimit < 1 || $orderLimit > 50) {
            throw new OrderLimitFormatException();
        }
    }
}

// be/src/Semantic/HistoryLimit.php  — full-history cap (1–200, single-page render)
final class HistoryLimit
{
    #[Validate]
    public function validate(int $historyLimit): void
    {
        if ($historyLimit < 1 || $historyLimit > 200) {
            throw new HistoryLimitFormatException();
        }
    }
}
```

Inputs use the matching parameter name:

```php
// be/src/Input/ListCustomersInput.php   — admin
public function __construct(public readonly int $limit) {}

// be/src/Input/GetMypageInput.php       — dashboard
public function __construct(public readonly int $orderLimit) {}

// be/src/Input/GetOrderHistoryInput.php — full history
public function __construct(public readonly int $historyLimit) {}
```

## Anti-pattern

```php
// WRONG #1 — one Semantic shared across contexts.
//
// One Limit class with range 1–200 (the loosest). Every Input uses
// `int $limit`. The admin grid query now accepts limit=200, but
// FakeCustomerStorage::search internally caps at 50; the test sees
// 50 rows for a 200-row request and nobody notices the gap.

final class Limit
{
    #[Validate]
    public function validate(int $limit): void
    {
        if ($limit < 1 || $limit > 200) {  // too loose for admin grid
            throw new LimitFormatException();
        }
    }
}
```

```php
// WRONG #2 — branching inside one Semantic on a side-channel.
final class Limit
{
    #[Validate]
    public function validate(int $limit, string $context = 'admin'): void
    {
        $max = match ($context) {
            'admin'     => 50,
            'dashboard' => 50,
            'history'   => 200,
        };
        // ...
    }
}
// This defeats Be Framework's per-name wiring. The validator does not
// know how to inject a $context, and the dispatch logic has migrated
// from "the type system" to "a switch buried in validation".
```

## Where this matters

- Pagination caps across multiple list-resource endpoints.
- Numeric scalar values that share an apparent shape but answer to different upstream constraints — discount percentage caps per pricing tier, retry-count caps per call class, file-size caps per upload endpoint.
- Be Framework projects in general, where Semantic identity is by parameter name.

**Forward-looking discipline**: when you create the *first* Semantic of a numeric class (e.g., `Limit` for the first list endpoint), choose a context-named identifier even if you don't yet have a second context. `OrderLimit` was created in Wave 3 before `HistoryLimit` was needed in Wave 6R. Had `OrderLimit` been called `Limit` in Wave 3, Wave 6R would have faced a rename or a forced unification.

## Related

- **G-17** — the Being-per-shape-and-destination rule. G-22 is the Semantic-side mirror: same shape, different governing context, distinct class. Both follow Be Framework's per-name wiring philosophy.
- **G-16** — server-derived Semantics also use empty-body `#[Validate]`. G-22 covers when a Semantic *does* validate but its rule is context-specific.
