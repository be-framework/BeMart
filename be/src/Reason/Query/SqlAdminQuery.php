<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;

use function ctype_digit;
use function str_replace;

final class SqlAdminQuery implements AdminQueryInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function findByLoginId(string $loginId): AdminEntity|null
    {
        $row = $this->db->row('admin_find_by_login', ['loginId' => $loginId]);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function findById(string $adminId): AdminEntity|null
    {
        if (! ctype_digit($adminId)) {
            return null;
        }
        $row = $this->db->row('admin_find_by_id', ['adminId' => $adminId]);
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<AdminEntity> */
    #[Override]
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return array_map($this->hydrate(...), $this->db->rows('admin_list', ['limit' => $limit, 'offset' => $offset]));
    }

    /** @return list<AdminEntity> */
    #[Override]
    public function search(string|null $nameKeyword): array
    {
        $query = $nameKeyword === null || $nameKeyword === '' ? 'admin_search_all' : 'admin_search_name';
        $values = $query === 'admin_search_name' ? ['pattern' => '%' . $this->escapeLike((string) $nameKeyword) . '%'] : [];
        return array_map($this->hydrate(...), $this->db->rows($query, $values));
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): AdminEntity
    {
        return new AdminEntity(
            adminId: (string) (int) $row['id'],
            loginId: (string) $row['login_id'],
            passwordHash: (string) $row['password'],
            name: $row['name'] === null ? '' : (string) $row['name'],
            authority: $row['authority_id'] === null ? 0 : (int) $row['authority_id'],
            work: $row['work_id'] === null ? AdminEntity::WORK_ACTIVE : (int) $row['work_id'],
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
