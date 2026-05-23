<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\MediaQueryExecutor;
use Override;

final class SqlAdminIdGenerator implements AdminIdGeneratorInterface
{
    public function __construct(
        private readonly MediaQueryExecutor $db,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $row = $this->db->row('admin_next_id');
        $id = (string) ($row['next_id'] ?? '1');
        if ($id === '') {
            return '1';
        }

        return $id;
    }
}
