<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested blockId does not resolve (Wave 9).
 * Resource layer maps to HTTP 404.
 */
#[Message([
    'en' => 'The requested block was not found.',
    'ja' => '指定されたブロックは見つかりませんでした。',
])]
final class BlockNotFoundException extends DomainException
{
}
