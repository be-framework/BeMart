<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested categoryId does not resolve to any row in
 * the category store (Wave 7 catalog hierarchy). Same shape as
 * {@see AddressNotFoundException}: a legitimate admin caller asking
 * about a stale id gets a 404, anonymous-as-admin callers never reach
 * this code path because {@see UnauthorizedAdminAccessException} fires
 * first.
 *
 * Resource layer maps to HTTP 404.
 */
#[Message([
    'en' => 'The requested category was not found.',
    'ja' => '指定されたカテゴリは見つかりませんでした。',
])]
final class CategoryNotFoundException extends DomainException
{
}
