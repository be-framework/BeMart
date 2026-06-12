<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AuthorityRoleRuleEntity;
use MyVendor\BeMart\Be\Reason\Query\AuthorityRoleRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\AuthorityRoleRuleList;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;
use function ctype_digit;
use function is_array;
use function is_int;
use function is_scalar;
use function trim;

final readonly class AuthorityRulesUpdated
{
    /** @var list<array{authority: int, denyUrl: string}> */
    public array $rules;

    public int $count;

    /**
     * @param array<array-key, mixed> $authorityRoles
     */
    public function __construct(
        #[Input] array $authorityRoles,
        #[Inject] AdminSession $adminSession,
        #[Inject] AuthorityRoleRuleStorageInterface $storage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entities = $this->normalize($authorityRoles);
        $storage->deleteAll();
        if ($entities !== []) {
            $storage->insert(
                AuthorityRoleRuleList::fromArray($entities),
                $adminSession->adminId,
            );
        }

        $this->rules = $this->toRows($entities);
        $this->count = count($entities);
    }

    /**
     * @param array<array-key, mixed> $authorityRoles
     *
     * @return list<AuthorityRoleRuleEntity>
     */
    private function normalize(array $authorityRoles): array
    {
        $rules = [];
        foreach ($authorityRoles as $row) {
            if (! is_array($row)) {
                continue;
            }

            $denyUrl = $this->stringValue($row['deny_url'] ?? $row['denyUrl'] ?? null);
            if ($denyUrl === '') {
                continue;
            }

            $rules[] = new AuthorityRoleRuleEntity(
                authority: $this->authorityValue($row['Authority'] ?? $row['authority'] ?? 1),
                denyUrl: $denyUrl,
            );
        }

        return $rules;
    }

    private function stringValue(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function authorityValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            $authority = trim((string) $value);
            if (ctype_digit($authority)) {
                return (int) $authority;
            }
        }

        return 1;
    }

    /**
     * @param list<AuthorityRoleRuleEntity> $entities
     *
     * @return list<array{authority: int, denyUrl: string}>
     */
    private function toRows(array $entities): array
    {
        $rows = [];
        foreach ($entities as $entity) {
            $rows[] = [
                'authority' => $entity->authority,
                'denyUrl' => $entity->denyUrl,
            ];
        }

        return $rows;
    }
}
