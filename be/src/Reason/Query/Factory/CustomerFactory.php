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
            (string) $id,
            $email,
            $password,
            $name01,
            $name02,
            $kana01,
            $kana02,
            $companyName,
            $phoneNumber,
            $postalCode,
            $prefId === null ? null : (int) $prefId,
            $addr01,
            $addr02,
            $birth,
            $sexId === null ? null : (int) $sexId,
            $jobId === null ? null : (int) $jobId,
            0,
            (int) $customerStatusId,
            $secretKey === '' ? null : $secretKey,
        );
    }
}
