<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Attribute;

/**
 * This operation changes what the storefront product corpus shows
 *
 * The corpus is cached until something invalidates it, so a write that is not announced is
 * served stale forever. Marking the declaration rather than each caller means a new write is
 * written next to the mark, not next to a call it can omit.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ChangesProductCorpus
{
}
