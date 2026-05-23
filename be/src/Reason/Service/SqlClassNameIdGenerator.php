<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\MediaQueryExecutor;
use Override;

final class SqlClassNameIdGenerator implements ClassNameIdGeneratorInterface
{
    public function __construct(
        private readonly MediaQueryExecutor $db,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $row = $this->db->row('className_next_id');

        return (string) ($row['next_id'] ?? '1');
    }
}
