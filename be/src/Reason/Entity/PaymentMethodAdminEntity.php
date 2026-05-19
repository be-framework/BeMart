<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin-side payment-method master row — projection of EC-CUBE
 * dtb_payment (Wave 9θ shop settings slice).
 *
 * Stores the editable master record an administrator manipulates via
 * the Payment CRUD endpoints. The customer-facing checkout factory
 * ({@see \MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface})
 * is a separate runtime concern that — in the production wiring — will
 * read from the same underlying table. They are deliberately kept as
 * two interfaces so the admin layer can evolve fields without
 * destabilising the runtime checkout dispatcher.
 *
 *   - paymentId  : opaque server-generated identifier
 *   - paymentMethodName: display name (e.g. "クレジットカード")
 *   - charge           : transaction-fee in JPY (>= 0)
 *   - ruleMin          : lower order-total bound; null = no lower bound
 *   - ruleMax          : upper order-total bound; null = no upper bound
 *   - visible          : true = surfaced on the front, false = soft hidden
 *
 * The soft `visible` flag is what `doDeletePayment` flips in Phase 1 —
 * EC-CUBE logically deletes payment masters to preserve historical
 * order snapshots.
 */
final readonly class PaymentMethodAdminEntity
{
    public function __construct(
        public string $paymentId,
        public string $paymentMethodName,
        public int $charge,
        public int|null $ruleMin,
        public int|null $ruleMax,
        public bool $visible,
    ) {
    }
}
