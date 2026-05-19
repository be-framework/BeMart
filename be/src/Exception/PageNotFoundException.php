<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested pageId does not resolve to any row in
 * the page store (Wave 9 CMS slice). Resource layer maps to HTTP 404.
 */
#[Message([
    'en' => 'The requested page was not found.',
    'ja' => '指定されたページは見つかりませんでした。',
])]
final class PageNotFoundException extends DomainException
{
}
