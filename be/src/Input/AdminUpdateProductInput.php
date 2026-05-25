<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductUpdated;

/**
 * Input for doUpdateProduct — admin edits an existing product.
 *
 *   AdminUpdateProductInput → AdminProductUpdated  (Direct, idempotent)
 *
 * ALPS `doUpdateProduct.type=idempotent`. The Final pulls AUTHZ from
 * AdminSessionInterface (no session → 403) and existence from
 * ProductQueryInterface (unknown productCode → 404).
 *
 * Mass-assignment safety (Pilot 5 F-2 lesson) — productCode is the
 * target selector and CANNOT be renamed through this transition. A
 * rename would re-key the row and break order-history snapshots —
 * use doCopyProduct + doDeleteProduct instead.
 *
 * Partial update convention (Pilot 8): every editable field is
 * nullable; null = keep persisted, non-null = overwrite. Caller can
 * pick which fields to touch without resubmitting the whole product
 * surface.
 *
 * Mirrors {@see AdminUpdateOrderInput} in shape and merge discipline.
 */
#[Be(AdminProductUpdated::class)]
final readonly class AdminUpdateProductInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $productName
     * @psalm-taint-source input $price02
     * @psalm-taint-source input $stock
     * @psalm-taint-source input $productStatus
     * @psalm-taint-source input $description
     * @psalm-taint-source input $searchWord
     * @psalm-taint-source input $note
     */
    public function __construct(
        public string $productCode,
        public string|null $productName = null,
        public int|null $price02 = null,
        public int|null $stock = null,
        public int|null $productStatus = null,
        public string|null $description = null,
        public string|null $searchWord = null,
        public string|null $note = null,
    ) {
    }
}
