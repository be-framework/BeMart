<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\SortNoMoved;

/**
 * Input for `doSortNoMove` — the generic admin-list reorder transition
 * (Phase 3 ALPS-audit remediation).
 *
 *   SortNoMoveInput → SortNoMoved   (Direct, idempotent, admin AUTHZ)
 *
 * EC-CUBE exposes a per-master *_sort_no_move route for each admin list
 * screen (Payment / Delivery / Tag / ClassName / ClassCategory). BeMart
 * folds them into one transition: `masterType` names the master, `rowId`
 * the row, `sortNo` the new display slot. The operation is idempotent —
 * re-sending the same (masterType, rowId, sortNo) leaves the same state.
 *
 * The constructor parameters are named to match their Semantic
 * validators ({@see \MyVendor\BeMart\Be\Semantic\MasterType},
 * {@see \MyVendor\BeMart\Be\Semantic\SortNo}); `rowId` is an opaque
 * master row id and carries no Semantic of its own (each master's id
 * shape differs — the registry resolves it).
 */
#[Be(SortNoMoved::class)]
final readonly class SortNoMoveInput
{
    /**
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $sortNo
     */
    public function __construct(
        public string $masterType,
        public string $rowId,
        public int $sortNo,
    ) {
    }
}
