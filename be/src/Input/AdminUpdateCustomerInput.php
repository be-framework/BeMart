<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\AdminCustomerUpdating;

/**
 * Input for doUpdateCustomerProfile — admin edits an existing customer
 * (management screen).
 *
 * Linear pattern (contact-form demo), mirroring the admin-create trio
 * ({@see AdminCreateCustomerInput}) with two differences that make it
 * the *edit* (not create) shape:
 *
 *   1. `customerId` is a first-class INPUT here — the admin chooses
 *      which customer to edit (the EC-CUBE admin_customer_edit route's
 *      `{id}` path segment). This is the deliberate inverse of the
 *      customer-self {@see UpdateCustomerInput}, which OMITS the id and
 *      derives it from the session to block mass-assignment of a
 *      foreign id. The admin path is safe to take the id as input ONLY
 *      because the downstream Being gates it behind the admin firewall
 *      (raises {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException}
 *      as its first statement).
 *   2. `password` is nullable: EC-CUBE pre-fills a default-password
 *      sentinel and only re-hashes when the admin actually changes it.
 *      `null` here means "leave the current hash untouched".
 *
 *   AdminUpdateCustomerInput
 *     → AdminCustomerUpdating (admin AUTHZ + load-current + conditional
 *                              uniqueness + conditional re-hash)
 *     → AdminCustomerUpdated  (Final — merge persisted-current with the
 *                              patch, persist)
 *
 * Be Framework G-17: the `#[Be]` chain destination is fixed at the
 * class level; the admin-edit chain is its own dedicated Being / Final
 * pair (different Input intent ⇒ different Being shape).
 *
 * @see AdminCreateCustomerInput  admin create (sibling)
 * @see UpdateCustomerInput       customer-self edit (session id)
 * @link https://schema.org/UpdateAction
 */
#[Be(AdminCustomerUpdating::class)]
final readonly class AdminUpdateCustomerInput
{
    /**
     * Every property comes from the HTTP edit form. Marked as input
     * sources for the boundary contract; format validation happens via
     * Be Semantic at metamorphosis time.
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $email
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
     * @psalm-taint-source input $password
     */
    public function __construct(
        public string $customerId,
        public string $email,
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
        public string|null $password = null,
    ) {
    }
}
