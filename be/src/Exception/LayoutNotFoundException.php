<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested layoutId does not resolve (Wave 9).
 * Resource layer maps to HTTP 404.
 */
#[Message([
    'en' => 'The requested layout was not found.',
    'ja' => '指定されたレイアウトは見つかりませんでした。',
])]
final class LayoutNotFoundException extends DomainException
{
}
