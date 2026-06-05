---
layout: default
title: "G-19: Admin AAA is a parallel firewall — separate `AdminSessionInterface` from `SessionInterface`"
---

# G-19: Admin AAA is a parallel firewall — separate `AdminSessionInterface` from `SessionInterface`

## Context

Discovered during Wave 4 of the EC-CUBE -> Be Framework migration. EC-CUBE itself uses Symfony's `security.firewalls` with two separate firewalls (`admin` and `customer`); the question was whether the Be-layer session abstraction should mirror that split or unify both principal types behind one `SessionInterface`.

## Problem

Unifying admin + customer into a single `SessionInterface` (typically with a `role` field or distinct `adminId()` / `customerId()` methods on the same interface) creates three structural risks:

1. **AUTHZ short-circuit accidents** — a null check intended to validate one role can silently accept the other. `if (!$session->id()) throw` does not distinguish "logged-in customer at admin endpoint" from "anonymous".
2. **Ambiguous audit logs** — when both ids live on one object, log entries record "principal X did Y" without role context. A later forensic question ("did a customer trigger this admin-only path?") becomes hard to answer.
3. **Muddied HTTP status mapping** — admin-only endpoints have *two* failure modes: anonymous request (`UNAUTHORIZED`) vs logged-in-customer request (`FORBIDDEN`, role mismatch). A unified interface tends to collapse both into one status code.

## Solution / Convention

**Two distinct interfaces, one per principal class:**

```php
interface SessionInterface
{
    /** @return non-empty-string|null  customerId, or null if no customer is logged in */
    public function customerId(): string|null;
}

interface AdminSessionInterface
{
    /** @return non-empty-string|null  adminId, or null if no admin is logged in */
    public function adminId(): string|null;
}
```

Each Reason / Final injects **only** the interface it needs. A customer-side flow takes `SessionInterface`; an admin-side flow takes `AdminSessionInterface`. The two are never used in the same constructor — that would itself be a design smell suggesting role confusion.

Resource-level status mapping then has a clean three-way split:

| Caller state at admin endpoint | Be exception | HTTP code |
|---|---|---|
| Anonymous | `UnauthenticatedException` | 401 `UNAUTHORIZED` |
| Logged-in customer (no admin id) | `UnauthorizedAdminAccessException` | 403 `FORBIDDEN` |
| Logged-in admin | (pass through) | 200 |

The same flow at a customer endpoint distinguishes "no customer logged in" from "logged-in admin who is not also a customer".

## Code example

```php
// be/src/Reason/Service/AdminSessionInterface.php  (Wave 4)

/**
 * AdminSession — the AAA boundary for "which admin is making this
 * request" (parallel firewall to SessionInterface for customers).
 *
 * The two interfaces are intentionally NOT unified: EC-CUBE itself
 * uses Symfony's security.firewalls with two separate firewalls
 * (admin + customer), and admins / customers are distinct AAA
 * principal classes. A logged-in customer is NOT logged-in-as-admin,
 * and vice versa.
 */
interface AdminSessionInterface
{
    /**
     * @return non-empty-string|null  adminId, or null if no admin is logged in
     * @psalm-taint-source session
     */
    public function adminId(): string|null;
}

// Wave 5 admin Resource using the dedicated interface:
final class CustomerList extends ResourceObject
{
    public function onGet(/* ... */): self
    {
        $finalized = $this->becoming->__invoke(new ListCustomersInput(/* ... */));
        // The Being chain consults AdminSessionInterface inside its AUTHZ
        // Reason; here the Resource only maps domain exceptions to HTTP.
        return $this;
    }
}
```

## Anti-pattern

```php
// WRONG — one interface with a role discriminator.
interface SessionInterface
{
    public function id(): ?string;
    public function role(): ?string;   // 'admin' | 'customer' | null
}

// Now every Reason has to write the wrong shape of check:
if ($session->role() !== 'admin') {
    throw new ForbiddenException();
}
// vs the correct version that the parallel-interface form makes natural:
$adminId = $this->adminSession->adminId();
if ($adminId === null) {
    throw new UnauthorizedAdminAccessException();
}

// The unified form is also Psalm-unfriendly: the `role()` string is a
// stringly-typed enum that fans out to dozens of equality checks.
```

```php
// EQUALLY WRONG — one interface with two methods sharing nullability.
interface SessionInterface
{
    public function customerId(): ?string;
    public function adminId(): ?string;
}
// Same instance returns both. A flow that asks for customerId() can
// accidentally accept an admin session that happens to also have a
// customerId set, and vice versa. The Reason cannot tell whether it
// was injected the "customer view" or the "admin view" of the principal.
```

## Where this matters

- Any application with two or more principal classes (customer / admin / merchant / partner).
- Reasons that perform AUTHZ — they should accept the smallest possible session interface, not a god-object.
- BEAR Resources mapping domain exceptions to HTTP status — the split lets `UNAUTHORIZED` (401) and `FORBIDDEN` (403) be assigned cleanly without ambiguity.
- Taint analysis — each session source can have its own `@psalm-taint-source session` marker, letting role-specific sinks be checked independently.

The wiring cost is small: two bindings instead of one. Production adapters bridge each to the upstream firewall (Symfony `security.firewalls.admin` vs `security.firewalls.customer`).

## Related

- **G-18** — when admin AAA infrastructure has missing ALPS descriptors (Wave 4 `doAdminLogin`), apply the ALPS-absent-transition protocol while introducing the parallel firewall.
- **G-14 / G-20** — the AdminSession Fake in tests is the same kind of stateful Fake covered there; the same `toInstance`-on-both-keys binding applies.
