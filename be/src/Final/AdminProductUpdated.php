<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin product updated — Final, proof an admin edited one product in
 * place.
 *
 *   AdminUpdateProductInput → AdminProductUpdated  (Direct, idempotent)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson, same ladder as Wave 7
 * AdminOrderUpdated):
 *
 *   1. No admin session       → UnauthorizedAdminAccessException  (403)
 *   2. Unknown productCode    → ProductNotFoundException          (404)
 *
 * The admin firewall check happens before existence is probed so an
 * admin-anonymous client has no business learning whether a given
 * productCode resolves.
 *
 * Merge semantics (Pilot 8 / Wave 7 partial-update convention) — every
 * editable field is nullable; null = keep persisted value, else
 * overwrite. The productCode itself is the target selector and is
 * NEVER editable through this transition (a rename requires
 * doCopyProduct + doDeleteProduct).
 *
 * Mass-assignment safety: the editable surface is intentionally
 * narrow. The Final reads the persisted entity and only overrides the
 * fields the input supplied; all other ProductEntity fields round-trip
 * verbatim.
 *
 * Idempotency (ALPS `type=idempotent`): a PUT with the same body
 * returns the same projection. We do NOT short-circuit on equality —
 * the operation is the same overwrite either way and the test layer
 * verifies by comparing the projected fields.
 */
final readonly class AdminProductUpdated
{
    public string $productCode;
    public string $productName;
    public int $price02;
    public int|null $stock;
    public int $productStatus;
    public string|null $description;

    public function __construct(
        #[Input] string $productCode,
        #[Input] string|null $productName,
        #[Input] int|null $price02,
        #[Input] int|null $stock,
        #[Input] int|null $productStatus,
        #[Input] string|null $description,
        #[Input] string|null $searchWord,
        #[Input] string|null $note,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
        #[Inject] ProductCommandInterface $productCommand,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $productQuery->item($productCode);
        if ($current === null) {
            throw new ProductNotFoundException();
        }

        $merged = new ProductEntity(
            productCode: $current->productCode,
            productName: $productName ?? $current->productName,
            price02: $price02 ?? $current->price02,
            // `stock` is nullable on the entity (stock-unlimited products
            // carry null). To allow the admin to explicitly clear the
            // stock count we'd need a tri-state input — out of scope for
            // Wave 8. Convention: null on the input means "leave alone",
            // any non-null value overwrites including 0.
            stock: $stock ?? $current->stock,
            productStatus: $productStatus ?? $current->productStatus,
            description: $description ?? $current->description,
            searchWord: $searchWord ?? $current->searchWord,
            note: $note ?? $current->note,
        );

        $productCommand->update($merged);

        $this->productCode = $merged->productCode;
        $this->productName = $merged->productName;
        $this->price02 = $merged->price02;
        $this->stock = $merged->stock;
        $this->productStatus = $merged->productStatus;
        $this->description = $merged->description;
    }
}
