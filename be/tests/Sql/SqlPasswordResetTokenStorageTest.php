<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;

/**
 * Storage-layer coverage for {@see PasswordResetTokenStorageInterface}
 * (Phase 2b).
 *
 * EC-CUBE 4.3 has no separate password-reset-token table — the token
 * lives as the `reset_key` / `reset_expire` columns on `dtb_customer`
 * (Option A, mirror EC-CUBE, no schema change). So every assertion here
 * pivots on those two columns of a customer row inserted via
 * {@see SqlFixturesTrait::insertCustomer}.
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * sibling ({@see \MyVendor\BeMart\Tests\Resource\Sql\ResetResourceSqlTest});
 * this file pins the per-method SQL paths.
 *
 * Surprises this suite locks in:
 *  - getByResetKey returns the row REGARDLESS of expiry — the consumer
 *    (PasswordResetCompleted) does its own `expiresAt < now` check, and
 *    FakePasswordResetTokenStorage returns unfiltered too. The storage
 *    is a dumb column reader.
 *  - put is a column UPDATE, so issuing a new token for the same
 *    customer naturally replaces the prior one (latest-wins).
 *  - delete clears both columns, making a re-lookup of the consumed key
 *    a clean miss (single-use).
 */
final class SqlPasswordResetTokenStorageTest extends AbstractSqlTestCase
{
    public function testPutWritesResetColumnsOntoCustomerRow(): void
    {
        $customerId = $this->insertCustomer();

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $storage->put(new PasswordResetTokenEntity(
            customerId: (string) $customerId,
            resetKey: 'reset-key-issue-aaaa1111bbbb2222',
            expiresAt: new DateTimeImmutable('2026-05-20 12:00:00'),
        ));

        $stmt = $this->pdo->prepare(
            'SELECT reset_key, reset_expire FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => $customerId]);
        $row = $stmt->fetch();

        $this->assertIsArray($row);
        $this->assertSame('reset-key-issue-aaaa1111bbbb2222', $row['reset_key']);
        $this->assertSame('2026-05-20 12:00:00', $row['reset_expire']);
    }

    public function testGetByResetKeyReturnsTokenForIssuedKey(): void
    {
        $customerId = $this->insertCustomer([
            'reset_key' => 'reset-key-lookup-cccc3333dddd4444',
            'reset_expire' => '2026-05-20 18:30:00',
        ]);

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $token = $storage->getByResetKey('reset-key-lookup-cccc3333dddd4444');

        $this->assertInstanceOf(PasswordResetTokenEntity::class, $token);
        $this->assertSame((string) $customerId, $token->customerId);
        $this->assertSame('reset-key-lookup-cccc3333dddd4444', $token->resetKey);
        $this->assertSame(
            '2026-05-20 18:30:00',
            $token->expiresAt->format('Y-m-d H:i:s'),
        );
    }

    public function testGetByResetKeyMissesForUnknownKey(): void
    {
        // A customer with no active token — reset_key NULL.
        $this->insertCustomer();

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);

        $this->assertNull($storage->getByResetKey('no-such-key-eeee5555ffff6666'));
    }

    public function testGetByResetKeyReturnsExpiredTokenUnfiltered(): void
    {
        // The storage does NOT filter on expiry — it returns the row and
        // lets the consumer (PasswordResetCompleted) reject it. This
        // mirrors FakePasswordResetTokenStorage::getByResetKey exactly.
        $this->insertCustomer([
            'reset_key' => 'reset-key-expired-7777aaaa8888bbbb',
            'reset_expire' => '2020-01-01 00:00:00',
        ]);

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $token = $storage->getByResetKey('reset-key-expired-7777aaaa8888bbbb');

        $this->assertInstanceOf(PasswordResetTokenEntity::class, $token);
        $this->assertSame(
            '2020-01-01 00:00:00',
            $token->expiresAt->format('Y-m-d H:i:s'),
        );
        // Expiry is in the past — but the storage still returned it.
        $this->assertLessThan(new DateTimeImmutable('now'), $token->expiresAt);
    }

    public function testPutReplacesPriorTokenLatestWins(): void
    {
        $customerId = $this->insertCustomer();
        $storage = $this->sql(PasswordResetTokenStorageInterface::class);

        $storage->put(new PasswordResetTokenEntity(
            customerId: (string) $customerId,
            resetKey: 'reset-key-first-1111aaaa2222bbbb',
            expiresAt: new DateTimeImmutable('2026-05-20 09:00:00'),
        ));
        $storage->put(new PasswordResetTokenEntity(
            customerId: (string) $customerId,
            resetKey: 'reset-key-second-3333cccc4444dddd',
            expiresAt: new DateTimeImmutable('2026-05-20 15:00:00'),
        ));

        // Latest-wins: the first key no longer resolves.
        $this->assertNull(
            $storage->getByResetKey('reset-key-first-1111aaaa2222bbbb'),
        );

        // The second key is the live token.
        $live = $storage->getByResetKey('reset-key-second-3333cccc4444dddd');
        $this->assertInstanceOf(PasswordResetTokenEntity::class, $live);
        $this->assertSame((string) $customerId, $live->customerId);
        $this->assertSame(
            '2026-05-20 15:00:00',
            $live->expiresAt->format('Y-m-d H:i:s'),
        );
    }

    public function testDeleteConsumesTokenAndClearsColumns(): void
    {
        $customerId = $this->insertCustomer([
            'reset_key' => 'reset-key-consume-5555eeee6666ffff',
            'reset_expire' => '2026-05-20 20:00:00',
        ]);

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $storage->delete('reset-key-consume-5555eeee6666ffff');

        // Both columns nulled — a re-lookup of the consumed key misses.
        $this->assertNull(
            $storage->getByResetKey('reset-key-consume-5555eeee6666ffff'),
        );

        $stmt = $this->pdo->prepare(
            'SELECT reset_key, reset_expire FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => $customerId]);
        $row = $stmt->fetch();
        $this->assertIsArray($row);
        $this->assertNull($row['reset_key']);
        $this->assertNull($row['reset_expire']);
    }

    public function testDeleteUnknownKeyIsSilentNoOp(): void
    {
        // Idempotent under retries: deleting a never-issued key matches
        // no row and raises nothing — same as the Fake.
        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $storage->delete('never-issued-key-9999aaaa0000bbbb');
        $this->addToAssertionCount(1);
    }

    public function testPutNonNumericCustomerIdIsSilentNoOp(): void
    {
        // dtb_customer.id is an int PK — a non-numeric handle cannot
        // address a row. put() returns without raising.
        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $storage->put(new PasswordResetTokenEntity(
            customerId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            resetKey: 'reset-key-orphan-cccc7777dddd8888',
            expiresAt: new DateTimeImmutable('2026-05-20 12:00:00'),
        ));

        // Nothing was written — the orphan key resolves to nothing.
        $this->assertNull(
            $storage->getByResetKey('reset-key-orphan-cccc7777dddd8888'),
        );
    }

    public function testGetByResetKeyDoesNotMatchNullResetKeyColumn(): void
    {
        // A customer with reset_key NULL must never match — even an
        // empty-string probe. NULL never equals a value in SQL.
        $this->insertCustomer();

        $storage = $this->sql(PasswordResetTokenStorageInterface::class);
        $this->assertNull($storage->getByResetKey(''));
    }
}
