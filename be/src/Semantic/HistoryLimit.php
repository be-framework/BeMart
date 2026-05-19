<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\HistoryLimitFormatException;

/**
 * Order-history page-size cap — bounds the number of past orders returned
 * by goOrderHistory (the customer's "full history" view, distinct from the
 * five-row dashboard summary surfaced by goMypage).
 *
 * The dashboard cap ({@see OrderLimit}, 1—50) is intentionally tight: the
 * Mypage summary panel must stay shallow. The admin grid cap
 * ({@see Limit}, 1—50) likewise mirrors the storage-side ceiling. Neither
 * fits "full history" — a customer's long-running account can easily
 * exceed 50 orders. We allow up to 200 here so a single request can
 * realistically render the full list; pages beyond that are reached via
 * the `offset` Semantic for pagination.
 *
 * Positive integer; 1—200.
 */
final class HistoryLimit
{
    #[Validate]
    public function validate(int $historyLimit): void
    {
        if ($historyLimit < 1 || $historyLimit > 200) {
            throw new HistoryLimitFormatException();
        }
    }
}
