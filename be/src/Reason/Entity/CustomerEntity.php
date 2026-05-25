<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Customer entity — projection of EC-CUBE 4.3 dtb_customer for the
 * registration pipeline. Holds the persisted shape so the Fake store
 * can answer EmailUniquenessChecker queries without re-reading the
 * raw json fixture.
 */
final readonly class CustomerEntity
{
    public function __construct(
        public string $customerId,
        public string $email,
        public string $passwordHash,
        public string $name01,
        public string $name02,
        public string|null $kana01,
        public string|null $kana02,
        public string|null $companyName,
        public string|null $phoneNumber,
        public string|null $postalCode,
        public int|null $pref,
        public string|null $addr01,
        public string|null $addr02,
        public string|null $birth,
        public int|null $sex,
        public int|null $job,
        public int $initialPoint,
        public int $customerStatus,
    ) {
    }
}
