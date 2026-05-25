<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Cart item quantity updated — Final, proof the replacement cart was
 * persisted.
 *
 *   UpdateCartItemQuantityInput
 *     → CartItemQuantityReplacing (Being)
 *     → CartItemQuantityUpdated   (this stage)
 *
 * The merged CartEntity already has totals re-computed by the upstream
 * Being. This stage just hands it to the storage.
 */
final readonly class CartItemQuantityUpdated
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
        $cartCommand->save($mergedCart);

        $this->cartKey = $cartKey;
        $this->productCode = $productCode;
        $this->requestedQuantity = $requestedQuantity;
        $this->adjustedQuantity = $adjustedQuantity;
        $this->unitPrice = $unitPrice;
        $this->saleTypeName = $saleTypeName;
        $this->totalPrice = $mergedCart->totalPrice;
        $this->deliveryFeeTotal = $mergedCart->deliveryFeeTotal;
    }
}
