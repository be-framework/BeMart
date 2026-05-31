<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MasterDataSelected;

/**
 * Input for `doSelectMasterData` — an admin picks which master to view on
 * the masterdata screen (Hard ActionRedirect completion).
 *
 *   SelectMasterDataInput → MasterDataSelected   (Direct, idempotent, admin AUTHZ)
 *
 * `masterType` is validated by {@see \MyVendor\BeMart\Be\Semantic\MasterType}
 * (the known `mtb_*` set). ALPS marks it `idempotent` — selecting is a
 * read-through that returns the master's rows.
 */
#[Be(MasterDataSelected::class)]
final readonly class SelectMasterDataInput
{
    /** @psalm-taint-source input $masterType */
    public function __construct(
        public string $masterType,
    ) {
    }
}
