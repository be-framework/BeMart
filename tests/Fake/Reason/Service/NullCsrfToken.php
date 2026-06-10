<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use Override;

/**
 * Test-default CSRF adapter.
 *
 * Use this for tests whose subject is not CSRF. Dedicated CSRF boundary
 * tests should bind {@see FakeCsrfToken} or the production adapter
 * explicitly.
 */
final readonly class NullCsrfToken extends CsrfToken
{
    public const TOKEN = FakeCsrfToken::TOKEN;

    public function __construct()
    {
        parent::__construct(self::TOKEN);
    }

    #[Override]
    public function isValid(string|null $token): bool
    {
        return true;
    }
}
