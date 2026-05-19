<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested paymentMethodId does not resolve to any row
 * in the admin payment-method master (Wave 9θ).
 */
#[Message([
    'en' => 'The requested payment method was not found.',
    'ja' => '指定された支払方法は見つかりませんでした。',
])]
final class PaymentMethodAdminNotFoundException extends DomainException
{
}
