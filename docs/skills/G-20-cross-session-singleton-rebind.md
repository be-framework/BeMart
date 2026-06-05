---
layout: default
title: "G-20: Cross-session rebind requires `toInstance` on both Iface and Impl bindings"
---

# G-20: Cross-session rebind requires `toInstance` on both Iface and Impl bindings

## Context

Discovered during Wave 6P (`doCreateCustomerAddress` / `doUpdateCustomerAddress` AUTHZ tests) of the EC-CUBE -> Be Framework migration. AUTHZ tests need to assert "Alice writes; Bob reads back the same row but is rejected with 403". To do that, the test rebinds `SessionInterface` between calls — first as Alice, then as Bob. The storage must persist across the rebind, or the AUTHZ assertion is testing nothing.

## Problem

When a test rebinds the session interface to simulate a different principal mid-test:

```php
// Alice writes
$module->bind(SessionInterface::class)->toInstance(new FakeSession('alice'));
$injector1 = new Injector($module);
$injector1->getInstance(BecomingInterface::class)->__invoke($aliceInput);

// Bob reads
$module->bind(SessionInterface::class)->toInstance(new FakeSession('bob'));
$injector2 = new Injector($module);
$injector2->getInstance(BecomingInterface::class)->__invoke($bobInput);
```

A naive Storage binding (`bind(FakeAddressStorage::class)->in(Scope::SINGLETON)`) gives each Injector its **own** `FakeAddressStorage` singleton. Alice's write happens in Injector #1's storage; Bob's read happens in Injector #2's storage. Bob sees an empty store — the AUTHZ check accidentally returns 404 instead of 403, and the test passes for the wrong reason.

This is **G-14** (the `bind(Iface)->to(Impl)` linked-binding gotcha) re-emerging across Injector boundaries.

## Solution / Convention

Bind one **explicitly constructed** storage object to **both** the Iface and the Impl key, then preserve that object reference across all per-test rebinds:

```php
// In test setUp (or a helper):
$storage = new FakeAddressStorage();
$module->bind(AddressStorageInterface::class)->toInstance($storage);
$module->bind(FakeAddressStorage::class)->toInstance($storage);

// Later, when rebinding the session, do NOT touch the storage bindings.
// The storage instance survives across Injector instantiations because
// both bindings point to the same PHP object reference held by the test.
$module->bind(SessionInterface::class)->toInstance(new FakeSession('alice'));
$injector1 = new Injector($module);
// ... Alice writes ...

$module->bind(SessionInterface::class)->toInstance(new FakeSession('bob'));
$injector2 = new Injector($module);
// ... Bob reads — the same $storage object answers both Injectors.
```

The rule: **for any storage that AUTHZ tests need to span session rebinds, both Iface and Impl must use `toInstance($shared)` of the same explicitly-held object reference.**

This is the cross-session variant of G-14. G-14 covers "one request needs the Iface lookup and the Impl lookup to be the same object"; G-20 covers "two requests, in two Injectors built from the same Module, need to see the same storage".

## Code example

```php
// tests/Resource/AddressResourceTest.php  (Wave 6P)

protected function setUp(): void
{
    // Build once, reuse across all rebinds in this test class.
    $this->addressStorage = new FakeAddressStorage();
    $this->module = new AppModule();
    $this->module->bind(AddressStorageInterface::class)
        ->toInstance($this->addressStorage);
    $this->module->bind(FakeAddressStorage::class)
        ->toInstance($this->addressStorage);
}

private function rebindSession(string $customerId): Injector
{
    // CRITICAL: do not rebuild the module or the storage. Only the
    // session binding rotates. The shared storage instance survives.
    $this->module->bind(SessionInterface::class)
        ->toInstance(new FakeSession($customerId));
    return new Injector($this->module);
}

public function testForbidsCrossCustomerEdit(): void
{
    // Alice creates the address...
    $alice = $this->rebindSession('alice-001');
    $alice->getInstance(BecomingInterface::class)
        ->__invoke(new CreateAddressInput(/* ... */));

    // Bob attempts to edit Alice's address — same storage, different session.
    $bob = $this->rebindSession('bob-002');
    $this->expectException(UnauthorizedAddressAccessException::class);
    $bob->getInstance(BecomingInterface::class)
        ->__invoke(new UpdateAddressInput($aliceAddressId, /* ... */));
}
```

## Anti-pattern

```php
// WRONG — naive singleton:
$module->bind(FakeAddressStorage::class)->in(Scope::SINGLETON);
$module->bind(AddressStorageInterface::class)->to(FakeAddressStorage::class);

// Each Injector built from this Module creates ITS OWN singleton
// FakeAddressStorage (G-14). Cross-session rebind tests silently
// pass because both Alice and Bob see empty stores.
```

```php
// ALSO WRONG — toInstance on Iface only:
$storage = new FakeAddressStorage();
$module->bind(AddressStorageInterface::class)->toInstance($storage);
$module->bind(FakeAddressStorage::class)->in(Scope::SINGLETON);

// The Becoming chain resolves the Iface and sees $storage.
// Test assertions that introspect via the concrete class
// ($injector->getInstance(FakeAddressStorage::class)) see a different
// instance per Injector. AUTHZ-cross-session assertions break.
```

## Where this matters

- AUTHZ tests where two principals operate on shared state ("Alice creates, Bob can't edit").
- Cross-session tests in general — anywhere a single test exercises multiple session principals against persistent state.
- Production adapters that hold per-request state and need to survive request boundaries in tests (rate-limit counters, idempotency caches).

A useful invariant for the test suite:

> Any storage interface that appears in an AUTHZ test must be bound via `toInstance` on both the Iface and the concrete class.

This can be enforced by a one-shot grep in CI:

```bash
grep -rn "bind(.*StorageInterface" tests/  # all bindings should be ->toInstance
```

## Related

- **G-14** — the parent rule. G-14 is the single-Injector form; G-20 is the multi-Injector / cross-session form.
- **G-19** — admin AAA tests will need the same pattern with `AdminSessionInterface` rebinds; the rule generalizes to any session-like role-rotating test.
