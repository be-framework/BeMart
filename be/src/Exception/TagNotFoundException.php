<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested tagId does not resolve (Wave 9).
 */
#[Message([
    'en' => 'The requested tag was not found.',
    'ja' => '指定されたタグは見つかりませんでした。',
])]
final class TagNotFoundException extends DomainException
{
}
