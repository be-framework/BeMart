<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\OrderLimitFormatException;

/**
 * Mypage recent-orders panel cap — bounds the number of past orders
 * the dashboard projects (goMypage). Positive integer; 1 — 50 is the
 * dashboard-sane range. Pages that need the full history use the
 * dedicated history resource (goMypageHistory) instead of widening
 * this cap.
 */
final class OrderLimit
{
    #[Validate]
    public function validate(int $orderLimit): void
    {
        if ($orderLimit < 1 || $orderLimit > 50) {
            throw new OrderLimitFormatException();
        }
    }
}
