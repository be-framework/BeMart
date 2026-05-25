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

    /**
     * @return list<AdminEntity>
     */
    #[Override]
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->storage->listAll($limit, $offset);
    }

    /**
     * @return list<AdminEntity>
     */
    #[Override]
    public function search(string|null $nameKeyword): array
    {
        return $this->storage->search($nameKeyword);
    }
}
