<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerifyResult;
use Override;

/**
 * Pilot 3 fake — verify() always returns failure with a fixed reason. Used
 * to exercise the branching path (OrderConfirming → OrderConfirmFailed).
 */
final class FakeVerifyFailing implements PaymentMethodInterface
{
    #[Override]
    public function verify(OrderEntity $preOrder): PaymentVerifyResult
    {
        return new PaymentVerifyResult(
            success: false,
            errors: ['Card validation failed'],
        );
    }
}
