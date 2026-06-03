<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Param;

use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use Override;
use Ray\MediaQuery\ToScalarInterface;

use function array_map;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Order-item snapshot vector — the bind parameter for
 * `order_item_register` (doCreateOrder / checkout).
 *
 * Serializes a finalized order's line items to a JSON array so the
 * INSERT can fan them out with a single `JSON_TABLE` round-trip (same
 * shape as {@see CsvColumnConfigList}). Each element carries the four
 * columns dtb_order_item needs for the order-time snapshot:
 * productCode, productName, quantity, unitPrice (→ `price`).
 */
final readonly class OrderItemList implements ToScalarInterface
{
    /** @param list<OrderItemEntity> $items */
    public function __construct(public array $items)
    {
    }

    /** @param list<OrderItemEntity> $items */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    #[Override]
    public function toScalar(): string
    {
        return json_encode(array_map(
            static fn (OrderItemEntity $item): array => [
                'productCode' => $item->productCode,
                'productName' => $item->productName,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unitPrice,
            ],
            $this->items,
        ), JSON_THROW_ON_ERROR);
    }
}
