<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\CartItemNotInCartException;
use MyVendor\BeMart\Be\Exception\OutOfStockException;
use MyVendor\BeMart\Be\Exception\ProductClassNotFoundException;
use MyVendor\BeMart\Be\Final\CartItemQuantityUpdated;
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
use function sprintf;

/**
 * Stage 1 Being for Pilot 10 — quantity replacement.
 *
 * Converges:
 *   - ProductClassQuery — current stock / saleLimit caps
 *   - CartQuery         — existing cart for the productCode's sale type
 *
 * The merged CartEntity is built in memory with the existing item's
 * quantity REPLACED (not incremented — that's Pilot 2). The Final
 * stage persists. Failure modes raised here:
 *
 *   - ProductClassNotFoundException → productCode unknown / discontinued
 *   - OutOfStockException           → stock is 0 (cannot satisfy any qty)
 *   - CartItemNotInCartException    → the item was never added to the
 *                                     cart for this session
 */
#[Be(CartItemQuantityUpdated::class)]
final readonly class CartItemQuantityReplacing
{
    public string $productCode;
    public int $requestedQuantity;
    public int $adjustedQuantity;
    public int $unitPrice;
    public string $cartKey;
    public string $saleTypeName;
    public CartEntity $mergedCart;

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $quantity,
        #[Input] string $sessionPrefix,
        #[Inject] ProductClassQueryInterface $productClassQuery,
        #[Inject] CartQueryInterface $cartQuery,
    ) {
        $productClass = $productClassQuery->item($productCode);
        if (! $productClass instanceof ProductClassEntity) {
            throw new ProductClassNotFoundException();
        }

        if (! $productClass->stockUnlimited && $productClass->stock === 0) {
            throw new OutOfStockException();
        }

        $cartKey = sprintf('%s_%d', $sessionPrefix, $productClass->saleTypeId);
        $existingCart = $cartQuery->item($cartKey);
        if ($existingCart === null) {
            throw new CartItemNotInCartException();
        }

        $itemFound = false;
        $newItems = [];
        foreach ($existingCart->items as $item) {
            if ($item->productCode !== $productCode) {
                $newItems[] = $item;

                continue;
            }

            $itemFound = true;
            $adjusted = $quantity;
            if (! $productClass->stockUnlimited && $productClass->stock !== null) {
                $adjusted = min($adjusted, $productClass->stock);
            }

            if ($productClass->saleLimit !== null) {
                $adjusted = min($adjusted, $productClass->saleLimit);
            }

            $newItems[] = new CartItemEntity(
                productCode: $productCode,
                quantity: $adjusted,
                price: $productClass->price02,
            );
            $this->adjustedQuantity = $adjusted;
        }

        if (! $itemFound) {
            throw new CartItemNotInCartException();
        }

        $totalPrice = (int) array_sum(
            array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $newItems),
        );
        $deliveryFeeTotal = (int) array_sum(
            array_map(
                static function (CartItemEntity $i) use ($productClassQuery): int {
                    $pc = $productClassQuery->item($i->productCode);

                    return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                },
                $newItems,
            ),
        );

        $this->productCode = $productCode;
        $this->requestedQuantity = $quantity;
        $this->unitPrice = $productClass->price02;
        $this->cartKey = $existingCart->cartKey;
        $this->saleTypeName = $existingCart->saleTypeName;
        $this->mergedCart = new CartEntity(
            cartKey: $existingCart->cartKey,
            saleTypeId: $existingCart->saleTypeId,
            saleTypeName: $existingCart->saleTypeName,
            items: $newItems,
            totalPrice: $totalPrice,
            deliveryFeeTotal: $deliveryFeeTotal,
            preOrderId: $existingCart->preOrderId,
        );
    }
}
