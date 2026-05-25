<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

final class NoopPaymentGateway implements PaymentGatewayInterface
{
    #[Override]
    public function checkout(string $preOrderId, int $paymentMethodId, int $amount): void
    {
    }
}
