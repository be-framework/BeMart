<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested newsId does not resolve (Wave 9).
 * Resource layer maps to HTTP 404.
 */
#[Message([
    'en' => 'The requested news post was not found.',
    'ja' => '指定されたニュースは見つかりませんでした。',
])]
final class NewsNotFoundException extends DomainException
{
}
