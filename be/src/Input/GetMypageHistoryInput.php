<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MypageHistoryFetched;

/**
 * Input for goMypageHistory — read one of the logged-in customer's
 * past orders (header + items).
 *
 *   GetMypageHistoryInput → MypageHistoryFetched (Final — Direct)
 *
 * Safe read. AUTHN + AUTHZ live in the Final: the customerId
 * INTENTIONALLY does not appear here. It is taken from the session
 * exclusively, so a malicious client cannot view another customer's
 * order detail by tampering with request parameters (Pilot 5 F-2
 * lesson, mirrored by Pilot 8 and Pilot 12).
 *
 * Pilot 12 (doReorder) takes the same `orderNo` shape — keeping the
 * Input signatures aligned lets the BEAR layer point doReorder's
 * Location at this resource without translation.
 */
#[Be(MypageHistoryFetched::class)]
final readonly class GetMypageHistoryInput
{
    /**
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
    ) {
    }
}
