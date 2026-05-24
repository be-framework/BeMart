<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Favorite removed — Final, proof the product was removed from the
 * logged-in customer's favorites (or was already absent).
 *
 *   RemoveFavoriteInput → FavoriteRemoved
 *
 * Idempotent (ALPS type=idempotent): re-removing the same product is
 * a silent no-op on storage; the Final still records
 * `alreadyAbsent=true` so the caller can distinguish first-remove
 * from re-remove. Both branches return HTTP 200 at the resource
 * layer.
 *
 * Unlike FavoriteAdded, we do NOT validate that the productCode
 * resolves to a real ProductEntity — DELETE removes a stored row,
 * not a product. A productCode that never existed is just an
 * "alreadyAbsent" no-op.
 */
final readonly class FavoriteRemoved
{
    public string $customerId;
    public string $productCode;
    public bool $alreadyAbsent;

    public function __construct(
        #[Input] string $productCode,
        #[Inject] SessionInterface $session,
        #[Inject] FavoriteStorageInterface $favorites,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $this->alreadyAbsent = ! $favorites->has($sessionCustomerId, $productCode)->exists;
        $favorites->delete($sessionCustomerId, $productCode);

        $this->customerId = $sessionCustomerId;
        $this->productCode = $productCode;
    }
}
