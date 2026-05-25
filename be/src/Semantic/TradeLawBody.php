<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TradeLawBodyFormatException;

use function mb_strlen;
use function trim;

/**
 * Trade-law body — EC-CUBE 4.3 dtb_trade_law.description (collapsed
 * into a single blob for Wave 8). Non-empty, <= 65,535 chars
 * (defensive MySQL TEXT cap; an unbounded body submission is an abuse
 * surface).
 *
 * Empty bodies are rejected — an empty trade-law page would violate
 * the Japanese Specified Commercial Transactions Act requirement.
 * EC-CUBE itself does HTMLPurifier-style sanitization on the rich-
 * text content; that is Phase 2 scope.
 */
final class TradeLawBody
{
    #[Validate]
    public function validate(string $tradeLawBody): void
    {
        $length = mb_strlen($tradeLawBody);
        if (trim($tradeLawBody) === '' || $length > 65535) {
            throw new TradeLawBodyFormatException();
        }
    }
}
