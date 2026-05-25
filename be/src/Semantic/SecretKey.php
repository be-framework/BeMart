<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SecretKeyFormatException;

use function ctype_print;
use function mb_strlen;

/**
 * SecretKey — opaque token EC-CUBE emails for email-verification on
 * provisional accounts. Format: URL-safe printable ASCII, 16-128
 * characters. The actual token comes from the email link query
 * string; this Semantic only enforces a sane envelope.
 */
final class SecretKey
{
    #[Validate]
    public function validate(string $secretKey): void
    {
        $length = mb_strlen($secretKey);
        if ($length < 16 || $length > 128 || ! ctype_print($secretKey)) {
            throw new SecretKeyFormatException();
        }
    }
}
