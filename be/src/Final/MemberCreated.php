<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Member created — Final, proof the new admin was persisted.
 *
 *   CreateMemberInput → MemberCreating (Multi-Reason + admin AUTHZ)
 *                     → MemberCreated  (this stage — persistence)
 *
 * Existence of this object proves AdminCommand::create() ran without
 * raising. Public surface mirrors the doCreateMember response shape:
 * identity + role of the new admin. The plaintext password is
 * intentionally NOT exposed here — only the server-side hash is held
 * on the upstream Being and is consumed by the persistence layer.
 *
 * Be Framework G-17: this Final is a sibling of Wave 5O's
 * {@see AdminCustomerCreated}, not a subclass. The two are
 * conceptually similar (admin-driven create + bcrypt) but operate on
 * different entities so they are namespaced separately.
 */
final readonly class MemberCreated
{
    public string $adminId;
    public string $loginId;
    public string $name;
    public string $mailAddress;
    public int $authority;
    public int $work;

    public function __construct(
        #[Input] string $adminId,
        #[Input] string $loginId,
        #[Input] string $passwordHash,
        #[Input] string $name,
        #[Input] string $mailAddress,
        #[Input] int $authority,
        #[Input] int $work,
        #[Inject] AdminCommandInterface $command,
    ) {
        $command->create(new AdminEntity(
            adminId: $adminId,
            loginId: $loginId,
            passwordHash: $passwordHash,
            name: $name,
            mailAddress: $mailAddress,
            authority: $authority,
            work: $work,
        ));

        $this->adminId = $adminId;
        $this->loginId = $loginId;
        $this->name = $name;
        $this->mailAddress = $mailAddress;
        $this->authority = $authority;
        $this->work = $work;
    }
}
