<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin authority URL-deny rule, projected from EC-CUBE dtb_authority_role.
 */
final readonly class AuthorityRoleRuleEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public int $authority,
        public string $denyUrl,
    ) {
    }
}
