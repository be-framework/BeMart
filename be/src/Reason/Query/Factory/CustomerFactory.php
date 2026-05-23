<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;

final class CustomerFactory
{
    public function factory(
        int|string $id,
        string $email,
        string $password,
        string $name01,
        string $name02,
        string|null $kana01,
        string|null $kana02,
        string|null $companyName,
        string|null $phoneNumber,
        string|null $postalCode,
        int|string|null $prefId,
        string|null $addr01,
        string|null $addr02,
        string|null $birth,
        int|string|null $sexId,
        int|string|null $jobId,
        int|string $customerStatusId,
        string|null $secretKey,
    ): CustomerEntity {
        return new CustomerEntity(
            customerId: (string) $id,
            email: $email,
            passwordHash: $password,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $prefId === null ? null : (int) $prefId,
            addr01: $addr01,
            addr02: $addr02,
            birth: $birth,
            sex: $sexId === null ? null : (int) $sexId,
            job: $jobId === null ? null : (int) $jobId,
            initialPoint: 0,
            customerStatus: (int) $customerStatusId,
            secretKey: $secretKey === '' ? null : $secretKey,
        );
    }
}
