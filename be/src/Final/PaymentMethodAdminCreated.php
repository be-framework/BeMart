<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\PaymentMethodAdminIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Payment method created — Final, proof a new payment-method master row
 * was persisted (Wave 9θ).
 *
 *   CreatePaymentMethodAdminInput → PaymentMethodAdminCreated
 *     (Direct, admin AUTHZ)
 */
final readonly class PaymentMethodAdminCreated
{
    public string $paymentId;
    public string $paymentMethodName;
    public int $charge;
    public int|null $ruleMin;
    public int|null $ruleMax;
    public bool $visible;

    public function __construct(
        #[Input] string $paymentMethodName,
        #[Input] int $charge,
        #[Input] int|null $ruleMin,
        #[Input] int|null $ruleMax,
        #[Input] bool $visible,
        #[Inject] AdminSession $adminSession,
        #[Inject] PaymentMethodAdminStorageInterface $payments,
        #[Inject] PaymentMethodAdminIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new PaymentMethodAdminEntity(
            paymentId: $ids->get(),
            paymentMethodName: $paymentMethodName,
            charge: $charge,
            ruleMin: $ruleMin,
            ruleMax: $ruleMax,
            visible: $visible,
        );

        $payments->put($entity);

        $this->paymentId = $entity->paymentId;
        $this->paymentMethodName = $entity->paymentMethodName;
        $this->charge = $entity->charge;
        $this->ruleMin = $entity->ruleMin;
        $this->ruleMax = $entity->ruleMax;
        $this->visible = $entity->visible;
    }
}
