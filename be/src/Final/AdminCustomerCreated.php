<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer created (admin-side) — Final, proof the new customer was
 * persisted by an admin operation.
 *
 * Cascade:
 *   AdminCreateCustomerInput
 *     → AdminCustomerCreating (Multi-Reason Being + admin AUTHZ)
 *     → AdminCustomerCreated   (this stage — persistence)
 *
 * Existence of this object proves CustomerCommand::register() ran
 * without raising. Public surface mirrors the doCreateCustomer
 * response shape: identity + status of the new account. The plaintext
 * password is intentionally NOT exposed here — only its server-side
 * hash is held on the upstream Being, which the persistence layer
 * consumes through `$this->passwordHash` and immediately discards.
 *
 * Be Framework G-17 (Pilot 10): this Final is a sibling of Pilot 4's
 * {@see CustomerRegistered}, not a subclass. The two are shape-equal
 * but namespaced separately so the `#[Be]` chain stays crisp — see
 * the {@see \MyVendor\BeMart\Be\Input\AdminCreateCustomerInput} class
 * docblock for the G-17 rationale.
 */
final readonly class AdminCustomerCreated
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;
    public int $initialPoint;
    public int $customerStatus;

    public function __construct(
        #[Input] string $customerId,
        #[Input] string $email,
        #[Input] string $passwordHash,
        #[Input] string $name01,
        #[Input] string $name02,
        #[Input] string|null $kana01,
        #[Input] string|null $kana02,
        #[Input] string|null $companyName,
        #[Input] string|null $phoneNumber,
        #[Input] string|null $postalCode,
        #[Input] int|null $pref,
        #[Input] string|null $addr01,
        #[Input] string|null $addr02,
        #[Input] string|null $birth,
        #[Input] int|null $sex,
        #[Input] int|null $job,
        #[Input] int $initialPoint,
        #[Input] int $customerStatus,
        #[Inject] CustomerCommandInterface $command,
    ) {
        $command->register(new CustomerEntity(
            customerId: $customerId,
            email: $email,
            passwordHash: $passwordHash,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            birth: $birth,
            sex: $sex,
            job: $job,
            initialPoint: $initialPoint,
            customerStatus: $customerStatus,
        ));

        $this->customerId = $customerId;
        $this->email = $email;
        $this->name01 = $name01;
        $this->name02 = $name02;
        $this->initialPoint = $initialPoint;
        $this->customerStatus = $customerStatus;
    }
}
