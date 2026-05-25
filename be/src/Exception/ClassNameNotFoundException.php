<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested classNameId does not resolve to any row in
 * the class-name store (Wave 7). Same shape as
 * {@see CategoryNotFoundException}.
 */
#[Message([
    'en' => 'The requested class name was not found.',
    'ja' => '指定された規格名は見つかりませんでした。',
])]
final class ClassNameNotFoundException extends DomainException
{
}
