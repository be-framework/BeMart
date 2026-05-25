<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\FavoriteListFetched;

/**
 * Input for goFavoriteList — render the logged-in customer's full
 * favorites list (the read pair for Pilot 13's doAddFavorite +
 * doRemoveFavorite).
 *
 * Direct pattern: Input → Final. The Final injects SessionInterface
 * to resolve the customerId (AUTHN) and the FavoriteStorageInterface
 * to load the rows.
 *
 * AUTHZ design — mass-assignment safety:
 *   The customerId is INTENTIONALLY ABSENT from this Input. It comes
 *   exclusively from the session. The favorites list always renders
 *   "your own"; there is no way to view another customer's by
 *   tampering with request parameters. (Pilot 5 F-2 lesson carried
 *   forward, same convention `GetMypageInput` / `GetMypageChangeInput`
 *   use.)
 *
 * The goMypage dashboard surfaces only `favoriteCount`; this Input
 * drives the dedicated full-listing view.
 *
 * @link https://schema.org/ViewAction
 */
#[Be(FavoriteListFetched::class)]
final readonly class GetFavoriteListInput
{
    public function __construct()
    {
    }
}
