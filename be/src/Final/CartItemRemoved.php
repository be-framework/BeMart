<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CartItemNotInCartException;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_filter;
use function array_map;
use function array_sum;
use function array_values;

/**
 * Cart item removed — Final, proof the item was removed from the cart.
 *
 *   RemoveCartItemInput → CartItemRemoved  (Direct)
 *
 * Scans every cart under the session prefix for the productCode (the
 * item could live in any sale-type partition) and removes it.
 * Recomputes totalPrice and deliveryFeeTotal off the surviving items.
 * Raises CartItemNotInCartException when the productCode was not
 * present in any cart.
 */
final readonly class CartItemRemoved
{
    public string $productCode;
    public string $cartKey;
    public int $totalPrice;
    public int $deliveryFeeTotal;

    public function __construct(
        #[Input] string $productCode,
        #[Input] string $sessionPrefix,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] CartCommandInterface $cartCommand,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        $found = false;
        $newCart = null;

        foreach ($cartQuery->bySessionPrefix($sessionPrefix) as $cart) {
            $hasItem = false;
            foreach ($cart->items as $item) {
                if ($item->productCode === $productCode) {
                    $hasItem = true;

                    break;
                }
            }

            if (! $hasItem) {
                continue;
            }

            $found = true;
            $survivors = array_values(array_filter(
                $cart->items,
                static fn (CartItemEntity $i): bool => $i->productCode !== $productCode,
            ));

            $totalPrice = (int) array_sum(
                array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $survivors),
            );
            $deliveryFeeTotal = (int) array_sum(
                array_map(
                    static function (CartItemEntity $i) use ($productClassQuery): int {
                        $pc = $productClassQuery->item($i->productCode);

                        return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                    },
                    $survivors,
                ),
            );

            $newCart = new CartEntity(
                cartKey: $cart->cartKey,
                saleTypeId: $cart->saleTypeId,
                saleTypeName: $cart->saleTypeName,
                items: $survivors,
                totalPrice: $totalPrice,
                deliveryFeeTotal: $deliveryFeeTotal,
                preOrderId: $cart->preOrderId,
            );

            $cartCommand->save($newCart);

            break;
        }

        if (! $found || $newCart === null) {
            throw new CartItemNotInCartException();
        }

        $this->productCode = $productCode;
        $this->cartKey = $newCart->cartKey;
        $this->totalPrice = $newCart->totalPrice;
        $this->deliveryFeeTotal = $newCart->deliveryFeeTotal;
    }
}
