<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use Override;

/**
 * In-memory corpus invalidator for test assertions
 */
final class RecordingProductCacheInvalidator implements ProductCacheInvalidatorInterface
{
    public int $calls = 0;

    #[Override]
    public function invalidateCorpus(): void
    {
        $this->calls++;
    }
}
