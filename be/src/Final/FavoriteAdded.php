<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Favorite added — Final, proof the product was added to the
 * logged-in customer's favorites.
 *
 *   AddFavoriteInput → FavoriteAdded
 *
 * Idempotent (the ALPS doc: "重複追加は無視") — re-adding the same
 * product is silently no-op on storage; the Final still constructs
 * with `alreadyExisted=true` so the caller can distinguish first-add
 * from re-add in the response code if desired.
 */
final readonly class FavoriteAdded
{
    public string $customerId;
    public string $productCode;
    public string $productName;
    public int $unitPrice;
    public bool $alreadyExisted;

    public function __construct(
        #[Input] string $productCode,
        #[Inject] SessionInterface $session,
        #[Inject] ProductQueryInterface $productQuery,
        #[Inject] FavoriteStorageInterface $favorites,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $product = $productQuery->item($productCode);
        if (! $product instanceof ProductEntity) {
            throw new ProductNotFoundException();
        }

        $this->alreadyExisted = $favorites->has($sessionCustomerId, $productCode)->exists();
        if (! $this->alreadyExisted) {
            $favorites->add(new FavoriteEntity(
                customerId: $sessionCustomerId,
                productCode: $product->productCode,
                productName: $product->productName,
                unitPrice: $product->price02,
            ));
        }

        $this->customerId = $sessionCustomerId;
        $this->productCode = $product->productCode;
        $this->productName = $product->productName;
        $this->unitPrice = $product->price02;
    }
}
