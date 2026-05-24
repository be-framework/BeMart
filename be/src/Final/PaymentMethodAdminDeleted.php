<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PaymentMethodAdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Payment method deleted — Final, proof one master row was removed
 * (Wave 9θ).
 *
 *   DeletePaymentMethodAdminInput → PaymentMethodAdminDeleted
 *     (Direct, idempotent)
 *
 * ALPS doc note: real EC-CUBE applies a "logical delete" (visible =
 * false) so existing order snapshots stay readable. The in-memory
 * store does NOT model that nuance in this first iteration — it just
 * drops the row. Phase 2 can swap to soft-delete when the consumer
 * for historical orders is wired.
 */
final readonly class PaymentMethodAdminDeleted
{
    public string $paymentId;

    public function __construct(
        #[Input] string $paymentId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PaymentMethodAdminStorageInterface $payments,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($payments->item($paymentId) === null) {
            throw new PaymentMethodAdminNotFoundException();
        }

        $payments->delete($paymentId);

        $this->paymentId = $paymentId;
    }
}
