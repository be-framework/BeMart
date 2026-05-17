<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;

final class FakeEmailUniquenessChecker implements EmailUniquenessCheckerInterface
{
    public function __construct(
        private readonly FakeCustomerStorage $storage,
    ) {
    }

    public function ensureUnique(string $email): void
    {
        if ($this->storage->existsByEmail($email)) {
            throw new EmailAlreadyRegisteredException();
        }
    }
}
