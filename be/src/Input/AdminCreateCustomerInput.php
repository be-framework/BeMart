<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\AdminCustomerCreating;

/**
 * Input for doCreateCustomer — admin creates a customer (management screen).
 *
 * Multi-Reason Being pattern (blog-publishing demo), mirroring Pilot 4
 * doRegisterCustomer with two changes:
 *
 *   1. AUTHZ: requires an admin session at the first Being. The
 *      front-end self-registration variant is anonymous-accessible;
 *      this admin variant rejects requests where the admin firewall
 *      has not granted access (raises {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException}).
 *   2. `customerStatus` is immediately 2 (Active). ALPS doc:
 *      "仮会員フラグなしで即時本会員として登録". No provisional flag,
 *      no email-verification round-trip.
 *
 *   AdminCreateCustomerInput
 *     → AdminCustomerCreating (single Being, multi-Reason + admin AUTHZ)
 *     → AdminCustomerCreated  (Final — persistence proof)
 *
 * Be Framework G-17 (Pilot 10): the `#[Be]` chain destination is
 * fixed at the class level. Reusing Pilot 4's CustomerRegistering
 * would force the admin path through CustomerRegistered (the wrong
 * Final), so the admin chain is its own dedicated Being / Final pair
 * — different Input intent ⇒ different Being shape.
 *
 * The required surface (`email`, `password`, `name01`, `name02`)
 * matches `doCreateCustomer.descriptor[]` in alps.json. The 11
 * optional fields are inherited from RegisterCustomerInput so EC-CUBE's
 * shared CustomerRegistration form can drive both endpoints.
 *
 * @see RegisterCustomerInput  Pilot 4 (front-end self-registration)
 * @link https://schema.org/RegisterAction
 */
#[Be(AdminCustomerCreating::class)]
final readonly class AdminCreateCustomerInput
{
    /**
     * Phase B Slice 9: all 15 properties come from the HTTP form. Marked
     * as input sources for the boundary contract; format validation
     * happens via Be Semantic at metamorphosis time.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $birth
     * @psalm-taint-source input $sex
     * @psalm-taint-source input $job
     */
    public function __construct(
        public string $email,
        public string $password,
        public string $name01,
        public string $name02,
        public string|null $kana01 = null,
        public string|null $kana02 = null,
        public string|null $companyName = null,
        public string|null $phoneNumber = null,
        public string|null $postalCode = null,
        public int|null $pref = null,
        public string|null $addr01 = null,
        public string|null $addr02 = null,
        public string|null $birth = null,
        public int|null $sex = null,
        public int|null $job = null,
    ) {
    }
}
