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
 * Three failure modes all raise LoginFailedException (no enumeration):
 *   1. no customer with that email
 *   2. password does not verify
 *   3. customerStatus is not STATUS_ACTIVE — a 仮会員 (1) has not
 *      proven the address yet, and a 退会 member (3) keeps its
 *      password hash after withdrawal, so only the status stops the
 *      old credentials from minting a session
 *
 * Existence of this object proves: email is registered AND the
 * customer is a 本会員 AND password matches stored hash. The public
 * surface exposes the customerId and the customer profile fields the
 * BEAR resource needs to populate the session and the response body.
 * The plaintext password is consumed inside the constructor
 * (#[SensitiveParameter]) and is intentionally NOT promoted to a
 * public property.
 */
final readonly class CustomerAuthenticated
{
    /** 本会員 — the only status that may authenticate (EC-CUBE dtb_customer.customer_status_id=2). */
    public const int STATUS_ACTIVE = 2;

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
        $customer = $customerQuery->byEmail($email);
        if ($customer === null) {
            throw new LoginFailedException();
        }

        if (! $passwordHasher->verify($password, $customer->passwordHash)) {
            throw new LoginFailedException();
        }

        if ($customer->customerStatus !== self::STATUS_ACTIVE) {
            throw new LoginFailedException();
        }

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;
        $this->customerStatus = $customer->customerStatus;
    }
}
