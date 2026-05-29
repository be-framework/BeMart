<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Rows — the edited master rows submitted on the masterdata editor
 * (doUpdateMasterData), each `{id, name, sortNo?}`. The per-row shape is
 * applied by the boundary writer; the ontology only names the variable.
 *
 * @param list<array{id: string, name: string, sortNo?: int}> $rows
 */
final class Rows
{
    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    #[Validate]
    public function validate(array $rows): void
    {
    }
}
