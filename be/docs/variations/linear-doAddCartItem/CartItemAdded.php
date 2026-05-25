<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OutOfStockException;
use MyVendor\BeMart\Be\Exception\ProductClassNotFoundException;
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
use function sprintf;

/**
 * Cart item added — terminal state of the doAddCartItem transition.
 *
 * Pattern: Linear/Minimal (Input → Final). NOT a Cascade Diamond.
 * The five labeled blocks below are sequential procedural steps within a
 * single constructor, not separate Being classes converging via #[Reason].
 * A true Cascade Diamond reference is reserved for a future pilot whose
 * domain naturally splits into independent Reasons (e.g. doCreateOrder
 * converging Cart + Customer + Payment).
 *
 * Sequential blocks:
 *   client-input (productCode, quantity)
 *     [1] StockCheck         — cap by stock when !stockUnlimited
 *     [2] SaleLimitCheck     — cap by saleLimit
 *     [3] SaleTypeResolution — cartKey = sessionPrefix_saleTypeId
 *     [4] CartItemMergePrice — same productCode → quantity sum
 *     [5] DeliveryFeeAccumulation
 *
 * Existence of this object proves all five blocks passed. OutOfStock and
 * ProductClassNotFound are the only hard failures; quantity overflow is
 * silently capped (EC-CUBE convention).
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
        #[Input] int $quantity,
        #[Input] string $sessionPrefix,
        #[Inject] ProductClassQueryInterface $productClassQuery,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] CartCommandInterface $cartCommand,
    ) {
        $this->requestedQuantity = $quantity;
        $this->productCode = $productCode;

        // Reason 1 — StockCheck: fetch ProductClass and cap quantity.
        $productClass = $productClassQuery->item($productCode);
        if (! $productClass instanceof ProductClassEntity) {
            throw new ProductClassNotFoundException();
        }

        if (! $productClass->stockUnlimited && $productClass->stock === 0) {
            throw new OutOfStockException();
        }

        $adjusted = $quantity;
        if (! $productClass->stockUnlimited && $productClass->stock !== null) {
            $adjusted = min($adjusted, $productClass->stock);
        }

        // Reason 2 — SaleLimitCheck: per-customer purchase cap.
        if ($productClass->saleLimit !== null) {
            $adjusted = min($adjusted, $productClass->saleLimit);
        }

        $this->adjustedQuantity = $adjusted;
        $this->unitPrice = $productClass->price02;
        $this->saleTypeName = $productClass->saleTypeName;

        // Reason 3 — SaleTypeResolution: per-saleType cart partition.
        $this->cartKey = sprintf('%s_%d', $sessionPrefix, $productClass->saleTypeId);

        $existingCart = $cartQuery->byCartKey($this->cartKey)
            ?? new CartEntity(
                cartKey: $this->cartKey,
                saleTypeId: $productClass->saleTypeId,
                saleTypeName: $productClass->saleTypeName,
                items: [],
                totalPrice: 0,
                deliveryFeeTotal: 0,
                preOrderId: '',
            );

        // Reason 5 — CartItemMergePrice: same productCode → sum, capped again.
        $mergedItems = [];
        $merged = false;
        foreach ($existingCart->items as $item) {
            if ($item->productCode === $productCode) {
                $newQty = $item->quantity + $adjusted;
                if (! $productClass->stockUnlimited && $productClass->stock !== null) {
                    $newQty = min($newQty, $productClass->stock);
                }

                if ($productClass->saleLimit !== null) {
                    $newQty = min($newQty, $productClass->saleLimit);
                }

                $mergedItems[] = new CartItemEntity(
                    productCode: $item->productCode,
                    quantity: $newQty,
                    price: $productClass->price02,
                );
                $merged = true;
                continue;
            }

            $mergedItems[] = $item;
        }

        if (! $merged) {
            $mergedItems[] = new CartItemEntity(
                productCode: $productCode,
                quantity: $adjusted,
                price: $productClass->price02,
            );
        }

        $totalPrice = (int) array_sum(
            array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $mergedItems),
        );

        // Reason 4 — DeliveryFeeAccumulation: per-item shipping × total quantity in cart.
        $deliveryFeeTotal = (int) array_sum(
            array_map(
                static function (CartItemEntity $i) use ($productClassQuery): int {
                    $pc = $productClassQuery->item($i->productCode);

                    return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                },
                $mergedItems,
            ),
        );

        $this->totalPrice = $totalPrice;
        $this->deliveryFeeTotal = $deliveryFeeTotal;

        $cartCommand->save(new CartEntity(
            cartKey: $existingCart->cartKey,
            saleTypeId: $existingCart->saleTypeId,
            saleTypeName: $existingCart->saleTypeName,
            items: $mergedItems,
            totalPrice: $totalPrice,
            deliveryFeeTotal: $deliveryFeeTotal,
            preOrderId: $existingCart->preOrderId,
        ));

        assert($this->adjustedQuantity >= 1 && $this->adjustedQuantity <= $this->requestedQuantity);
    }
}
