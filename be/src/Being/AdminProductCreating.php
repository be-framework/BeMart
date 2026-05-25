<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCreated;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * The product-being-created (admin-side) moment.
 *
 * Multi-Reason Being (blog-publishing demo) mirroring Wave 5O
 * {@see AdminCustomerCreating}, with two Reasons checked before
 * persistence:
 *
 *   0. AdminSessionInterface     — fail-fast if no admin session
 *   1. ProductQueryInterface     — fail-fast on duplicate productCode
 *
 * Existence of this object proves both checks passed. The downstream
 * Final ({@see AdminProductCreated}) only has to persist a
 * ProductEntity built from this public surface.
 *
 * AUTHZ rationale: admin and customer are parallel firewalls (Wave 4
 * decision). A logged-in customer is NOT logged-in-as-admin and must
 * not reach this code path. The check is at Being-time so the
 * resource layer can map the resulting exception to HTTP 403.
 *
 * Duplicate detection: ALPS gives `doCreateProduct` type=unsafe
 * (replays are NOT idempotent) — re-submitting the same productCode
 * has to fail rather than silently overwrite the existing row. The
 * 409 conflict is raised inside the Being so the Final never runs
 * with a duplicate code.
 *
 * `productStatus` defaults to STATUS_VISIBLE (1) when the form omits
 * it, matching the EC-CUBE admin convention (new products are
 * published by default).
 */
#[Be(AdminProductCreated::class)]
final readonly class AdminProductCreating
{
    public int $productStatus;

    public function __construct(
        #[Input] public string $productCode,
        #[Input] public string $productName,
        #[Input] public int $price02,
        #[Input] public int|null $stock,
        #[Input] int|null $productStatus,
        #[Input] public string|null $description,
        #[Input] public string|null $searchWord,
        #[Input] public string|null $note,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($productQuery->item($productCode) !== null) {
            // ALPS `doCreateProduct.type=unsafe`. Replay collision must
            // surface — the resource layer maps this to 409 (mirrors
            // Wave 5O EmailAlreadyRegistered handling).
            throw new ProductCodeAlreadyInUseException();
        }

        $this->productStatus = $productStatus ?? ProductEntity::STATUS_VISIBLE;
    }
}
