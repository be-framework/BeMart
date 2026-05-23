<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;

final class SqlCustomerQueryTest extends AbstractSqlTestCase
{
    public function testFindByEmailReturnsCustomer(): void
    {
        $id = $this->insertCustomer([
            'email' => 'alice@example.com',
            'name01' => 'Alice',
            'name02' => 'Anderson',
        ]);

        $query = $this->sql(CustomerQueryInterface::class);
        $customer = $query->findByEmail('alice@example.com');

        $this->assertInstanceOf(CustomerEntity::class, $customer);
        $this->assertSame((string) $id, $customer->customerId);
        $this->assertSame('alice@example.com', $customer->email);
        $this->assertSame('Alice', $customer->name01);
        $this->assertSame('Anderson', $customer->name02);
    }

    public function testFindByEmailReturnsNullOnMiss(): void
    {
        $this->insertCustomer(['email' => 'bob@example.com']);

        $query = $this->sql(CustomerQueryInterface::class);
        $this->assertNull($query->findByEmail('nobody@example.com'));
    }

    public function testFindByIdReturnsCustomer(): void
    {
        $id = $this->insertCustomer(['email' => 'carol@example.com']);

        $query = $this->sql(CustomerQueryInterface::class);
        $customer = $query->findById((string) $id);

        $this->assertNotNull($customer);
        $this->assertSame('carol@example.com', $customer->email);
    }

    public function testFindByIdReturnsNullOnMiss(): void
    {
        $this->insertCustomer();

        $query = $this->sql(CustomerQueryInterface::class);
        $this->assertNull($query->findById('99999999'));
        // Non-numeric id is rejected early without hitting the DB.
        $this->assertNull($query->findById('not-an-int'));
    }

    public function testFindBySecretKeyReturnsCustomer(): void
    {
        $secret = 'fixed-secret-' . bin2hex(random_bytes(8));
        $this->insertCustomer([
            'email' => 'dave@example.com',
            'secret_key' => $secret,
        ]);

        $query = $this->sql(CustomerQueryInterface::class);
        $customer = $query->findBySecretKey($secret);

        $this->assertNotNull($customer);
        $this->assertSame('dave@example.com', $customer->email);
        // The hydrator passes a non-empty secret_key through unchanged.
        $this->assertSame($secret, $customer->secretKey);
    }

    public function testFindBySecretKeyReturnsNullOnMiss(): void
    {
        $this->insertCustomer();

        $query = $this->sql(CustomerQueryInterface::class);
        $this->assertNull($query->findBySecretKey('does-not-exist'));
    }

    public function testSearchByNameKeywordOnly(): void
    {
        $this->insertCustomer(['name01' => 'Alice', 'email' => 'a@example.com']);
        $this->insertCustomer(['name02' => 'Smith', 'email' => 'b@example.com']);
        $this->insertCustomer(['name01' => 'Bob', 'name02' => 'Brown', 'email' => 'c@example.com']);
        $this->insertCustomer(['company_name' => 'Smithsonian Inc', 'email' => 'd@example.com']);

        $query = $this->sql(CustomerQueryInterface::class);
        $results = $query->search('Smith', null);

        // Hits "Smith" (name02) and "Smithsonian Inc" (company_name).
        $emails = array_map(static fn (CustomerEntity $c) => $c->email, $results);
        sort($emails);
        $this->assertSame(['b@example.com', 'd@example.com'], $emails);
    }

    public function testSearchByEmailKeywordOnly(): void
    {
        $this->insertCustomer(['email' => 'admin@shop.example.com']);
        $this->insertCustomer(['email' => 'user@other.example.com']);
        $this->insertCustomer(['email' => 'admin2@shop.example.com']);

        $query = $this->sql(CustomerQueryInterface::class);
        $results = $query->search(null, 'shop.example');

        $this->assertCount(2, $results);
        foreach ($results as $r) {
            $this->assertStringContainsString('shop.example', $r->email);
        }
    }

    public function testSearchWithBothKeywordsCombinesWithAndAndEnforcesLimit(): void
    {
        // 6 rows where name matches "Yamada", but only 3 where email
        // also matches "@target.example.com". With limit=2 we expect 2.
        for ($i = 0; $i < 3; $i++) {
            $this->insertCustomer([
                'name01' => 'Yamada',
                'email' => sprintf('hit-%d@target.example.com', $i),
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            $this->insertCustomer([
                'name01' => 'Yamada',
                'email' => sprintf('miss-%d@other.example.com', $i),
            ]);
        }

        // Non-matching name should also be excluded even when email matches.
        $this->insertCustomer([
            'name01' => 'Tanaka',
            'email' => 'tanaka@target.example.com',
        ]);

        $query = $this->sql(CustomerQueryInterface::class);
        $results = $query->search('Yamada', 'target.example.com', 2);

        $this->assertCount(2, $results, 'limit must cap the result set');
        foreach ($results as $r) {
            $this->assertSame('Yamada', $r->name01);
            $this->assertStringContainsString('@target.example.com', $r->email);
        }
    }
}
