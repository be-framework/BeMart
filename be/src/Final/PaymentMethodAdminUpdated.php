<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PaymentMethodAdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Payment method updated — Final, proof one master row was edited in
 * place (Wave 9θ).
 *
 *   UpdatePaymentMethodAdminInput → PaymentMethodAdminUpdated
 *     (Direct, idempotent)
 *
 * AUTHZ ladder:
 *   1. No admin session → UnauthorizedAdminAccessException     (403)
 *   2. Unknown id       → PaymentMethodAdminNotFoundException  (404)
 *
 * Null body fields preserve the current persisted value — same
 * convention as {@see ClassNameUpdated}. Real EC-CUBE keeps the
 * existing order snapshots untouched, so this update is master-only.
 */
final readonly class PaymentMethodAdminUpdated
{
    public string $paymentId;
    public string $paymentMethodName;
    public int $charge;
    public int|null $ruleMin;
    public int|null $ruleMax;
    public bool $visible;

    public function __construct(
        #[Input] string $paymentId,
        #[Input] string|null $paymentMethodName,
        #[Input] int|null $charge,
        #[Input] int|null $ruleMin,
        #[Input] int|null $ruleMax,
        #[Input] bool|null $visible,
        #[Inject] AdminSession $adminSession,
        #[Inject] PaymentMethodAdminStorageInterface $payments,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $payments->item($paymentId);
        if ($current === null) {
            throw new PaymentMethodAdminNotFoundException();
        }

        $merged = new PaymentMethodAdminEntity(
            paymentId: $current->paymentId,
            paymentMethodName: $paymentMethodName ?? $current->paymentMethodName,
            charge: $charge ?? $current->charge,
            ruleMin: $ruleMin ?? $current->ruleMin,
            ruleMax: $ruleMax ?? $current->ruleMax,
            visible: $visible ?? $current->visible,
        );

        $payments->put($merged);

        $this->paymentId = $merged->paymentId;
        $this->paymentMethodName = $merged->paymentMethodName;
        $this->charge = $merged->charge;
        $this->ruleMin = $merged->ruleMin;
        $this->ruleMax = $merged->ruleMax;
        $this->visible = $merged->visible;
    }
}
