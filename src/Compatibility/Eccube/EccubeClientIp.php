<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use Override;

use function is_string;

/**
 * EC-CUBE-compatible client IP boundary.
 *
 * EC-CUBE stores `Symfony\Request::getClientIp()` in
 * `dtb_login_history.client_ip`, which with no trusted proxies
 * configured is the connection's remote address — so this adapter reads
 * exactly that. Forwarded-for headers are deliberately NOT consulted:
 * they are client-supplied, and an audit row that an attacker can
 * choose the contents of is worse than one that records the last hop.
 *
 * CLI and internal calls have no remote address; they record an empty
 * IP rather than a plausible-looking lie.
 */
final class EccubeClientIp implements ClientIpInterface
{
    #[Override]
    public function address(): string
    {
        /** @var mixed $address */
        $address = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($address) ? $address : '';
    }
}
