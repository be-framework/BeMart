<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function password_hash;
use function password_verify;

use const PASSWORD_DEFAULT;

/** Native PHP password hasher used by every context. */
final class NativePasswordHasher implements PasswordHasherInterface
{
    #[Override]
    public function hash(string $plaintext): string
    {
        return password_hash($plaintext, PASSWORD_DEFAULT);
    }

    #[Override]
    public function verify(string $plaintext, string $hash): bool
    {
        return password_verify($plaintext, $hash);
    }
}
