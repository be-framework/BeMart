<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin entity — projection of EC-CUBE 4.3 dtb_member for the admin
 * authentication pipeline. Distinct from {@see CustomerEntity}: admins
 * log in by `loginId` (not email), carry a numeric `authority` level
 * (0=system admin / 1=shop owner — mirroring EC-CUBE's mtb_authority),
 * and are a different AAA principal class.
 *
 * Wave 4 introduces this entity for doAdminLogin / doAdminLogout. The
 * shape intentionally mirrors {@see CustomerEntity} in spirit — opaque
 * id + login-key + hashed password + display fields — but the field
 * names follow the EC-CUBE Member shape (loginId, authority, name).
 *
 * Wave 8 adds `work` (EC-CUBE mtb_work: 0=NON_ACTIVE / 1=ACTIVE) so the
 * member-management surface can soft-delete admins without losing the
 * audit row. The login flow already implicitly assumed all admins were
 * active; Wave 8 keeps the default at WORK_ACTIVE so existing fixtures
 * stay backward-compatible.
 *
 * Note: this Be project's source-of-truth ALPS profile does not yet
 * carry `doAdminLogin` / `doAdminLogout` transition ids (only customer
 * `doLogin` / `doLogout` are present). The admin actor exists in the
 * ALPS through descriptors like `loginId`, `memberName`, `authority`,
 * but the explicit admin auth state transitions are tracked here as a
 * Be foundation; the ALPS profile is expected to gain matching
 * descriptors in a follow-up sweep.
 */
final readonly class AdminEntity
{
    /** Active admin — can log in. Mirrors EC-CUBE mtb_work=1. */
    public const int WORK_ACTIVE = 1;

    /** Inactive admin — soft-deleted, cannot log in. mtb_work=0. */
    public const int WORK_INACTIVE = 0;

    public function __construct(
        public string $adminId,
        public string $loginId,
        public string $passwordHash,
        public string $name,
        public int $authority,
        public int $work = self::WORK_ACTIVE,
    ) {
    }
}
