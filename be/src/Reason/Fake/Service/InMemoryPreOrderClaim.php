<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\ClaimedOrderNo;
use MyVendor\BeMart\Be\Reason\Service\PreOrderClaimInterface;
use Override;

use function array_filter;
use function is_array;
use function is_string;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Fake compare-and-swap: first caller per preOrderId wins, later ones are
 * told who won.
 *
 * Session-backed when a session is active so a browser Fake flow keeps the
 * verdict across requests, instance-backed otherwise. There is no
 * concurrency to arbitrate in the Fake context; what this preserves is the
 * SQL adapter's observable contract, including the replay case a single
 * process can reach — POSTing the same preOrderId twice.
 */
final class InMemoryPreOrderClaim implements PreOrderClaimInterface
{
    private const SESSION_KEY = 'bemart_fake_pre_order_claims';

    /** @var array<string, string> preOrderId => winning orderNo */
    private array $claims = [];

    #[Override]
    public function claim(string $preOrderId, string $orderNo): ClaimedOrderNo
    {
        $claims = $this->read();
        if (! isset($claims[$preOrderId])) {
            $claims[$preOrderId] = $orderNo;
            $this->write($claims);
        }

        return new ClaimedOrderNo($claims[$preOrderId]);
    }

    /** @return array<string, string> */
    private function read(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $this->claims;
        }

        /** @var mixed $claims */
        $claims = $_SESSION[self::SESSION_KEY] ?? [];

        return is_array($claims) ? array_filter($claims, is_string(...)) : [];
    }

    /** @param array<string, string> $claims */
    private function write(array $claims): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->claims = $claims;

            return;
        }

        $_SESSION[self::SESSION_KEY] = $claims;
    }
}
