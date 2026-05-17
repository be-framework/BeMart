<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function min;

/**
 * Cart item added — Stage 2 Final, terminal state of the doAddCartItem cascade.
 *
 * Pattern: Cascade metamorphosis with Reason convergence in the Final.
 *
 *   AddCartItemInput
 *     → QuantityAdjusted (Being) — Stage 1: quantity caps + cartKey resolution
 *     → CartItemAdded (Final)    — Stage 2: cart context + merge + delivery + persistence
 *
 * Stage 2 converges three independent Reasons via #[Inject]:
 *
 *   - CartQuery          — existing cart lookup
 *   - CartCommand        — persistence
 *   - ProductClassQuery  — per-cart-item delivery fee lookup across merged items
 *
 * Existence of this object proves Stage 1 succeeded (ProductClass found,
 * stock checked, quantity capped) and Stage 2 completed (cart merged and
 * saved). Quantity overflow is silently capped (EC-CUBE convention); only
 * OutOfStock and ProductClassNotFound are hard failures, both raised in
 * Stage 1.
 *
 * Note: a "true" Cascade Diamond (multiple parallel Moments converging into
 * a Final, as in be-patterns/order-processing) is reserved for a future
 * pilot whose domain naturally splits into independent parallel reasons
 * (e.g., doCreateOrder converging Cart + Customer + Payment in parallel).
 * doAddCartItem is inherently sequential — quantity must be resolved before
 * the cart can be merged — so a Being chain is the honest shape here.
 */
final readonly class CartItemAdded
{
    public string $cartKey;
    public string $productCode;
    public int $requestedQuantity;
    public int $adjustedQuantity;
    public int $unitPrice;
    public int $totalPrice;
    public int $deliveryFeeTotal;
    public string $saleTypeName;

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $requestedQuantity,
        #[Input] int $adjustedQuantity,
        #[Input] string $cartKey,
        #[Input] int $unitPrice,
        #[Input] int $saleTypeId,
        #[Input] string $saleTypeName,
        #[Input] bool $stockUnlimited,
        #[Input] int|null $stock,
        #[Input] int|null $saleLimit,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] CartCommandInterface $cartCommand,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        $this->productCode = $productCode;
        $this->requestedQuantity = $requestedQuantity;
        $this->adjustedQuantity = $adjustedQuantity;
        $this->cartKey = $cartKey;
        $this->unitPrice = $unitPrice;
        $this->saleTypeName = $saleTypeName;

        $existingCart = $cartQuery->byCartKey($cartKey)
            ?? new CartEntity(
                cartKey: $cartKey,
                saleTypeId: $saleTypeId,
                saleTypeName: $saleTypeName,
                items: [],
                totalPrice: 0,
                deliveryFeeTotal: 0,
                preOrderId: '',
            );

        $mergedItems = [];
        $merged = false;
        foreach ($existingCart->items as $item) {
            if ($item->productCode === $productCode) {
                $newQty = $item->quantity + $adjustedQuantity;
                if (! $stockUnlimited && $stock !== null) {
                    $newQty = min($newQty, $stock);
                }

                if ($saleLimit !== null) {
                    $newQty = min($newQty, $saleLimit);
                }

                $mergedItems[] = new CartItemEntity(
                    productCode: $item->productCode,
                    quantity: $newQty,
                    price: $unitPrice,
                );
                $merged = true;
                continue;
            }

            $mergedItems[] = $item;
        }

        if (! $merged) {
            $mergedItems[] = new CartItemEntity(
                productCode: $productCode,
                quantity: $adjustedQuantity,
                price: $unitPrice,
            );
        }

        $this->totalPrice = (int) array_sum(
            array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $mergedItems),
        );

        $this->deliveryFeeTotal = (int) array_sum(
            array_map(
                static function (CartItemEntity $i) use ($productClassQuery): int {
                    $pc = $productClassQuery->item($i->productCode);

                    return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                },
                $mergedItems,
            ),
        );

        $cartCommand->save(new CartEntity(
            cartKey: $existingCart->cartKey,
            saleTypeId: $existingCart->saleTypeId,
            saleTypeName: $existingCart->saleTypeName,
            items: $mergedItems,
            totalPrice: $this->totalPrice,
            deliveryFeeTotal: $this->deliveryFeeTotal,
            preOrderId: $existingCart->preOrderId,
        ));

        assert($this->adjustedQuantity >= 1 && $this->adjustedQuantity <= $this->requestedQuantity);
    }
}
