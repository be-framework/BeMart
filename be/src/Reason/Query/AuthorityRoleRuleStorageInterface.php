<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AuthorityRoleRuleEntity;
use MyVendor\BeMart\Be\Reason\Query\Param\AuthorityRoleRuleList;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorityRoleRuleStorageInterface
{
    /** @return list<AuthorityRoleRuleEntity> */
    #[DbQuery('authority_role_list')]
    public function list(): array;

    #[DbQuery('authority_role_delete_all')]
    public function deleteAll(): void;

    #[DbQuery('authority_role_insert')]
    public function insert(AuthorityRoleRuleList $rules, string|null $creatorId): void;
}
