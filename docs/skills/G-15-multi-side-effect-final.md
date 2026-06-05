---
layout: default
title: "G-15: Multi-side-effect Final (Complex Convergence) judgment criteria"
---

# G-15: Multi-side-effect Final (Complex Convergence) judgment criteria

## Context

Discovered during Pilot 5 (`doCheckout`) of the EC-CUBE -> Be Framework migration. Before Pilot 5, every Final invoked exactly one `*Command` Reason. `doCheckout` required three: `OrderCommand.register` + `Mailer.sendOrderConfirmation` + `CartCommand.clearByPreOrderId`. The question was: do we collapse all three into one Final (Multi-side-effect Final), or chain a series of single-side-effect Finals (Cascade Final)?

## Problem

Two failure modes flank the wrong choice:

1. **Collapsing too eagerly** — three side-effects that *do* depend on each other's results end up in one constructor; an inner failure leaves the system in a partial-commit state with no clean handoff to a subsequent step.
2. **Cascading too eagerly** — three side-effects that *don't* depend on each other balloon into three Final classes plus intermediate value objects, obscuring intent and doubling the test surface.

## Solution / Convention

Use a **Multi-side-effect Final** (one Final that fires N side-effects from a single constructor) when **both** hold:

- The side-effects have an obvious ordering by purpose (record-of-truth first, cleanup last) — but the order is for hygiene, not because step N reads step N-1's return.
- No side-effect's result is the input of another side-effect. Each takes data already captured by the upstream Being chain.

Use a **Cascade Final** (chain of single-side-effect Finals) when:

- Three or more side-effects exist AND at least one consumes another's result. Then the producer must surface its result as a typed value object that the next Final consumes.

The first criterion is the discriminator. Order-for-hygiene is fine inside one Final; data dependency between side-effects forces a cascade.

## Code example

```php
// be/src/Final/CheckoutCompleted.php (BeMart Pilot 5)

final class CheckoutCompleted
{
    public function __construct(
        // ... data already aggregated by CheckoutSettled (Being) ...
        OrderCommandInterface $orderCommand,
        MailerInterface       $mailer,
        CartCommandInterface  $cartCommand,
    ) {
        // 1. Record of truth FIRST — durable state must land before any
        //    transient action that could be retried.
        $orderCommand->register($finalizedOrder);

        // 2. Notification — no other side-effect reads its result, but it
        //    happens after persistence so a failure here does not orphan
        //    a never-persisted order.
        $mailer->sendOrderConfirmation($customerEmail, $orderNo);

        // 3. Cleanup LAST — losing cart-clear is the safest failure mode
        //    (a stale cart is harmless; a non-persisted order is not).
        $cartCommand->clearByPreOrderId($preOrderId);
    }
}
```

This is **Complex Convergence** in `be-patterns` `loan-application` terms: multiple Reason streams flow into one Final, the Final orders them by purpose, and none reads from another.

## Anti-pattern

```php
// WRONG — three Finals chained because of a phobia of "doing too much in one place":
//   CheckoutPersisted -> CheckoutMailed -> CheckoutCartCleared
//
// You now have two intermediate value objects (PersistedOrder, MailedOrder)
// whose only purpose is to pass data through. The cart-clear Final must
// reach back to data already captured upstream, but the chain has hidden
// it behind two opaque hops. Test surface triples; semantic clarity drops.

// EQUALLY WRONG — one Final that DOES need a cascade:
//   final class OrderProcessed {
//       public function __construct(
//           PaymentGatewayInterface $gateway,
//           OrderNoProvider $numbers,
//           OrderCommandInterface $orderCommand,
//       ) {
//           $auth      = $gateway->capture(...);   // result feeds...
//           $orderNo   = $numbers->generate($auth->id); // ...this step
//           $orderCommand->register($orderNo, $auth);   // ...and this
//       }
//   }
// This *should* be a Cascade Final because each step's output is the next
// step's input. The Multi-side-effect form hides the chain dependency.
```

## Where this matters

- **Be Framework Final design** — every Final-design decision should explicitly answer "does any side-effect read another's result?".
- **Order processing, checkout, registration completion** patterns where a single domain event triggers persistence + notification + cleanup.
- Compare against `be-patterns/demos/loan-application` (Complex Convergence reference) and `be-patterns/demos/order-processing` (Diamond reference).

Companion concern: side-effect ordering also implies an exception contract — mailers and cleanup commands in a Multi-side-effect Final should be **non-throwing on transient failure** (log + swallow) so a successful record-of-truth is never undone by a notification glitch.

## Related

- **G-14** — the Ray.Di binding lesson that surfaced because Multi-side-effect Final needs three stateful Fakes wired correctly at once.
- **G-16** — the broader "partial-commit window" problem that Multi-side-effect Finals open, and how to close it.
