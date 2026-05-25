<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SessionPrefixFormatException;

use function trim;

/**
 * Cart session prefix — identifier prefix for the active cart partition.
 *
 * Non-empty. Combined with saleTypeId to form the cartKey
 * ({sessionPrefix}_{saleTypeId}).
 */
final class SessionPrefix
{
    #[Validate]
    public function validate(string $sessionPrefix): void
    {
        if (trim($sessionPrefix) === '') {
            throw new SessionPrefixFormatException();
        }
    }
}
