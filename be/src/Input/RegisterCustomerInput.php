<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\CustomerRegistering;

/**
 * Input for doRegisterCustomer — register a new customer (front-end).
 *
 * Multi-Reason Being pattern (blog-publishing demo).
 *
 *   RegisterCustomerInput
 *     → CustomerRegistering (single Being, multiple independent Reasons)
 *     → CustomerRegistered (Final — persistence proof)
 *
 * The required surface (`email`, `password`, `name01`, `name02`) is
 * declared by `doRegisterCustomer.descriptor[]` in alps.json. The
 * remaining 11 properties come from EC-CUBE's CustomerRegistration
 * form (kana, address, contact, attributes); they are nullable so
 * Pilot 4 can mirror EC-CUBE's behaviour where most of them are
 * optional at registration time.
 *
 * Pilot 4 scope: email verification OFF only (customerStatus = 2,
 * "registered + active" directly). The verification ON branch
 * (customerStatus = 1 → verify email → customerStatus = 2) is left
 * for a follow-up Branching pilot — Pilot 3 already validated the
 * Branching mechanics, so re-validating them here would not
 * uncover new findings.
 *
 * @link https://schema.org/RegisterAction
 */
#[Be(CustomerRegistering::class)]
final readonly class RegisterCustomerInput
{
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
