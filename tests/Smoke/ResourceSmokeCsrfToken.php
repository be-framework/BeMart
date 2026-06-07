<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use Override;

final readonly class ResourceSmokeCsrfToken extends CsrfToken
{
    public function __construct()
    {
        parent::__construct('resource-smoke-csrf-token');
    }

    #[Override]
    public function isValid(string|null $token): bool
    {
        return true;
    }
}
