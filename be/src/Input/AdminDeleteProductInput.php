<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductDeleted;

/**
 * Input for doDeleteProduct — admin soft-deletes a product.
 *
 *   AdminDeleteProductInput → AdminProductDeleted  (Direct, idempotent)
 *
 * ALPS `doDeleteProduct.type=idempotent`. Soft-delete via status flip
 * to STATUS_WITHDRAWN — the row is never physically removed.
 *
 * Mass-assignment safety: only the target productCode is on the wire;
 * the adminId is read from AdminSession inside the Final.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(AdminProductDeleted::class)]
final readonly class AdminDeleteProductInput
{
    /**
     * @psalm-taint-source input $productCode
     */
    public function __construct(
        public string $productCode,
    ) {
    }
}
