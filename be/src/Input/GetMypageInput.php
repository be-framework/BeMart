<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MypageFetched;

/**
 * Input for goMypage — render the logged-in customer's dashboard.
 *
 * Direct pattern: Input → Final. The Final injects SessionInterface
 * to resolve the customerId (AUTHN), then aggregates profile + recent
 * orders + favorite count for the dashboard projection.
 *
 * AUTHZ design — mass-assignment safety:
 *   The customerId is INTENTIONALLY ABSENT from this Input. It comes
 *   exclusively from the session. The dashboard always renders "your
 *   own page"; there is no way to view another customer's by tampering
 *   with request parameters. (Pilot 5 F-2 lesson carried forward.)
 *
 * The single tunable `$orderLimit` caps the recent-orders panel; the
 * dashboard does not need the full history (goMypageHistory drills
 * down for that).
 *
 * @link https://schema.org/ViewAction
 */
#[Be(MypageFetched::class)]
final readonly class GetMypageInput
{
    public function __construct(
        public int $orderLimit = 5,
    ) {
    }
}
