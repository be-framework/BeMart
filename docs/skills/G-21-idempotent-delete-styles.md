---
layout: default
title: "G-21: Idempotent DELETE has two styles — silent (200 + `alreadyAbsent`) vs 404-on-miss"
---

# G-21: Idempotent DELETE has two styles — silent (200 + `alreadyAbsent`) vs 404-on-miss

## Context

Discovered during Wave 6P (`doDeleteCustomerAddress`) of the EC-CUBE -> Be Framework migration, by contrast with Pilot 11 (`doRemoveCartItem`) and Pilot 13 / Wave 6Q (`doRemoveFavorite`). Three DELETE-shaped transitions had landed by Wave 6 with two visibly different behaviors on "delete something that is already gone". Wave 6 made the discrepancy a deliberate, documented choice.

## Problem

HTTP DELETE is required to be idempotent: repeated calls must leave the server in the same state. That constraint is satisfied by *both* of the following responses for "the row is already gone":

- **200 OK** with a body field like `alreadyAbsent: true` (silent idempotent).
- **404 Not Found** (precise feedback to an authenticated caller; idempotent because state is unchanged either way).

If a project mixes the two without rule, frontends end up writing case-by-case handling and engineers debate it in every new DELETE PR.

## Solution / Convention

Pick the style by **caller expectation**, not by "what feels RESTful":

| Caller expectation | Style | HTTP status | Rationale |
|---|---|---|---|
| "I am toggling state; double-tap is normal UX." | **Silent idempotent** | 200 + `alreadyAbsent: true` (or similar flag) | A second tap on "favorite" or "follow" should not look like an error; UI can read the flag if it wants to distinguish |
| "I expect the row to be there; if not, I want to know." | **404 on miss** | 404 | An authenticated caller editing their own cart / address has a precise mental model; surfacing the absence helps detect double-submits, stale links, or UI desync |

**Routing rule**: ask "is this the rare-but-OK path?" If yes (favorite re-click, mute already muted), silent. If "absence is unexpected" (cart-item that the user just looked at moments ago; address book entry the user picked from a list), 404.

Both styles satisfy idempotency. The choice is about UX clarity, not protocol semantics.

## Code example

**Silent idempotent (favorite, like, follow):**

```php
// be/src/Final/FavoriteRemoved.php
final class FavoriteRemoved
{
    public bool $alreadyAbsent;

    public function __construct(
        string $customerId,
        string $productCode,
        FavoriteStorageInterface $storage,
    ) {
        $existed = $storage->existsByCustomerAndProduct($customerId, $productCode);
        $storage->removeByCustomerAndProduct($customerId, $productCode); // no-op if absent
        $this->alreadyAbsent = ! $existed;
    }
}

// Resource:
final class Favorite extends ResourceObject
{
    public function onDelete(string $productCode): self
    {
        $final = $this->becoming->__invoke(/* ... */);
        $this->code = 200; // OK either way
        $this->body = ['alreadyAbsent' => $final->alreadyAbsent];
        return $this;
    }
}
```

**404 on miss (cart-item, address-book entry):**

```php
// be/src/Final/CartItemRemoved.php
final class CartItemRemoved
{
    public function __construct(
        string $sessionPrefix,
        string $productCode,
        CartQueryInterface $cartQuery,
        CartCommandInterface $cartCommand,
    ) {
        $cart = $cartQuery->findContaining($sessionPrefix, $productCode);
        if ($cart === null) {
            throw new CartItemNotInCartException($productCode);
        }
        $cartCommand->removeItem($cart, $productCode);
    }
}

// Resource maps CartItemNotInCartException -> Code::NOT_FOUND (404).
```

## Anti-pattern

```php
// WRONG #1 — silent everywhere "for consistency":
//
// User edits an address book entry, hits "delete" twice by accident
// because the second tap landed on a now-empty row. Server returns 200
// both times. User has no way to tell their second tap missed a row
// that the UI rearranged. Double-submit detection is now a UI-only
// problem.
```

```php
// WRONG #2 — 404 everywhere "for precision":
//
// Mobile UI's "favorite" button is a toggle. The user hits it twice
// in quick succession; the second request arrives after the first
// already removed the row. Server returns 404. UI shows "not found"
// for what is a normal toggle interaction.
```

```php
// WRONG #3 — picking the style ad-hoc per endpoint:
//
// Two engineers write `doRemoveFavorite` (silent) and
// `doDeleteCustomerAddress` (404 on miss). A third writes
// `doRemoveCartItem` and has to guess; they pick silent because
// the most recent example was silent. Now cart-item delete is
// inconsistent with address-book delete and the project has no rule.
```

## Where this matters

- Every DELETE endpoint in a REST API.
- "Toggle" UI elements (favorite, like, follow, mute, subscribe) -> silent.
- "List item" UI elements where absence is unexpected (cart, address book, saved-search list) -> 404.
- Admin soft-delete with audit log -> typically 404 on miss (admin tools expect precise feedback).

A useful sanity check: would a debounce on the client be the right fix if the user sees double-tap weirdness? If yes (favorite toggle), silent is right. If no (cart-item edit), 404 is right.

## Related

- (No direct skill-gap dependency; G-21 is a standalone API convention.)
- Tangentially related to **G-17** because separate intents (silent-remove vs 404-on-miss) often correspond to separate Input + Being / Final classes anyway.
