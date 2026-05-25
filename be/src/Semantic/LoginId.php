<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\LoginIdFormatException;

use function mb_strlen;

/**
 * LoginId — EC-CUBE 4.3 dtb_member.login_id (descriptor `loginId` in
 * `alps.json`). Admin authentication uses this as the login key rather
 * than email (matching EC-CUBE's two-firewall split). Unique among
 * active admins; dynamic uniqueness is enforced elsewhere — this
 * Semantic checks only static shape.
 *
 * Static constraints only — non-empty + length cap 128 to match the
 * EC-CUBE Member.login_id column. No charset enforcement at this level
 * (production EC-CUBE allows letters/digits/punctuation per the admin
 * registration form; locking that down can be a later sweep).
 */
final class LoginId
{
    #[Validate]
    public function validate(string $loginId): void
    {
        $length = mb_strlen($loginId);
        if ($length < 1 || $length > 128) {
            throw new LoginIdFormatException();
        }
    }
}
