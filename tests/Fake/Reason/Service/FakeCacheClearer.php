<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface;
use Override;

/** Recording fake: tests assert doClearCache invoked clear() exactly once. */
final class FakeCacheClearer implements CacheClearerInterface
{
    public int $clears = 0;

    #[Override]
    public function clear(): int
    {
        return ++$this->clears;
    }
}
