<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

use DateTimeImmutable;

/**
 * Pilot 14 (doRequestPasswordReset) — issued reset token. Held in
 * its own storage (not on CustomerEntity) so an evicted/expired
 * token doesn't pollute customer reads.
 *
 * EC-CUBE 4.3 stores `reset_key` and `reset_expire` directly on
 * dtb_customer; Phase 2 may collapse back to that shape. The
 * separation here keeps the Be Framework Final clean.
 */
final readonly class PasswordResetTokenEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public DateTimeImmutable $expiresAt;

    public function __construct(
        public string $customerId,
        public string $resetKey,
        DateTimeImmutable|string $expiresAt,
    ) {
        $this->expiresAt = $expiresAt instanceof DateTimeImmutable
            ? $expiresAt
            : new DateTimeImmutable($expiresAt);
    }
}
