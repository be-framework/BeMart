<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One admin-login-attempt audit row as the grid reads it.
 *
 * Mirrors EC-CUBE's eccube_login_history: every admin login attempt is
 * logged with timestamp / loginId / success flag / client IP for
 * security review. The grid is admin-only.
 *
 * Read shape only — appends state no timestamp
 * ({@see \MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface::append()}),
 * so `timestamp` is the string the store recorded, passed through to the
 * renderer as-is (MySQL datetime from SQL, ISO-8601 from the Fake).
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
