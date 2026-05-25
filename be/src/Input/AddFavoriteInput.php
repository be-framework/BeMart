<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\FavoriteAdded;

/**
 * Input for doAddFavorite — Pilot 13.
 *
 * Direct pattern: Input → Final.
 *
 *   AddFavoriteInput → FavoriteAdded (Final)
 *
 * AUTHZ via Session — customerId is NOT in the body (Pilot 5 F-2 +
 * Pilot 8 pattern). The Final pulls it from SessionInterface.
 *
 * Duplicate-add is idempotent (the ALPS doc spec: "重複追加は無視").
 *
 * @link https://schema.org/LikeAction
 */
#[Be(FavoriteAdded::class)]
final readonly class AddFavoriteInput
{
    /**
     * @psalm-taint-source input $productCode
     */
    public function __construct(
        public string $productCode,
    ) {
    }
}
