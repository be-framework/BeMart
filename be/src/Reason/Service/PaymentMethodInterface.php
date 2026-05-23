<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification;

/**
 * Payment plugin contract — EC-CUBE's PaymentMethodInterface narrowed to the
 * single concern doConfirmOrder needs: `verify()`.
 *
 * verify() is "may we proceed to the confirm screen?" — e.g. credit card
 * validity, available balance, fraud screening. checkout() and the Symfony
 * Workflow status transitions live in the next Pilot (doCheckout).
 */
interface PaymentMethodInterface
{
    public function verify(OrderEntity $preOrder): PaymentVerification;
}
