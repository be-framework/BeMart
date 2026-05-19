<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Admin store — Wave 4 foundation. Mirrors the customer-side
 * {@see FakeCustomerStorage} shape but indexes by `loginId` because
 * EC-CUBE admins authenticate with a username, not an email.
 *
 * Bound as Singleton in AppModule so a future AdminCommand's writes
 * (e.g. doUpdateMember in a later Wave) become visible to FakeAdminQuery
 * within the same request — same convention as customer-side CQRS.
 *
 * The seed fixture (`var/fake/admins.json`) carries the test admin used
 * by doAdminLogin tests plus a couple of `authority=1` rows for future
 * AUTHZ tests (Wave 5).
 */
final class FakeAdminStorage
{
    /** @var array<string, AdminEntity>|null indexed by loginId */
    private array|null $byLoginId = null;

    /**
     * Fetch an Admin by loginId, or null if none. Used by the admin
     * login flow (Wave 4 via FakeAdminQuery).
     */
    public function getByLoginId(string $loginId): AdminEntity|null
    {
        return $this->load()[$loginId] ?? null;
    }

    /**
     * Look up an Admin by their opaque adminId. Used by Wave 5 AUTHZ
     * flows where the session's adminId is mapped back to a full
     * Admin record. Returns null when no such admin exists.
     */
    public function getById(string $adminId): AdminEntity|null
    {
        foreach ($this->load() as $admin) {
            if ($admin->adminId === $adminId) {
                return $admin;
            }
        }

        return null;
    }

    /** @return array<string, AdminEntity> */
    private function load(): array
    {
        if ($this->byLoginId !== null) {
            return $this->byLoginId;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/admins.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{adminId: string, loginId: string, passwordHash: string, name: string, mailAddress: string, authority: int}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $byLoginId = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $byLoginId[$row['loginId']] = new AdminEntity(
                adminId: $row['adminId'],
                loginId: $row['loginId'],
                passwordHash: $row['passwordHash'],
                name: $row['name'],
                mailAddress: $row['mailAddress'],
                authority: $row['authority'],
            );
        }

        return $this->byLoginId = $byLoginId;
    }
}
