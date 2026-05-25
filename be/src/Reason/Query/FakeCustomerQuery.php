<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;

final class FakeCustomerQuery implements CustomerQueryInterface
{
    public function __construct(
        private readonly FakeCustomerStorage $storage,
    ) {
    }

    #[Override]
    public function findByEmail(string $email): CustomerEntity|null
    {
        return $this->storage->getByEmail($email);
    }

    #[Override]
    public function findBySecretKey(string $secretKey): CustomerEntity|null
    {
        return $this->storage->getBySecretKey($secretKey);
    }

    #[Override]
    public function findById(string $customerId): CustomerEntity|null
    {
        return $this->storage->getById($customerId);
    }

    /**
     * @return list<CustomerEntity>
     */
    #[Override]
    public function search(?string $nameKeyword, ?string $emailKeyword, int $limit = 50): array
    {
        return $this->storage->search($nameKeyword, $emailKeyword, $limit);
    }
}
