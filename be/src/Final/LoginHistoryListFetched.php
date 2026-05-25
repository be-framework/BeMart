<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Login history list fetched — Final, admin view of admin-login
 * audit log (Wave 8 goLoginHistoryList).
 *
 *   GetLoginHistoryListInput → LoginHistoryListFetched
 *                              (Direct, safe read)
 *
 * AUTHZ — admin firewall: null admin session → 403.
 *
 * Public surface — flat projection of LoginHistoryEntity. Each row
 * carries `timestamp / loginId / success / clientIp`. No projection
 * filtering needed here (the entity already only carries audit-safe
 * fields).
 */
final readonly class LoginHistoryListFetched
{
    /** @var list<array{timestamp: string, loginId: string, success: bool, clientIp: string}> */
    public array $entries;

    public int $count;

    public function __construct(
        #[Input] int $limit,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] LoginHistoryStorageInterface $history,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $history->listRecent($limit);

        $this->entries = array_map(
            static fn (LoginHistoryEntity $e): array => [
                'timestamp' => $e->timestamp,
                'loginId' => $e->loginId,
                'success' => $e->success,
                'clientIp' => $e->clientIp,
            ],
            $rows,
        );
        $this->count = count($rows);
    }
}
