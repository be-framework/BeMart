<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use Override;

use function end;
use function explode;
use function is_string;
use function trim;

/**
 * EC-CUBE-compatible client IP boundary.
 *
 * EC-CUBE stores `Symfony\Request::getClientIp()` in
 * `dtb_login_history.client_ip`. Behind the demo's reverse proxy
 * (Caddy in the same compose network) REMOTE_ADDR is always the
 * proxy's container IP, so every visitor would share one address — and
 * one login-throttle key. This adapter therefore takes the LAST hop of
 * `X-Forwarded-For` when the header is present — the hop the nearest
 * proxy appended — and falls back to REMOTE_ADDR otherwise.
 *
 * Residual: with NO proxy in front, a client can supply the header and
 * pick its own address, so the throttle key and the audit row's
 * client_ip record what the client chose. That is evasion of the
 * client's own counter, not lockout of others, and it is the same
 * trusted-proxy caveat Symfony documents for getClientIp(); while a
 * proxy is in front, a client-supplied header cannot displace the
 * proxy-appended last hop.
 *
 * CLI and internal calls have no remote address; they record an empty
 * IP rather than a plausible-looking lie.
 */
final class EccubeClientIp implements ClientIpInterface
{
    #[Override]
    public function address(): string
    {
        /** @var mixed $forwarded */
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded) && $forwarded !== '') {
            $hops = explode(',', $forwarded);
            /** @var mixed $last */
            $last = end($hops);
            if (is_string($last)) {
                $address = trim($last);
                if ($address !== '') {
                    return $address;
                }
            }
        }

        /** @var mixed $address */
        $address = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($address) ? $address : '';
    }
}
