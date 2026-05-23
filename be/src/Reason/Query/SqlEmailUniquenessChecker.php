<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\EmailUniqueness;
use Override;

final class SqlEmailUniquenessChecker implements EmailUniquenessCheckerInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function check(string $email): EmailUniqueness
    {
        return new EmailUniqueness($this->db->row('customer_email_exists', ['email' => $email]) === null);
    }
}
