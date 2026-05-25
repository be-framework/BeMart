<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'The payment was declined by the payment gateway.',
    'ja' => '決済が承認されませんでした。',
])]
final class PaymentDeclinedException extends DomainException
{
}
