<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;

/**
 * In-memory Admin command — Wave 8. Delegates to the singleton
 * {@see FakeAdminStorage} so writes are visible to subsequent reads
 * within the same request / test.
 */
final class FakeAdminCommand implements AdminCommandInterface
{
    public function __construct(
        private readonly FakeAdminStorage $storage,
    ) {
    }

    #[Override]
    public function create(AdminEntity $admin): void
    {
        $this->storage->put($admin);
    }

    #[Override]
    public function update(AdminEntity $admin): void
    {
        $this->storage->replace($admin);
    }

    #[Override]
    public function delete(string $adminId): void
    {
        $this->storage->softDelete($adminId);
    }

    #[Override]
    public function updateAuthority(string $adminId, int $newAuthority): void
    {
        $this->storage->updateAuthority($adminId, $newAuthority);
    }
}
