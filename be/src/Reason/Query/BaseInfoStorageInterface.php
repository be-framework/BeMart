<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;

/**
 * BaseInfo storage — unified Query + Command (Wave 8, doUpdateBaseInfo).
 *
 * dtb_base_info is a single-row table — the contract is therefore a
 * trivial get + replace pair. No filtering, no listing.
 *
 *   - get()                         → the one BaseInfoEntity (never null
 *                                       — initial install seeds defaults).
 *   - update(BaseInfoEntity $entity) → replace the row wholesale.
 *
 * Idempotency: doUpdateBaseInfo is ALPS `type=idempotent`; repeatedly
 * applying the same update is a no-op-equivalent (the row ends up
 * the same). The storage does not signal "changed" — the Final
 * compares old vs new and reports `changed=false` when they match.
 */
interface BaseInfoStorageInterface
{
    public function get(): BaseInfoEntity;

    public function update(BaseInfoEntity $entity): void;
}
