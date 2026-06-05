---
layout: default
title: "G-17: `#[Be]` chain destination is class-level fixed — Input-per-intent, Being-per-shape"
---

# G-17: `#[Be]` chain destination is class-level fixed — Input-per-intent, Being-per-shape

## Context

Discovered during Pilot 10 (`doUpdateCartItemQuantity`) and re-confirmed in Wave 5O (`doCreateCustomer`). The temptation in both cases was to reuse an existing Being class because it had the right "shape" for the operation. Be Framework's `#[Be(NextClass::class)]` attribute is declared on the Being class itself, so reusing a Being means accepting its hard-coded downstream.

## Problem

Be Framework chains a step's destination with `#[Be(Next::class)]` written on the **current Being class**. The destination is therefore **class-level constant**: once you say "QuantityAdjusted goes to CartMerged", every flow that passes through `QuantityAdjusted` ends up at `CartMerged`.

When two transitions need the **same shape mid-chain** but **different Finals**, you cannot reuse one Being. Trying to add a `Branching` step or making the destination dynamic violates Be Framework's structural-typing philosophy — the framework explicitly avoids runtime dispatch on Being identity.

Examples from the BeMart migration:

- **Pilot 2 vs Pilot 10**: both adjust a cart-item quantity, but Pilot 2 (`doAddCartItem`) goes to a merge-by-add Final, Pilot 10 (`doUpdateCartItemQuantity`) goes to a replace Final. Same intermediate shape; different terminal behavior.
- **Pilot 4 vs Wave 5O**: customer registration via self-signup vs admin-creation. Shape of "validated customer about to be persisted" is identical; the Final is different (welcome email vs no email; AUTHZ-gated vs anonymous).

## Solution / Convention

**Input-per-intent + Being-per-shape**:

- One **Input** class per distinct intent (`doAddCartItem` vs `doUpdateCartItemQuantity`; `doRegisterCustomer` vs `doCreateCustomer`).
- One **Being** class per distinct intermediate shape *plus* destination pair. If two flows share a shape but split on destination, **duplicate the Being** with a name that signals the destination.

This is DRY-violating by line count but DRY-respecting by *intent*: the duplication is meaningful, because the two Beings really do head somewhere different.

Selection rule, in order:

1. Reuse the existing Being only if the destination Final is identical.
2. Otherwise, create a sibling Being class (`QuantityAdjusted` -> `QuantityReplacing`; `CustomerRegistering` -> `AdminCustomerCreating`) with its own `#[Be(...)]`.
3. Do **not** try to make the existing Being's destination dynamic via Branching or runtime dispatch.

## Code example

```php
// Pilot 2: doAddCartItem flow
//
//   AddCartItemInput -> QuantityAdjusted -> CartMerged (Final: ADD)
//
final class QuantityAdjusted
{
    public function __construct(/* ... */)
    {
        // intermediate: quantity capped against stock+saleLimit
    }
}
// And on QuantityAdjusted itself:
#[Be(CartMerged::class)]
final class QuantityAdjusted { /* ... */ }


// Pilot 10: doUpdateCartItemQuantity flow
//
//   UpdateCartItemQuantityInput -> CartItemQuantityReplacing -> CartItemQuantityUpdated (Final: REPLACE)
//
#[Be(CartItemQuantityUpdated::class)]
final class CartItemQuantityReplacing
{
    public function __construct(/* same shape as QuantityAdjusted */)
    {
        // intermediate: same logic, different downstream
    }
}
```

Wave 5O followed the same rule for admin customer creation:

```php
// Pilot 4 (self-signup):
#[Be(CustomerRegistered::class)]
final class CustomerRegistering { /* ... */ }

// Wave 5O (admin create) — sibling, not reuse:
#[Be(AdminCustomerCreated::class)]
final class AdminCustomerCreating { /* same shape as CustomerRegistering */ }
```

## Anti-pattern

```php
// WRONG #1 — reusing the Being because its shape matches:
#[Be(CartMerged::class)]   // hard-coded destination
final class QuantityAdjusted { /* ... */ }

// Pilot 10 tries to route through QuantityAdjusted... and lands at
// CartMerged (add-mode) instead of CartItemQuantityUpdated (replace-mode).
// Tests fail in unobvious ways because the merge logic ran on a replace.


// WRONG #2 — introducing dynamic dispatch inside the Being to "fix" reuse:
#[Be(CartMerged::class)]
final class QuantityAdjusted
{
    public function __construct(
        // ...
        string $mode,   // 'add' or 'replace'
    ) {
        // run-time branching here, defeating the point of #[Be]
    }
}
// This is the rejected option (A) from the discovery — it abandons Be
// Framework's structural typing for procedural dispatch.
```

## Where this matters

- Any time two transitions share an intermediate "shape" but diverge before the Final.
- Customer registration vs admin user creation.
- Cart-item add (merge by sum) vs cart-item update (replace) vs cart-item remove.
- Order placement (post-purchase) vs reorder (re-uses historical order data).

A useful heuristic: if you find yourself reaching for `if`/`switch` on a `$mode` parameter inside a Being, you have actually discovered a *second* Being. Name it for its destination, not for its parameters.

## Where this does NOT apply

When two flows share a Being **and** a Final, reuse is correct. The rule is "Being-per-shape **and destination**", not "Being-per-shape-unconditionally".

## Related

- **G-15** — pattern judgment for the Final at the end of the chain. G-17 is about the upstream Being; G-15 is about how many side-effects converge at the Final.
- **G-22** — same per-name wiring principle applied to Semantics: same numeric "shape" needs different classes when context differs.
