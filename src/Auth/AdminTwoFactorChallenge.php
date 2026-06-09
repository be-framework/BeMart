<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

/** Pending admin login identity bound to a pre-auth 2FA step. */
final readonly class AdminTwoFactorChallenge
{
    public function __construct(
        public string $adminId,
        public string $loginId,
        public string|null $authKey = null,
    ) {
    }
}
