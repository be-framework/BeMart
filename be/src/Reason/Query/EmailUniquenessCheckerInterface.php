<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

interface EmailUniquenessCheckerInterface
{
    /**
     * Verify that no active customer already uses this email.
     *
     * @throws \MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException
     */
    public function ensureUnique(string $email): void;
}
