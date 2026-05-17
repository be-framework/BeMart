<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\OutOfStockException;
use MyVendor\BeMart\Be\Exception\ProductClassNotFoundException;
use MyVendor\BeMart\Be\Final\CartItemAdded;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function min;
use function sprintf;

/**
 * Stage 1 Being — quantity adjustment.
 *
 * Collapses the Reasons that decide "what quantity is allowed and into which
 * cart partition it goes" into one existence:
 *
 *   - ProductClass lookup (fail-fast: ProductClassNotFound / OutOfStock)
 *   - Stock cap (when !stockUnlimited)
 *   - SaleLimit cap (per-customer purchase ceiling)
 *   - SaleType resolution (cartKey = sessionPrefix_saleTypeId)
 *
 * Downstream CartItemAdded converges cart-side Reasons (existing cart lookup,
 * merge, delivery accumulation, persistence) using this Being's outputs as
 * #[Input].
 */
#[Be([CartItemAdded::class])]
final readonly class QuantityAdjusted
{
    public string $productCode;
    public int $requestedQuantity;
    public int $adjustedQuantity;
    public string $sessionPrefix;
    public int $unitPrice;
    public int $saleTypeId;
    public string $saleTypeName;
    public int $deliveryFee;
    public bool $stockUnlimited;
    public int|null $stock;
    public int|null $saleLimit;
    public string $cartKey;

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $quantity,
        #[Input] string $sessionPrefix,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
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

        if ($productClass->saleLimit !== null) {
            $adjusted = min($adjusted, $productClass->saleLimit);
        }

        $this->productCode = $productCode;
        $this->requestedQuantity = $quantity;
        $this->adjustedQuantity = $adjusted;
        $this->sessionPrefix = $sessionPrefix;
        $this->unitPrice = $productClass->price02;
        $this->saleTypeId = $productClass->saleTypeId;
        $this->saleTypeName = $productClass->saleTypeName;
        $this->deliveryFee = $productClass->deliveryFee;
        $this->stockUnlimited = $productClass->stockUnlimited;
        $this->stock = $productClass->stock;
        $this->saleLimit = $productClass->saleLimit;
        $this->cartKey = sprintf('%s_%d', $sessionPrefix, $productClass->saleTypeId);

        assert($this->adjustedQuantity >= 1 && $this->adjustedQuantity <= $this->requestedQuantity);
    }
}
