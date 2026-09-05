<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AuthorityRulesUpdated;

#[Be(AuthorityRulesUpdated::class)]
final readonly class UpdateAuthorityRulesInput
{
    /**
     * @param array<array-key, mixed> $authorityRoles
     *
     * @psalm-taint-source input $authorityRoles
     */
    public function __construct(
        public array $authorityRoles,
    ) {
    }
}
