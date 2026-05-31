<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface;
use Ray\Di\Di\Inject;

/**
 * Cache cleared — Final, proof an admin triggered an application cache
 * clear (doClearCache).
 *
 *   ClearCacheInput → CacheCleared   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * filesystem purge is delegated to {@see CacheClearerInterface}.
 */
final readonly class CacheCleared
{
    public int $clears;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] CacheClearerInterface $cacheClearer,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->clears = $cacheClearer->clear();
    }
}
