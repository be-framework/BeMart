<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Announce that the product corpus changed
 *
 * A Final says what happened to the domain; it does not know there is a cache. This is the seam:
 * the implementation drops the entries an edit invalidates, and a test double records the call.
 *
 * Without it the storefront serves an edited product for as long as its TTL runs - the number is
 * short for that reason, and a write that announces itself is what makes a long one safe.
 */
interface ProductCacheInvalidatorInterface
{
    /** Every cached view of the corpus, whatever query string produced it */
    public function invalidateCorpus(): void;
}
