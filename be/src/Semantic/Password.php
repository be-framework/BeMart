<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;

use function mb_strlen;

/**
 * Password — plaintext as submitted. Stored only long enough to hand
 * to PasswordHasherInterface; never persisted in clear form.
 */
final class Password
{
    #[Validate]
    public function validate(string $password): void
    {
        $length = mb_strlen($password);
        if ($length < 8 || $length > 255) {
            throw new PasswordFormatException();
        }
    }
}
