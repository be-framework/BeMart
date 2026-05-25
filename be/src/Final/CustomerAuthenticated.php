<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\LoginFailedException;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * Customer authenticated — Final, proof the credentials check passed.
 *
 *   LoginInput → CustomerAuthenticated  (this stage — verification)
 *
 * Two failure modes both raise LoginFailedException (no enumeration):
 *   1. no customer with that email
 *   2. password does not verify
 *
 * Existence of this object proves: email is registered AND password
 * matches stored hash. The public surface exposes the customerId and
 * the customer profile fields the BEAR resource needs to populate the
 * session and the response body. The plaintext password is consumed
 * inside the constructor (#[SensitiveParameter]) and is intentionally
 * NOT promoted to a public property.
 */
final readonly class CustomerAuthenticated
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;
    public int $customerStatus;

    public function __construct(
        #[Input] string $email,
        #[Input] #[SensitiveParameter] string $password,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        $customer = $customerQuery->findByEmail($email);
        if ($customer === null) {
            throw new LoginFailedException();
        }

        if (! $passwordHasher->verify($password, $customer->passwordHash)) {
            throw new LoginFailedException();
        }

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;
        $this->customerStatus = $customer->customerStatus;
    }
}
