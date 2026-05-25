<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TradeLawFetched;

/**
 * Input for goTradeLawList — admin views the Specified Commercial
 * Transactions Act page contents (Wave 9).
 *
 *   GetTradeLawInput → TradeLawFetched  (Direct, safe read)
 *
 * Pair of the Wave 8ε {@see UpdateTradeLawInput} write side. Wave 8ε
 * treats the page as a single body blob; this read side mirrors that
 * shape. Phase 2 will split into per-item rows (see
 * {@see TradeLawStorageInterface}).
 *
 * AUTHZ in the Final (AdminSession). The customer-side display
 * surface lives at {@see \MyVendor\BeMart\Resource\Page\Help\TradeLaw},
 * which is anonymous-accessible — admin and help routes are separate.
 *
 * @link https://schema.org/ReadAction
 */
#[Be(TradeLawFetched::class)]
final readonly class GetTradeLawInput
{
    public function __construct()
    {
    }
}
