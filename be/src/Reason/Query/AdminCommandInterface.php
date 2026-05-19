<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;

/**
 * Write-side Admin command — Wave 8 (admin member CRUD).
 *
 * Split from {@see AdminQueryInterface} to keep CQRS boundaries explicit
 * — same convention as customer-side {@see CustomerCommandInterface}.
 * All four mutators are admin-only — AUTHZ lives in the calling Be Final
 * (which checks AdminSessionInterface before reaching this contract).
 *
 * The shape choice (full-entity put for create / replace, scalar surface
 * for soft-delete + authority flip) mirrors the customer-side split:
 *   - `create` / `update` mass-write the whole entity (the Final has
 *     already merged editable fields onto the current row);
 *   - `delete` / `updateAuthority` flip a single column so callers
 *     cannot reach unrelated fields through this contract (mass-
 *     assignment safety, Pilot 5 F-2 lesson).
 */
interface AdminCommandInterface
{
    /**
     * Insert a new admin row — Wave 8 (doCreateMember). The supplied
     * entity carries the already-hashed password and a freshly-generated
     * adminId; this method is pure persistence.
     */
    public function create(AdminEntity $admin): void;

    /**
     * Replace an existing admin row — Wave 8 (doUpdateMember). The
     * supplied entity carries the merged shape (current row + edited
     * fields); preserving fields outside the update form (passwordHash
     * / authority / work) is the Final's responsibility.
     */
    public function update(AdminEntity $admin): void;

    /**
     * Soft-delete: flip the `work` flag to NON_ACTIVE (0). Mirrors
     * EC-CUBE which keeps the row for audit / FK integrity and only
     * flips a flag. Wave 8 (doDeleteMember).
     */
    public function delete(string $adminId): void;

    /**
     * Flip the `authority` column for an admin — Wave 8
     * (doUpdateAuthorityRole). Smaller surface than `update` because
     * the role-flip path should not be able to reach other columns.
     */
    public function updateAuthority(string $adminId, int $newAuthority): void;
}
