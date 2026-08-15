<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'This pre-order is already being completed by another request.',
    'ja' => 'この仮注文は既に別のリクエストで確定処理に入っています。',
])]
final class PreOrderAlreadyClaimedException extends DomainException
{
}
