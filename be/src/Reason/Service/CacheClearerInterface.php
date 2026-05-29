<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Cache-clear boundary (`doClearCache`).
 *
 * EC-CUBE's `CacheController` deletes the Twig / Symfony cache
 * directories on POST. That filesystem/runtime side-effect stays behind
 * this boundary so {@see \MyVendor\BeMart\Be\Final\CacheCleared} depends
 * only on an interface.
 */
interface CacheClearerInterface
{
    /** Clear the application cache. Returns the number of clears performed (observability). */
    public function clear(): int;
}
