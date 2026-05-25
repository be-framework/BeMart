<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Favorite list fetched — Final, the logged-in customer's full
 * favorites projection (read pair for Pilot 13's doAddFavorite +
 * doRemoveFavorite).
 *
 *   GetFavoriteListInput → FavoriteListFetched  (Direct, safe read)
 *
 * AUTHN: the customerId comes from CustomerSession. A null session
 * raises UnauthenticatedException — the BEAR layer maps this to 401.
 * Unlike MypageFetched there is no second-stage existence check on
 * the customer record: the favorites listing is keyed only on the
 * session's customerId and an empty list is a valid Final (a logged-in
 * customer who has not yet favorited anything).
 *
 * The `favorites` list is a flat projection of FavoriteEntity — the
 * dashboard renders a plain list and the entity's internal layout
 * does not leak across the HTTP boundary (same convention
 * `MypageFetched::$recentOrders` uses).
 *
 * The goMypage dashboard surfaces only `favoriteCount`; this Final
 * drives the dedicated full-listing view.
 */
final readonly class FavoriteListFetched
{
    public string $customerId;

    /** @var list<array{productCode: string, productName: string, unitPrice: int, fileName: string|null}> */
    public array $favorites;

    public int $favoriteCount;

    public function __construct(
        #[Inject] CustomerSession $session,
        #[Inject] FavoriteStorageInterface $favorites,
    ) {
        $sessionCustomerId = $session->customerId;
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $rows = $favorites->listByCustomer($sessionCustomerId);

        $this->customerId = $sessionCustomerId;
        $this->favorites = array_map(
            static fn (FavoriteEntity $favorite): array => [
                'productCode' => $favorite->productCode,
                'productName' => $favorite->productName,
                'unitPrice' => $favorite->unitPrice,
                'fileName' => $favorite->fileName,
            ],
            $rows,
        );
        $this->favoriteCount = count($rows);
    }
}
