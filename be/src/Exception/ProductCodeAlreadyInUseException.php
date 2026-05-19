<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Wave 8 (doCreateProduct, doCopyProduct) — raised when an admin tries
 * to create or copy a product into an already-occupied productCode.
 *
 * ALPS `doCreateProduct.type=unsafe` and `doCopyProduct.type=unsafe`:
 * neither transition is idempotent, so re-submitting the same target
 * code MUST fail rather than silently overwrite the existing row.
 *
 * Mirrors Pilot 4's {@see EmailAlreadyRegisteredException} for the
 * customer-create flow — the resource layer maps both to HTTP 409.
 */
#[Message([
    'en' => 'The product code is already in use.',
    'ja' => 'この商品コードは既に使用されています。',
])]
final class ProductCodeAlreadyInUseException extends DomainException
{
}
