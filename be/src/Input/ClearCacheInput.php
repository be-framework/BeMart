<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CacheCleared;

/**
 * Input for `doClearCache` — an admin clears the application cache (Hard
 * ActionRedirect completion). No payload: the action targets the whole
 * cache. ALPS marks it `idempotent` (clearing twice is a no-op). The
 * filesystem side-effect is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface}.
 */
#[Be(CacheCleared::class)]
final readonly class ClearCacheInput
{
    public function __construct()
    {
    }
}
