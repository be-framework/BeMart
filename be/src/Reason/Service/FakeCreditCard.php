<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerifyResult;
use Override;

/**
 * Pilot 3 fake — クレジットカード. verify() succeeds in the Pilot scope;
 * a real plugin would talk to the gateway and could fail.
 */
final class FakeCreditCard implements PaymentMethodInterface
{
    #[Override]
    public function verify(OrderEntity $preOrder): PaymentVerifyResult
    {
        return new PaymentVerifyResult(success: true);
    }
}
