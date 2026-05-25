<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Trade-law updated — Final, proof an admin edited the trade-law
 * page text.
 *
 *   UpdateTradeLawInput → TradeLawUpdated (Direct, idempotent)
 *
 * AUTHZ — admin firewall: AdminSession::$adminId === null →
 * UnauthorizedAdminAccessException (403).
 *
 * Idempotency: when the new body equals the persisted body, the
 * storage write is skipped and the Final reports `changed=false`.
 */
final readonly class TradeLawUpdated
{
    public string $tradeLawBody;
    public bool $changed;

    public function __construct(
        #[Input] string $tradeLawBody,
        #[Inject] AdminSession $adminSession,
        #[Inject] TradeLawStorageInterface $tradeLawStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $existing = $tradeLawStorage->item();
        $changed = $existing->body !== $tradeLawBody;

        if ($changed) {
            $tradeLawStorage->put(new TradeLawEntity(body: $tradeLawBody));
        }

        $this->tradeLawBody = $tradeLawBody;
        $this->changed = $changed;
    }
}
