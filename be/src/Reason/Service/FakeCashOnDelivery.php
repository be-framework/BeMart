<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification;
use Override;

/**
 * Pilot 3 fake — 代金引換. verify() always succeeds (no remote validation).
 */
final class FakeCashOnDelivery implements PaymentMethodInterface
{
    #[Override]
    public function verify(OrderEntity $preOrder): PaymentVerification
    {
        return new PaymentVerification(success: true);
    }
}
