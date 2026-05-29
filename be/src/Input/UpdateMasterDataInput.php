<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MasterDataUpdated;

/**
 * Input for `doUpdateMasterData` — an admin bulk-edits a master's rows
 * (Hard ActionRedirect completion).
 *
 *   UpdateMasterDataInput → MasterDataUpdated   (Direct, idempotent, admin AUTHZ)
 *
 * `masterType` is validated by {@see \MyVendor\BeMart\Be\Semantic\MasterType}.
 * ALPS marks it `idempotent` — the rows are written as explicit values.
 * The destructive bulk write is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface}.
 */
#[Be(MasterDataUpdated::class)]
final readonly class UpdateMasterDataInput
{
    /**
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     *
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rows
     */
    public function __construct(
        public string $masterType,
        public array $rows = [],
    ) {
    }
}
