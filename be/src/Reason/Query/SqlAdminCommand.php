<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;

use function ctype_digit;

final class SqlAdminCommand implements AdminCommandInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function create(AdminEntity $admin): void
    {
        if (ctype_digit($admin->adminId)) {
            $this->db->exec('admin_create', $this->values($admin));
        }
    }

    #[Override]
    public function update(AdminEntity $admin): void
    {
        if (ctype_digit($admin->adminId)) {
            $this->db->exec('admin_update', $this->values($admin));
        }
    }

    #[Override]
    public function delete(string $adminId): void
    {
        if (ctype_digit($adminId)) {
            $this->db->exec('admin_delete', ['id' => (int) $adminId]);
        }
    }

    #[Override]
    public function updateAuthority(string $adminId, int $newAuthority): void
    {
        if (ctype_digit($adminId)) {
            $this->db->exec('admin_update_authority', ['id' => (int) $adminId, 'authority' => $newAuthority]);
        }
    }

    /** @return array<string, mixed> */
    private function values(AdminEntity $admin): array
    {
        return [
            'id' => (int) $admin->adminId,
            'work' => $admin->work,
            'authority' => $admin->authority,
            'name' => $admin->name,
            'loginId' => $admin->loginId,
            'password' => $admin->passwordHash,
        ];
    }
}
