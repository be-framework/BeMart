<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Param;

use MyVendor\BeMart\Be\Reason\Entity\AuthorityRoleRuleEntity;
use Override;
use Ray\MediaQuery\ToScalarInterface;

use function array_map;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class AuthorityRoleRuleList implements ToScalarInterface
{
    /** @param list<AuthorityRoleRuleEntity> $rules */
    public function __construct(public array $rules)
    {
    }

    /** @param list<AuthorityRoleRuleEntity> $rules */
    public static function fromArray(array $rules): self
    {
        return new self($rules);
    }

    #[Override]
    public function toScalar(): string
    {
        return json_encode(array_map(
            static fn (AuthorityRoleRuleEntity $rule): array => [
                'authority' => $rule->authority,
                'denyUrl' => $rule->denyUrl,
            ],
            $this->rules,
        ), JSON_THROW_ON_ERROR);
    }
}
