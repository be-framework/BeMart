<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\TwoFactorAuthSecret;
use Ray\MediaQuery\Annotation\DbQuery;

interface TwoFactorAuthStorageInterface
{
    #[DbQuery('admin_two_factor_secret')]
    public function secret(string $loginId): TwoFactorAuthSecret;

    #[DbQuery('admin_two_factor_enable')]
    public function enable(string $loginId, string $secret): void;
}
