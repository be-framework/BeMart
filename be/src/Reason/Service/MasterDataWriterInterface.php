<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generic master-data bulk-write boundary (`doUpdateMasterData`).
 *
 * EC-CUBE's masterdata editor rewrites the rows of an arbitrary `mtb_*`
 * master (id / name / sort_no). Spreading that destructive bulk update
 * across every per-master storage would widen each storage's surface, so
 * BeMart isolates it behind this boundary: the Be Final validates the
 * master type + AUTHZ and hands the rows here. Wiring the real per-master
 * persistence is the production cutover residual (migration-status §4).
 */
interface MasterDataWriterInterface
{
    /**
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     *
     * @return int number of rows written
     */
    public function update(string $masterType, array $rows): int;
}
