<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MypageChangeFormFetched;

/**
 * Input for goMypageChange — render the change-customer-info form
 * pre-populated with the logged-in customer's current values.
 *
 *   GetMypageChangeInput → MypageChangeFormFetched (Final — Direct)
 *
 * Zero-arg Input: there is nothing the request can carry that should
 * influence the form (the customer is always editing themselves). The
 * Final reads the SessionInterface for AUTHN and the customerId, and
 * the request body contributes nothing — the same mass-assignment
 * safety the goMypage dashboard relies on (Pilot 5 F-2 lesson).
 */
#[Be(MypageChangeFormFetched::class)]
final readonly class GetMypageChangeInput
{
    public function __construct()
    {
    }
}
