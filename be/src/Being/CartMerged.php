<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CartItemAdded;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function min;

/**
 * Stage 2 Being — in-memory cart merge.
 *
 * Converges two Reasons into "the merged cart state":
 *
 *   - CartQuery          — existing cart lookup
 *   - ProductClassQuery  — per-item deliveryFee lookup across merged items
 *
 * Outputs the merged CartEntity (with mergedItems / totalPrice / deliveryFeeTotal
 * computed) plus the scalars the Final needs to expose as its public surface.
 * Persistence is deferred to the Final stage (CartItemAdded).
 *
 * Existence of this object proves the cart has been correctly merged in memory.
 */
#[Be([CartItemAdded::class])]
final readonly class CartMerged
{
    public string $productCode;
    public int $requestedQuantity;
    public int $adjustedQuantity;
    public string $cartKey;
    public int $unitPrice;
    public string $saleTypeName;
    public CartEntity $mergedCart;

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
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        $this->productCode = $productCode;
        $this->requestedQuantity = $requestedQuantity;
        $this->adjustedQuantity = $adjustedQuantity;
        $this->cartKey = $cartKey;
        $this->unitPrice = $unitPrice;
        $this->saleTypeName = $saleTypeName;

        $existingCart = $cartQuery->item($cartKey)
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

        $totalPrice = (int) array_sum(
            array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $mergedItems),
        );

        $deliveryFeeTotal = (int) array_sum(
            array_map(
                static function (CartItemEntity $i) use ($productClassQuery): int {
                    $pc = $productClassQuery->item($i->productCode);

                    return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                },
                $mergedItems,
            ),
        );

        $this->mergedCart = new CartEntity(
            cartKey: $existingCart->cartKey,
            saleTypeId: $existingCart->saleTypeId,
            saleTypeName: $existingCart->saleTypeName,
            items: $mergedItems,
            totalPrice: $totalPrice,
            deliveryFeeTotal: $deliveryFeeTotal,
            preOrderId: $existingCart->preOrderId,
        );
    }
}
