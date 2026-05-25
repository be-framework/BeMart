<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MemberNameFormatException;

use function mb_strlen;

/**
 * Admin display name — ALPS descriptor `memberName` (Wave 8).
 *
 * EC-CUBE dtb_member.name is a varchar(255). Non-empty + length cap
 * 255. No charset enforcement; admin names are free-form Japanese in
 * production.
 *
 * The parameter is nullable to support partial-update transitions
 * (e.g. doUpdateMember leaves the name unchanged when null); null is
 * treated as "no validation needed". The semantic name "memberName"
 * matches both the create/update parameter `name` would not — Be
 * dispatches by parameter name, so callers using `$memberName` get
 * this Semantic. (We keep `name` parameters dispatched against
 * Be\Semantic\Name01 etc. as before via class names.)
 */
final class MemberName
{
    #[Validate]
    public function validate(string|null $memberName): void
    {
        if ($memberName === null) {
            return;
        }

        $length = mb_strlen($memberName);
        if ($length < 1 || $length > 255) {
            throw new MemberNameFormatException();
        }
    }
}
