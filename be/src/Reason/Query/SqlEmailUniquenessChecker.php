<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use Override;

final class SqlEmailUniquenessChecker implements EmailUniquenessCheckerInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function ensureUnique(string $email): void
    {
        if ($this->db->row('customer_email_exists', ['email' => $email]) !== null) {
            throw new EmailAlreadyRegisteredException();
        }
    }
}
