<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface;
use Override;

/**
 * EC-CUBE-compatible cache-clear boundary.
 *
 * Tracks clear invocations in process (bound as a singleton) so
 * `doClearCache` is exercisable end to end. Wiring the actual Twig /
 * Symfony cache-directory purge is the production cutover residual
 * (migration-status §4) — by design the runtime/file side-effect is held
 * behind this boundary rather than reproducing EC-CUBE's cache layout.
 */
final class EccubeCacheClearer implements CacheClearerInterface
{
    private int $clears = 0;

    #[Override]
    public function clear(): int
    {
        return ++$this->clears;
    }
}
