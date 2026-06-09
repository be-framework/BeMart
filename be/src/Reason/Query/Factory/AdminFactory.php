<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;

final class AdminFactory
{
    public function factory(
        int|string $id,
        string $loginId,
        string $password,
        string $name,
        int|string|null $authorityId = 1,
        int|string|null $workId = AdminEntity::WORK_ACTIVE,
        int|string|null $sortNo = 0,
    ): AdminEntity {
        return new AdminEntity(
            (string) $id,
            $loginId,
            $password,
            $name,
            (int) ($authorityId ?? 1),
            (int) ($workId ?? AdminEntity::WORK_ACTIVE),
            (int) ($sortNo ?? 0),
        );
    }
}
