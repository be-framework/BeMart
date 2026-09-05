<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Client IP of the request being authenticated.
 *
 * The login audit log records where an attempt came from, and domain
 * code must not read `$_SERVER` to find out. The HTTP adapter lives in
 * the application layer ({@see \MyVendor\BeMart\Compatibility\Eccube\EccubeClientIp}),
 * so Fake and CLI contexts bind a fixed value instead of pretending a
 * request exists.
 */
interface ClientIpInterface
{
    /** Client IP, or an empty string when the caller has no remote address (CLI, internal call). */
    public function address(): string;
}
