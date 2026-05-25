# Pilot 2 Variation — Linear/Minimal version of `doAddCartItem`

## What this is

A snapshot of `CartItemAdded.php` at commit `8f75c66` (2026-05-17), **before** the Cascade refactor that introduced `QuantityAdjusted` and `CartMerged` Beings.

In this version, the entire transition is implemented as **one Final class** with 5 sequential procedural blocks inside the constructor:

1. StockCheck — ProductClass lookup + stock cap
2. SaleLimitCheck — per-customer purchase cap
3. SaleTypeResolution — cartKey = `sessionPrefix_saleTypeId`
4. CartItemMergePrice — same productCode merge + totalPrice
5. DeliveryFeeAccumulation — per-item shipping aggregation

Three Reasons (`ProductClassQuery`, `CartQuery`, `CartCommand`) are all injected into the same Final.

## Why it was rejected

This is a **Linear / Minimal** pattern (1 Input → 1 Final), not a Cascade. The 5 "Reason blocks" are sequential procedural code inside one constructor — they do not form Being chain. The Final ended up doing four jobs:

- Quantity decision (Stock / SaleLimit / cartKey)
- Cart context loading
- In-memory merge + totals
- Persistence

The thickness of this Final was the symptom that drove the Cascade refactor. See `be-adoption-evaluation.md` §3 (a) for the diagnosis.

## What replaced it

Commit `35a0201` (2026-05-17) replaced this with a true Cascade:

```
AddCartItemInput
  → QuantityAdjusted (Being)   — quantity decision + cartKey
  → CartMerged (Being)         — cart load + in-memory merge + totals
  → CartItemAdded (Final)      — persistence only
```

The current `be/src/Final/CartItemAdded.php` (~30 LoC for the class body) does only persistence; the public surface comes from upstream Beings via `#[Input]` by-name connection.

## Why this snapshot is preserved

- **Educational comparison** — readers can diff this against the current Cascade version to see what "Lying-Being" or "thick Final" looks like in practice, and what the refactor actually moved.
- **Audit trail** — the HANDOVER Pilot 2 section references both classifications (initial "Cascade Diamond" → "Linear/Minimal" → "Cascade"). This file is the artifact of the "Linear/Minimal" stage.
- **Not for use** — this file is documentation only. It is intentionally outside `be/src/` so the autoloader does not pick it up.

## Related

- Current Cascade implementation: `be/src/Being/QuantityAdjusted.php`, `be/src/Being/CartMerged.php`, `be/src/Final/CartItemAdded.php`
- Evaluation of why Cascade is preferable: `be/docs/be-adoption-evaluation.md`
- HANDOVER Pilot 2 改訂履歴: `HANDOVER.md` §Pilot 2
