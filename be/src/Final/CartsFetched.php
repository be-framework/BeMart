<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function count;

/**
 * Carts fetched — Final, projection of the current session's carts.
 *
 *   GetCartsInput → CartsFetched  (Direct, read-only)
 *
 * Returns an empty list when the session hasn't added anything yet.
 * The cross-cart totals are computed here so the BEAR layer renders
 * a "shopping summary" row without re-traversing the items.
 */
final readonly class CartsFetched
{
    /** @var list<CartEntity> */
    public array $carts;

    public int $cartCount;
    public int $totalPrice;
    public int $deliveryFeeTotal;

    public function __construct(
        #[Input] string $sessionPrefix,
        #[Inject] CartQueryInterface $cartQuery,
    ) {
        $carts = $cartQuery->bySessionPrefix($sessionPrefix);

        $this->carts = $carts;
        $this->cartCount = count($carts);
        $this->totalPrice = array_sum(array_map(static fn (CartEntity $c) => $c->totalPrice, $carts));
        $this->deliveryFeeTotal = array_sum(array_map(static fn (CartEntity $c) => $c->deliveryFeeTotal, $carts));
    }
}
