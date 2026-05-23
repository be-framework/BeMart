<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\MediaQueryExecutor;
use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Override;

final class SqlCustomerIdGenerator implements CustomerIdGeneratorInterface
{
    public function __construct(
        private readonly MediaQueryExecutor $db,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $row = $this->db->row('customer_next_id');

        return (new GeneratedId((string) ($row['next_id'] ?? '1')))->value();
    }
}
