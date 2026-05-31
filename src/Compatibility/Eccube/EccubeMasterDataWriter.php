<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use Override;

use function count;

/**
 * EC-CUBE-compatible master-data bulk-write boundary.
 *
 * Records the last write per master type in process (bound as a
 * singleton) so `doUpdateMasterData` is exercisable end to end. Wiring the
 * real per-master `mtb_*` persistence is the production cutover residual
 * (migration-status §4).
 */
final class EccubeMasterDataWriter implements MasterDataWriterInterface
{
    /** @var array<string, list<array{id: string, name: string, sortNo?: int}>> */
    private array $written = [];

    /**
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     */
    #[Override]
    public function update(string $masterType, array $rows): int
    {
        $this->written[$masterType] = $rows;

        return count($rows);
    }
}
