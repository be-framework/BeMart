<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\MemberCreating;

/**
 * Input for doCreateMember — admin creates a new admin (Wave 8).
 *
 * Multi-Reason Being pattern (blog-publishing demo), mirroring Wave 5O
 * AdminCustomerCreating:
 *
 *   CreateMemberInput
 *     → MemberCreating (single Being, multi-Reason + admin AUTHZ)
 *     → MemberCreated  (Final — persistence proof)
 *
 * The required surface (`loginId`, `password`, `name`, `authority`)
 * matches `doCreateMember.descriptor[]` in alps.json (memberName /
 * loginId / password / authority / work). EC-CUBE 4.3 dtb_member has
 * no email column, so no mailAddress / mail input is accepted.
 *
 * Be Framework G-17: this Input has its own dedicated Being / Final
 * pair rather than reusing AdminCustomerCreating — different entity
 * (Admin vs Customer), different uniqueness key (loginId vs email),
 * different password discipline (admin bcrypt).
 *
 * Authority shape: 0=system admin / 1=shop owner (closed set). The
 * Semantic ensures the supplied authority is in the set; the runtime
 * privilege-escalation guard lives in the Being.
 *
 * @link https://schema.org/RegisterAction
 */
#[Be(MemberCreating::class)]
final readonly class CreateMemberInput
{
    /**
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $password
     * @psalm-taint-source input $name
     * @psalm-taint-source input $authority
     */
    public function __construct(
        public string $loginId,
        public string $password,
        public string $name,
        public int $authority,
    ) {
    }
}
