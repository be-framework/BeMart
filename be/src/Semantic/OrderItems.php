<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\OrderItemsFormatException;

use function count;
use function is_array;
use function is_int;
use function is_string;
use function mb_strlen;
use function trim;

/**
 * Order line-item vector — doCreateOrder (admin manual order entry).
 *
 * The admin posts the purchased lines; each entry must be a 4-tuple of
 * {productCode: string, productName: string, unitPrice: int,
 * quantity: int}. Validation walks the list and rejects malformed
 * shapes before PurchaseFlow recomputes the totals from it.
 *
 * Shape rules per entry:
 *   - productCode: non-empty, max 255 chars (dtb_order_item.product_code).
 *   - productName: non-empty, max 255 chars (dtb_order_item.product_name,
 *     NOT NULL — the frozen display name).
 *   - unitPrice:   0..99,999,999 (price02 snapshot; non-negative).
 *   - quantity:    1..9,999 (a finalized line ships at least one unit).
 *
 * The list itself must contain 1..100 entries (a single manual order
 * stays well under 100 distinct lines).
 *
 * @link https://schema.org/OrderItem
 */
final class OrderItems
{
    private const MAX_UNIT_PRICE = 99999999;
    private const MAX_QUANTITY = 9999;

    /** @param array<int, mixed> $orderItems */
    #[Validate]
    public function validate(array $orderItems): void
    {
        $count = count($orderItems);
        if ($count < 1 || $count > 100) {
            throw new OrderItemsFormatException();
        }

        foreach ($orderItems as $entry) {
            if (! is_array($entry)) {
                throw new OrderItemsFormatException();
            }

            if (! isset($entry['productCode'], $entry['productName'], $entry['unitPrice'], $entry['quantity'])) {
                throw new OrderItemsFormatException();
            }

            $code = $entry['productCode'];
            if (! is_string($code) || trim($code) === '' || mb_strlen($code) > 255) {
                throw new OrderItemsFormatException();
            }

            $name = $entry['productName'];
            if (! is_string($name) || trim($name) === '' || mb_strlen($name) > 255) {
                throw new OrderItemsFormatException();
            }

            $unitPrice = $entry['unitPrice'];
            if (! is_int($unitPrice) || $unitPrice < 0 || $unitPrice > self::MAX_UNIT_PRICE) {
                throw new OrderItemsFormatException();
            }

            $quantity = $entry['quantity'];
            if (! is_int($quantity) || $quantity < 1 || $quantity > self::MAX_QUANTITY) {
                throw new OrderItemsFormatException();
            }
        }
    }
}
