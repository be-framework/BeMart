<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;

final class FakeAdminQuery implements AdminQueryInterface
{
    public function __construct(
        private readonly FakeAdminStorage $storage,
    ) {
    }

    #[Override]
    public function findByLoginId(string $loginId): AdminEntity|null
    {
        return $this->storage->getByLoginId($loginId);
    }

    #[Override]
    public function findById(string $adminId): AdminEntity|null
    {
        return $this->storage->getById($adminId);
    }
}
