<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;

/**
 * Storage-layer coverage for {@see EmailUniquenessCheckerInterface} (Phase
 * 2b) — bundled with {@see CustomerCommandInterfaceTest} because the checker
 * is the read-guard companion of the customer write side, a trivial
 * existence probe against the same `dtb_customer` table.
 */
final class SqlEmailUniquenessCheckerTest extends AbstractSqlTestCase
{
    public function testEnsureUniquePassesForUnusedEmail(): void
    {
        $checker = $this->sql(EmailUniquenessCheckerInterface::class);

        // No row with this email — must return without raising.
        $checker->check('never-seen@example.com')->assertUnique();
        $this->addToAssertionCount(1);
    }

    public function testEnsureUniqueThrowsForTakenEmail(): void
    {
        $this->insertCustomer(['email' => 'taken@example.com']);

        $checker = $this->sql(EmailUniquenessCheckerInterface::class);

        $this->expectException(EmailAlreadyRegisteredException::class);
        $checker->check('taken@example.com')->assertUnique();
    }

    public function testEnsureUniqueRejectsProvisionalCustomerEmail(): void
    {
        // A not-yet-activated (status-1) customer still occupies the
        // address — a second registration must be rejected.
        // mtb_customer_status is empty in the dump — seed it so the
        // non-null customer_status_id FK holds.
        $this->seedCustomerStatus();
        $this->insertCustomer([
            'email' => 'provisional@example.com',
            'customer_status_id' => 1,
        ]);

        $checker = $this->sql(EmailUniquenessCheckerInterface::class);

        $this->expectException(EmailAlreadyRegisteredException::class);
        $checker->check('provisional@example.com')->assertUnique();
    }

    public function testEnsureUniqueIsCaseSensitive(): void
    {
        // dtb_customer uses utf8mb4_bin collation — binary comparison,
        // matching the Fake's array-key lookup.
        $this->insertCustomer(['email' => 'Case@example.com']);

        $checker = $this->sql(EmailUniquenessCheckerInterface::class);

        // Different case → not a collision.
        $checker->check('case@example.com')->assertUnique();
        $this->addToAssertionCount(1);
    }
}
