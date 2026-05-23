<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\EmailUniqueness;
use Override;

final class FakeEmailUniquenessChecker implements EmailUniquenessCheckerInterface
{
    public function __construct(
        private readonly FakeCustomerStorage $storage,
    ) {
    }

    #[Override]
    public function check(string $email): EmailUniqueness
    {
        return new EmailUniqueness(! $this->storage->existsByEmail($email));
    }
}
