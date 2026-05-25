<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TradeLawUpdated;

/**
 * Input for doUpdateTradeLaw — admin edits the trade-law text.
 *
 *   UpdateTradeLawInput → TradeLawUpdated (Final — Direct, idempotent)
 *
 * Wave 8 first iteration treats the Specified Commercial Transactions
 * page as a single body blob — see {@see \MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity}.
 * Phase 2 will split into per-item rows so the sort_no /
 * displayOrderScreen toggles can be exercised independently.
 *
 * The body is bounded by {@see \MyVendor\BeMart\Be\Semantic\TradeLawBody}
 * (parameter `$tradeLawBody` matches the Semantic class name via the
 * SemanticValidator's snake_case to PascalCase resolution). Empty
 * bodies are rejected — an empty trade-law page would violate the
 * Japanese Specified Commercial Transactions Act requirement.
 *
 * Mass-assignment safety: only `tradeLawBody` is accepted.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(TradeLawUpdated::class)]
final readonly class UpdateTradeLawInput
{
    /**
     * @psalm-taint-source input $tradeLawBody
     */
    public function __construct(
        public string $tradeLawBody,
    ) {
    }
}
