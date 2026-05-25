<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification;
use Override;

final class DefaultPaymentMethodFactory implements PaymentMethodFactoryInterface
{
    #[Override]
    public function methodFor(int $paymentMethodId): PaymentMethodInterface
    {
        return new readonly class implements PaymentMethodInterface {
            #[Override]
            public function verify(OrderEntity $preOrder): PaymentVerification
            {
                return new PaymentVerification(success: true);
            }
        };
    }

    /** @return list<array{paymentMethodId: int, paymentMethodName: string}> */
    #[Override]
    public function available(): array
    {
        return [
            ['paymentMethodId' => 1, 'paymentMethodName' => '代金引換'],
            ['paymentMethodId' => 2, 'paymentMethodName' => 'クレジットカード'],
        ];
    }
}
