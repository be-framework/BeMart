<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function password_hash;
use function password_verify;

use const PASSWORD_DEFAULT;

/**
 * Wraps password_hash() with PASSWORD_DEFAULT (bcrypt at PHP 8.4).
 * Identical to the production implementation; the "Fake" prefix is
 * convention only — there is no in-test cheaper shortcut because
 * password_hash is the contract.
 */
final class FakePasswordHasher implements PasswordHasherInterface
{
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
