<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MemberNameFormatException;

use function mb_strlen;

/**
 * Generic display name — dispatched for parameters named `name`.
 *
 * Wave 8 introduces this for admin members (CreateMemberInput /
 * UpdateMemberInput use `$name` rather than `$memberName`, matching
 * the AdminEntity column name). Reuses
 * {@see MemberNameFormatException} — the constraints are identical
 * (non-empty + length cap 255).
 *
 * Nullable to support partial-update transitions (doUpdateMember
 * leaves the name unchanged when null).
 */
final class Name
{
    #[Validate]
    public function validate(string|null $name): void
    {
        if ($name === null) {
            return;
        }

        $length = mb_strlen($name);
        if ($length < 1 || $length > 255) {
            throw new MemberNameFormatException();
        }
    }
}
