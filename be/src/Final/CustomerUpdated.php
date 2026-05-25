<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer updated — Final, proof the logged-in customer's profile
 * was edited in place.
 *
 *   UpdateCustomerInput → CustomerUpdated  (this stage)
 *
 * AUTHN: the customerId comes from SessionInterface. A null session
 * raises UnauthenticatedException — the BEAR layer maps this to 401.
 *
 * Merge semantics (Pilot 8 scope):
 *   - email: required by Input, written as-is (uniqueness re-checked
 *            only if different from the current value)
 *   - name01 / name02 / kana01 / kana02 / companyName / phoneNumber
 *     / postalCode / pref / addr01 / addr02: nullable; null leaves
 *     the existing value untouched
 *   - passwordHash, secretKey, customerStatus, initialPoint, birth,
 *     sex, job: preserved from current (not part of Pilot 8 scope)
 */
final readonly class CustomerUpdated
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;

    public function __construct(
        #[Input] string $email,
        #[Input] string|null $name01,
        #[Input] string|null $name02,
        #[Input] string|null $kana01,
        #[Input] string|null $kana02,
        #[Input] string|null $companyName,
        #[Input] string|null $phoneNumber,
        #[Input] string|null $postalCode,
        #[Input] int|null $pref,
        #[Input] string|null $addr01,
        #[Input] string|null $addr02,
        #[Inject] SessionInterface $session,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CustomerCommandInterface $customerCommand,
        #[Inject] EmailUniquenessCheckerInterface $uniquenessChecker,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $current = $customerQuery->findById($sessionCustomerId);
        if ($current === null) {
            // Session points to a non-existent customer (deleted /
            // expired). Treat same as not-logged-in to avoid leaking
            // existence signal across the AAA boundary.
            throw new UnauthenticatedException();
        }

        if ($email !== $current->email) {
            $uniquenessChecker->ensureUnique($email);
        }

        $merged = new CustomerEntity(
            customerId: $current->customerId,
            email: $email,
            passwordHash: $current->passwordHash,
            name01: $name01 ?? $current->name01,
            name02: $name02 ?? $current->name02,
            kana01: $kana01 ?? $current->kana01,
            kana02: $kana02 ?? $current->kana02,
            companyName: $companyName ?? $current->companyName,
            phoneNumber: $phoneNumber ?? $current->phoneNumber,
            postalCode: $postalCode ?? $current->postalCode,
            pref: $pref ?? $current->pref,
            addr01: $addr01 ?? $current->addr01,
            addr02: $addr02 ?? $current->addr02,
            birth: $current->birth,
            sex: $current->sex,
            job: $current->job,
            initialPoint: $current->initialPoint,
            customerStatus: $current->customerStatus,
            secretKey: $current->secretKey,
        );

        $customerCommand->update($merged);

        $this->customerId = $merged->customerId;
        $this->email = $merged->email;
        $this->name01 = $merged->name01;
        $this->name02 = $merged->name02;
    }
}
