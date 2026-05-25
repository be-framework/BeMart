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
        int|string $authorityId,
        int|string $workId,
    ): AdminEntity {
        return new AdminEntity((string) $id, $loginId, $password, $name, (int) $authorityId, (int) $workId);
    }
}
