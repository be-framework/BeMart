<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerAddressListFetched;

/**
 * Input for goCustomerAddressList — list the logged-in customer's
 * address book (Pilot 16).
 *
 *   GetCustomerAddressListInput → CustomerAddressListFetched
 *
 * Safe read. AUTHN lives in the Final: the customerId INTENTIONALLY
 * does not appear here — it is taken from the session exclusively,
 * so a malicious client cannot enumerate another customer's address
 * book by tampering with request parameters (Pilot 5 F-2 lesson,
 * carried by Pilots 8 / 12 / 13).
 *
 * @link https://schema.org/ViewAction
 */
#[Be(CustomerAddressListFetched::class)]
final readonly class GetCustomerAddressListInput
{
    public function __construct()
    {
    }
}
