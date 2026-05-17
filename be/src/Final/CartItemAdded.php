<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Cart item added — Final, proof that the merged cart was persisted.
 *
 * Cascade:
 *   AddCartItemInput
 *     → QuantityAdjusted (Being) — quantity decision + cartKey
 *     → CartMerged (Being)       — in-memory merge + totalPrice + deliveryFeeTotal
 *     → CartItemAdded (Final)    — persistence (this stage)
 *
 * Existence of this object proves CartMerged's merged cart was saved.
 * Public surface mirrors what Resource callers need; totalPrice and
 * deliveryFeeTotal are read off the already-computed CartEntity.
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
        #[Input] string $saleTypeName,
        #[Input] CartEntity $mergedCart,
        #[Inject] CartCommandInterface $cartCommand,
    ) {
        $this->productCode = $productCode;
        $this->requestedQuantity = $requestedQuantity;
        $this->adjustedQuantity = $adjustedQuantity;
        $this->cartKey = $cartKey;
        $this->unitPrice = $unitPrice;
        $this->saleTypeName = $saleTypeName;
        $this->totalPrice = $mergedCart->totalPrice;
        $this->deliveryFeeTotal = $mergedCart->deliveryFeeTotal;

        $cartCommand->save($mergedCart);

        assert($this->adjustedQuantity >= 1 && $this->adjustedQuantity <= $this->requestedQuantity);
    }
}
