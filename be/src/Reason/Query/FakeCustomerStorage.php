<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Customer store shared by FakeEmailUniquenessChecker +
 * FakeCustomerCommand. Bound as Singleton so the Command's writes
 * are visible to the uniqueness check within the same request.
 *
 * The seed fixture (`var/fake/customers.json`) holds a handful of
 * pre-registered customers used in tests as the "email already taken"
 * case.
 */
final class FakeCustomerStorage
{
    /** @var array<string, CustomerEntity>|null indexed by email */
    private array|null $byEmail = null;

    public function existsByEmail(string $email): bool
    {
        return isset($this->load()[$email]);
    }

    /**
     * Fetch a Customer by email, or null if none. Used by the future
     * login flow and by tests that need to verify what was persisted
     * (e.g. password hash round-trip) without reaching for reflection.
     */
    public function getByEmail(string $email): CustomerEntity|null
    {
        return $this->load()[$email] ?? null;
    }

    public function put(CustomerEntity $customer): void
    {
        $this->load();
        $this->byEmail[$customer->email] = $customer;
    }

    /** @return array<string, CustomerEntity> */
    private function load(): array
    {
        if ($this->byEmail !== null) {
            return $this->byEmail;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/customers.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{customerId: string, email: string, passwordHash: string, name01: string, name02: string, kana01: ?string, kana02: ?string, companyName: ?string, phoneNumber: ?string, postalCode: ?string, pref: ?int, addr01: ?string, addr02: ?string, birth: ?string, sex: ?int, job: ?int, initialPoint: int, customerStatus: int}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $byEmail = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $byEmail[$row['email']] = new CustomerEntity(
                customerId: $row['customerId'],
                email: $row['email'],
                passwordHash: $row['passwordHash'],
                name01: $row['name01'],
                name02: $row['name02'],
                kana01: $row['kana01'] ?? null,
                kana02: $row['kana02'] ?? null,
                companyName: $row['companyName'] ?? null,
                phoneNumber: $row['phoneNumber'] ?? null,
                postalCode: $row['postalCode'] ?? null,
                pref: $row['pref'] ?? null,
                addr01: $row['addr01'] ?? null,
                addr02: $row['addr02'] ?? null,
                birth: $row['birth'] ?? null,
                sex: $row['sex'] ?? null,
                job: $row['job'] ?? null,
                initialPoint: $row['initialPoint'],
                customerStatus: $row['customerStatus'],
            );
        }

        return $this->byEmail = $byEmail;
    }
}
