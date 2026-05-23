# G-14: Ray.Di `bind(Iface)->to(Impl)` does NOT consult `bind(Impl)->in(SINGLETON)`

## Context

Discovered during Pilot 5 (`doCheckout`) of the EC-CUBE -> Be Framework migration, while wiring stateful Fakes (`FakeMailer`, `FakePaymentGateway`, `FakeInventoryAllocator`) that both the Becoming chain and test introspection need to see as the **same** instance.

## Problem

When a Fake (or any class) holds in-memory state, two pieces of code typically need to reach it:

1. The Becoming chain resolves it via the **interface** (`MailerInterface`).
2. The test asserts on captured state via the **concrete class** (`FakeMailer`).

The naive Ray.Di pattern that "looks right":

```php
$this->bind(MailerInterface::class)->to(FakeMailer::class);
$this->bind(FakeMailer::class)->in(Scope::SINGLETON);
```

silently produces **two** instances. The `to(FakeMailer::class)` is a **linked binding** — it instantiates a fresh `FakeMailer` independent of the `bind(FakeMailer::class)` scope. The test sees an empty `FakeMailer::$sent[]` even though the Becoming chain sent a mail through the interface-resolved instance.

Symptom: tests that assert "mailer was called once" fail with `assertCount(1, $mailer->sent) — Failed asserting that an array has 1 elements (0 found)`.

## Solution / Convention

Bind a single object reference to **both** keys via `toInstance`:

```php
$mailer = new FakeMailer();
$this->bind(FakeMailer::class)->toInstance($mailer);
$this->bind(MailerInterface::class)->toInstance($mailer);
```

This forces both lookups to return `===`-identical objects. Use this whenever:

- the Fake/Impl holds in-memory state (captures, queues, counters, caches), AND
- code resolves it via *both* the interface and the concrete class.

If only the interface side is consulted (Storage classes used exclusively by Commands), the simple `bind(Iface)->to(Impl); bind(Impl)->in(SINGLETON)` pattern still works — because no other code reaches the Impl key.

Keep Fake implementations out of the contract namespace:

```txt
Reason/
  Query/                 # interfaces, factories, BDR/Param types
  Service/               # interfaces and production-neutral services
  Fake/
    Query/               # Fake* Query/Storage/Command implementations
    Service/             # Fake* service/generator implementations
```

`Reason\Query` and `Reason\Service` should read as the domain/infra boundary. Concrete dev/test doubles belong under `Reason\Fake\Query` or `Reason\Fake\Service`, while remaining in `be/src` because the dev/test `AppModule` uses them as real application implementations.

## Code example

```php
// src/Module/AppModule.php (BeMart)

// Stateful Fakes — must share one instance across Iface and Impl bindings.
$inventory = new FakeInventoryAllocator();
$gateway   = new FakePaymentGateway();
$mailer    = new FakeMailer();

$this->bind(FakeInventoryAllocator::class)->toInstance($inventory);
$this->bind(InventoryAllocatorInterface::class)->toInstance($inventory);

$this->bind(FakePaymentGateway::class)->toInstance($gateway);
$this->bind(PaymentGatewayInterface::class)->toInstance($gateway);

$this->bind(FakeMailer::class)->toInstance($mailer);
$this->bind(MailerInterface::class)->toInstance($mailer);

// Stateless or Becoming-only references — singleton-on-Impl is enough.
$this->bind(FakeFinalizedOrderStorage::class)->in(Scope::SINGLETON);
$this->bind(OrderCommandInterface::class)->to(FakeOrderCommand::class);
```

## Anti-pattern

```php
// Looks correct, silently broken for stateful Fakes:
$this->bind(MailerInterface::class)->to(FakeMailer::class);
$this->bind(FakeMailer::class)->in(Scope::SINGLETON);

// Even adding ->in(SINGLETON) to the linked binding does NOT fix it —
// you get two distinct singletons, one keyed on Iface, one keyed on Impl.
$this->bind(MailerInterface::class)->to(FakeMailer::class)->in(Scope::SINGLETON);
$this->bind(FakeMailer::class)->in(Scope::SINGLETON);
```

## Where this matters

- **Be Framework Fake Reasons** that hold state (captures, in-memory storage, counters).
- Any DI-driven test that introspects a Fake via the concrete class name while production code resolves the interface.
- Production wrappers that hold state too (idempotency cache, connection pool, metrics counter) — same rule applies.

Production adapters that delegate to external services (real SMTP, real gateway HTTP client) are typically stateless — the simple `bind(Iface)->to(Impl)->in(SINGLETON)` pattern is enough there.

## Related

- **G-20** — the cross-session rebind variant of this same rule. When tests rebind sessions per test case, `toInstance` must be preserved on **both** keys at rebind time too, or the storage diverges across the test's Injector instances.
- **G-15** — Multi-side-effect Final pattern is what made G-14 visible: when one Final drives three independent stateful side-effects, three Fakes break simultaneously.
