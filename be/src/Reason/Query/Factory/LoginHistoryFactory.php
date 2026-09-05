<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;

/**
 * MySQL has no boolean and `client_ip` is a nullable longtext, so the
 * audit rows need coercion before they reach the readonly entity.
 */
final class LoginHistoryFactory
{
    public function factory(
        string $createDate,
        string|null $loginId,
        bool|int|string $success,
        string|null $clientIp = null,
    ): LoginHistoryEntity {
        return new LoginHistoryEntity(
            $createDate,
            $loginId ?? '',
            (bool) (int) $success,
            $clientIp ?? '',
        );
    }
}
