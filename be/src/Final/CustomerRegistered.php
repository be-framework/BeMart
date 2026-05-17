<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer registered — Final, proof the new customer was persisted.
 *
 * Cascade:
 *   RegisterCustomerInput
 *     → CustomerRegistering (Multi-Reason Being)
 *     → CustomerRegistered  (this stage — persistence)
 *
 * Existence of this object proves CustomerCommand::register() ran
 * without raising. Public surface mirrors the doRegisterCustomer
 * response shape: identity + status of the new account. The plaintext
 * password is intentionally NOT exposed here — only its server-side
 * hash is held on the upstream Being, which the persistence layer
 * consumes through `$this->passwordHash` and immediately discards.
 */
final readonly class CustomerRegistered
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
