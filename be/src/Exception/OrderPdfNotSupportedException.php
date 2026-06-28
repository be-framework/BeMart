<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Order PDF export is not supported in this build.',
    'ja' => '納品書PDF出力はこのビルドでは利用できません。',
])]
final class OrderPdfNotSupportedException extends DomainException
{
}
