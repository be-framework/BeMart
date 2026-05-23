<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One admin-login-attempt audit row — Wave 8 (goLoginHistoryList).
 *
 * Mirrors EC-CUBE's eccube_login_history: every admin login attempt
 * is logged with timestamp / loginId / success flag / client IP for
 * security review. The grid is admin-only.
 *
 * `timestamp` is a string (ISO-8601) to keep the fixture trivial and
 * avoid coupling to a clock interface for Wave 8; Phase 2 will swap
 * for a DateTimeImmutable + ClockInterface.
 */
final readonly class LoginHistoryEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $timestamp,
        public string $loginId,
        public bool $success,
        public string $clientIp,
    ) {
    }
}
