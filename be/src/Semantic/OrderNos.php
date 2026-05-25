<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\OrderNosFormatException;

use function count;
use function is_string;
use function trim;

/**
 * List of order numbers — Wave 9η (doBulkDeleteOrder).
 *
 * Plural counterpart of {@see OrderNo} used by the bulk-delete
 * transition. Each element must be a non-empty string (the OrderNo
 * Semantic is intentionally lax — providers are the contract — so
 * we mirror that here). The list itself must be non-empty and capped
 * at 100 elements (an admin operator should not be cancelling more
 * than a page's worth of orders in one request).
 *
 * Mirrors the {@see ProductCodes} list-size contract (Wave 8).
 */
final class OrderNos
{
    /** @param array<int, mixed> $orderNos */
    #[Validate]
    public function validate(array $orderNos): void
    {
        $count = count($orderNos);
        if ($count < 1 || $count > 100) {
            throw new OrderNosFormatException();
        }

        foreach ($orderNos as $orderNo) {
            if (! is_string($orderNo) || trim($orderNo) === '') {
                throw new OrderNosFormatException();
            }
        }
    }
}
