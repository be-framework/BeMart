<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer updated (admin-side) — Final, proof an existing customer's
 * profile was edited in place by an admin operation.
 *
 *   AdminUpdateCustomerInput
 *     → AdminCustomerUpdating (admin AUTHZ + guards)
 *     → AdminCustomerUpdated  (this stage — merge + persist)
 *
 * Keyed by the admin-supplied `customerId` (NOT a session id — that is
 * the customer-self {@see CustomerUpdated} path). The admin firewall
 * already proved at the upstream Being, so the id is trusted here.
 *
 * Merge semantics (same shape as CustomerUpdated, but the admin CAN
 * edit birth / sex / job, which the customer-self Final preserves):
 *   - email: written as-is (uniqueness already re-checked upstream when
 *            it differed from the current value)
 *   - name01 / name02 / kana01 / kana02 / companyName / phoneNumber /
 *     postalCode / pref / addr01 / addr02 / birth / sex / job: nullable;
 *     null leaves the existing value untouched
 *   - passwordHash: `null` sentinel from the Being = keep the current
 *     hash; a non-null value replaces it
 *   - initialPoint / customerStatus / secretKey: preserved from current
 *     (status/point editing and the withdrawal branch are out of this
 *     slice — residual)
 */
final readonly class AdminCustomerUpdated
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
        #[Input] string|null $birth,
        #[Input] int|null $sex,
        #[Input] int|null $job,
        #[Input] string|null $passwordHash,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CustomerCommandInterface $customerCommand,
    ) {
        $current = $customerQuery->item($customerId);
        if ($current === null) {
            throw new CustomerNotFoundException();
        }

        $merged = new CustomerEntity(
            customerId: $current->customerId,
            email: $email,
            passwordHash: $passwordHash ?? $current->passwordHash,
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
            birth: $birth ?? $current->birth,
            sex: $sex ?? $current->sex,
            job: $job ?? $current->job,
            initialPoint: $current->initialPoint,
            customerStatus: $current->customerStatus,
            secretKey: $current->secretKey,
        );

        $customerCommand->update($merged);

        $this->customerId = $merged->customerId;
        $this->email = $merged->email;
        $this->name01 = $merged->name01;
        $this->name02 = $merged->name02;
        $this->initialPoint = $merged->initialPoint;
        $this->customerStatus = $merged->customerStatus;
    }
}
