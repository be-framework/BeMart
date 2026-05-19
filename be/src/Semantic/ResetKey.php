<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ResetKeyFormatException;

use function ctype_print;
use function mb_strlen;

/**
 * ResetKey — opaque token EC-CUBE emails for password-reset (Pilot 14
 * issues it, this Pilot consumes it). Format: URL-safe printable ASCII,
 * 16-128 characters. Distinct Semantic class from SecretKey only so the
 * format-error message can be reset-specific; the validation rules are
 * deliberately the same shape envelope as Pilot 7's SecretKey.
 */
final class ResetKey
{
    #[Validate]
    public function validate(string $resetKey): void
    {
        $length = mb_strlen($resetKey);
        if ($length < 16 || $length > 128 || ! ctype_print($resetKey)) {
            throw new ResetKeyFormatException();
        }
    }
}
