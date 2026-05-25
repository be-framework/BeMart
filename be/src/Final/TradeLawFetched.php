<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

/**
 * TradeLaw fetched — Final, the read-side projection of dtb_trade_law
 * (Wave 9, goTradeLawList).
 *
 *   GetTradeLawInput → TradeLawFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract):
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Public surface mirrors {@see TradeLawUpdated::tradeLawBody} so the
 * admin form pre-population matches the round-trip POST payload.
 * Single-blob shape — Phase 2 will split into per-item rows.
 */
final readonly class TradeLawFetched
{
    public string $tradeLawBody;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] TradeLawStorageInterface $tradeLawStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->tradeLawBody = $tradeLawStorage->item()->body;
    }
}
