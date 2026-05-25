<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\FavoriteRemoved;

/**
 * Input for doRemoveFavorite — idempotent inverse of Pilot 13.
 *
 * Direct pattern: Input → Final.
 *
 *   RemoveFavoriteInput → FavoriteRemoved (Final)
 *
 * AUTHZ via Session — customerId is NOT in the body (Pilot 5 F-2 +
 * Pilot 8 pattern). The Final pulls it from CustomerSession.
 *
 * Idempotent (ALPS type=idempotent): re-removing an already-absent
 * item is a silent no-op — the Final still constructs with
 * `alreadyAbsent=true` so the caller can distinguish first-remove
 * from re-remove if desired.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(FavoriteRemoved::class)]
final readonly class RemoveFavoriteInput
{
    /**
     * @psalm-taint-source input $productCode
     */
    public function __construct(
        public string $productCode,
    ) {
    }
}
