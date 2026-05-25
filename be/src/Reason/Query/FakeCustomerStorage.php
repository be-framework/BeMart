<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use RuntimeException;

use function array_values;
use function array_slice;
use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;
use function str_contains;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Customer store shared by FakeEmailUniquenessChecker +
 * FakeCustomerCommand + FakeCustomerQuery. Bound as Singleton so a
 * Command's writes are visible to all readers within the same request.
 *
 * The seed fixture (`var/fake/customers.json`) holds a handful of
 * pre-registered customers used in tests as the "email already taken"
 * case (Pilot 4), the "happy-path login" case (Pilot 6), and the
 * "activate provisional customer" case (Pilot 7).
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
     * Fetch a Customer by email, or null if none. Used by the login
     * flow (Pilot 6 via FakeCustomerQuery) and by tests that need to
     * verify what was persisted (e.g. password hash round-trip)
     * without reaching for reflection.
     */
    public function getByEmail(string $email): CustomerEntity|null
    {
        return $this->load()[$email] ?? null;
    }

    /**
     * Look up a Customer by their email-verification secretKey.
     * Returns null when no customer carries that key (already activated,
     * wrong key, expired). Pilot 7 (doActivateCustomer).
     */
    public function getBySecretKey(string $secretKey): CustomerEntity|null
    {
        foreach ($this->load() as $customer) {
            if ($customer->secretKey === $secretKey) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Look up a Customer by their opaque id — Pilot 8
     * (doUpdateCustomer). Returns null when no such customer exists.
     */
    public function getById(string $customerId): CustomerEntity|null
    {
        foreach ($this->load() as $customer) {
            if ($customer->customerId === $customerId) {
                return $customer;
            }
        }

        return null;
    }

    public function put(CustomerEntity $customer): void
    {
        $this->load();
        $this->byEmail[$customer->email] = $customer;
    }

    /**
     * Pilot Wave 5 (goCustomerList) — admin-side substring filter scan.
     * Walks the whole in-memory corpus; both keywords are optional and
     * combined with AND when both are present. Case-sensitive
     * `str_contains` is sufficient for v1 (the EC-CUBE admin grid does
     * substring matching too, and the seed corpus uses Japanese strings
     * where case folding is a no-op anyway). Returns at most `$limit`
     * rows so the admin grid stays bounded even for an empty filter.
     *
     * @return list<CustomerEntity>
     */
    public function search(?string $nameKeyword, ?string $emailKeyword, int $limit = 50): array
    {
        $rows = array_values($this->load());
        $matches = [];
        foreach ($rows as $customer) {
            if ($nameKeyword !== null && $nameKeyword !== '' && ! $this->nameMatches($customer, $nameKeyword)) {
                continue;
            }

            if ($emailKeyword !== null && $emailKeyword !== '' && ! str_contains($customer->email, $emailKeyword)) {
                continue;
            }

            $matches[] = $customer;
        }

        return array_slice($matches, 0, $limit);
    }

    private function nameMatches(CustomerEntity $customer, string $keyword): bool
    {
        if (str_contains($customer->name01, $keyword)) {
            return true;
        }

        if (str_contains($customer->name02, $keyword)) {
            return true;
        }

        if ($customer->companyName !== null && str_contains($customer->companyName, $keyword)) {
            return true;
        }

        return false;
    }

    /**
     * Replace a customer record, handling the case where the email
     * field itself changed (the storage indexes by email, so we
     * remove the old key before inserting the new one). Pilot 8
     * (doUpdateCustomer).
     */
    public function replace(CustomerEntity $customer): void
    {
        $rows = $this->load();
        foreach ($rows as $email => $existing) {
            if ($existing->customerId === $customer->customerId) {
                unset($rows[$email]);

                break;
            }
        }

        $rows[$customer->email] = $customer;
        $this->byEmail = $rows;
    }

    /**
     * Swap the customer's passwordHash in place — Pilot 15
     * (doResetPassword). Smaller surface than `replace()` because the
     * reset path only touches the credential field; addr/profile/etc.
     * are preserved verbatim. Silently does nothing when the
     * customerId is not in the store (the Final's caller has already
     * proven existence via the reset-token lookup, so the no-op branch
     * is unreachable in practice — it exists only to keep this method
     * total).
     */
    public function replacePassword(string $customerId, string $passwordHash): void
    {
        $rows = $this->load();
        foreach ($rows as $email => $customer) {
            if ($customer->customerId !== $customerId) {
                continue;
            }

            $this->byEmail[$email] = new CustomerEntity(
                customerId: $customer->customerId,
                email: $customer->email,
                passwordHash: $passwordHash,
                name01: $customer->name01,
                name02: $customer->name02,
                kana01: $customer->kana01,
                kana02: $customer->kana02,
                companyName: $customer->companyName,
                phoneNumber: $customer->phoneNumber,
                postalCode: $customer->postalCode,
                pref: $customer->pref,
                addr01: $customer->addr01,
                addr02: $customer->addr02,
                birth: $customer->birth,
                sex: $customer->sex,
                job: $customer->job,
                initialPoint: $customer->initialPoint,
                customerStatus: $customer->customerStatus,
                secretKey: $customer->secretKey,
            );

            return;
        }
    }

    /**
     * Mark the customer as active (status=2) and clear the secretKey.
     * Idempotent: a customer that is already active is left untouched.
     * Pilot 7 (doActivateCustomer).
     */
    public function activate(string $customerId): void
    {
        $rows = $this->load();
        foreach ($rows as $email => $customer) {
            if ($customer->customerId !== $customerId) {
                continue;
            }

            if ($customer->customerStatus === 2 && $customer->secretKey === null) {
                return;
            }

            $this->byEmail[$email] = new CustomerEntity(
                customerId: $customer->customerId,
                email: $customer->email,
                passwordHash: $customer->passwordHash,
                name01: $customer->name01,
                name02: $customer->name02,
                kana01: $customer->kana01,
                kana02: $customer->kana02,
                companyName: $customer->companyName,
                phoneNumber: $customer->phoneNumber,
                postalCode: $customer->postalCode,
                pref: $customer->pref,
                addr01: $customer->addr01,
                addr02: $customer->addr02,
                birth: $customer->birth,
                sex: $customer->sex,
                job: $customer->job,
                initialPoint: $customer->initialPoint,
                customerStatus: 2,
                secretKey: null,
            );

            return;
        }
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

        /** @var array<string, array{customerId: string, email: string, passwordHash: string, name01: string, name02: string, kana01: ?string, kana02: ?string, companyName: ?string, phoneNumber: ?string, postalCode: ?string, pref: ?int, addr01: ?string, addr02: ?string, birth: ?string, sex: ?int, job: ?int, initialPoint: int, customerStatus: int, secretKey?: ?string}|string> $rows */
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
                secretKey: $row['secretKey'] ?? null,
            );
        }

        return $this->byEmail = $byEmail;
    }
}
