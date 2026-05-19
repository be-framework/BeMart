<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerFetched;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;

/**
 * End-to-end goCustomer smoke against three SQL backends.
 *
 * Wires three real PDO-backed query/storage classes
 * ({@see SqlCustomerQuery}, {@see SqlOrderQuery}, {@see SqlFavoriteStorage})
 * directly into the {@see AdminCustomerFetched} Final's constructor —
 * NO injector, NO Becoming chain. The Final is a `readonly final class`
 * with all dependencies on its constructor, so direct instantiation is
 * the cleanest path for an integration smoke that wants to assert the
 * raw projection shape.
 *
 * The AdminSession is a {@see FakeAdminSession} — the production cookie
 * adapter is out of scope for Phase 2a (deferred per
 * AdminSessionInterface docblock), and the Fake is a one-line shim that
 * carries a fixed adminId. Substituting it in-process is the test-fake
 * pattern the brief calls out.
 *
 * Production DI (`AppModule`) is intentionally NOT exercised — it remains
 * bound to Fake* implementations. Phase 2b will swap the bindings once
 * every read-side query has a Sql counterpart.
 */
final class AdminCustomerFetchedSqlIntegrationTest extends AbstractSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    public function testHappyPathProjectsAllThreeSqlBackends(): void
    {
        // Customer.
        $customerId = $this->insertCustomer([
            'email' => 'admin-fetched@example.com',
            'name01' => 'Yamada',
            'name02' => 'Hanako',
            'kana01' => 'ヤマダ',
            'kana02' => 'ハナコ',
            'phone_number' => '0312345678',
            'postal_code' => '1500001',
            'pref_id' => null,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'birth' => '1985-06-15 00:00:00',
            'sex_id' => null,
            'job_id' => null,
        ]);

        // 3 finalized orders — totals 1000 + 2000 + 3000 = 6000.
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'INT-ORD-1',
            'total' => 1000,
            'payment_total' => 1000,
            'order_date' => '2026-05-01 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'INT-ORD-2',
            'total' => 2000,
            'payment_total' => 2000,
            'order_date' => '2026-05-05 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'INT-ORD-3',
            'total' => 3000,
            'payment_total' => 3000,
            'order_date' => '2026-05-10 10:00:00',
        ]);

        // 2 favorites (each requires a product + default class).
        $productA = $this->insertProduct([
            'name' => 'Cherry',
            'product_code' => 'INT-FAV-1',
            'price02' => 500,
        ]);
        $productB = $this->insertProduct([
            'name' => 'Durian',
            'product_code' => 'INT-FAV-2',
            'price02' => 800,
        ]);
        $this->insertFavorite($customerId, $productA);
        $this->insertFavorite($customerId, $productB);

        $final = new AdminCustomerFetched(
            email: 'admin-fetched@example.com',
            adminSession: new FakeAdminSession(self::TEST_ADMIN_ID),
            customerQuery: new SqlCustomerQuery($this->pdo),
            orderQuery: new SqlOrderQuery($this->pdo),
            favorites: new SqlFavoriteStorage($this->pdo),
        );

        $this->assertSame((string) $customerId, $final->customerId);
        $this->assertSame('admin-fetched@example.com', $final->email);
        $this->assertSame('Yamada', $final->name01);
        $this->assertSame('Hanako', $final->name02);
        $this->assertSame('ヤマダ', $final->kana01);
        $this->assertSame('ハナコ', $final->kana02);

        // Order aggregation.
        $this->assertSame(3, $final->orderCount);
        $this->assertSame(6000, $final->totalSpent);
        $this->assertCount(3, $final->orders);
        // Projection shape: each row has exactly the 5 declared keys.
        $expectedOrderKeys = ['orderNo', 'total', 'paymentTotal', 'orderDate', 'orderStatus'];
        foreach ($final->orders as $orderRow) {
            $this->assertSame($expectedOrderKeys, array_keys($orderRow));
        }
        // Order DESC: newest (INT-ORD-3) comes first.
        $this->assertSame('INT-ORD-3', $final->orders[0]['orderNo']);

        // Favorites.
        $this->assertSame(2, $final->favoriteCount);
        $this->assertCount(2, $final->favorites);
        $expectedFavKeys = ['productCode', 'productName', 'unitPrice'];
        foreach ($final->favorites as $favRow) {
            $this->assertSame($expectedFavKeys, array_keys($favRow));
        }
        $favCodes = array_map(static fn (array $r) => $r['productCode'], $final->favorites);
        sort($favCodes);
        $this->assertSame(['INT-FAV-1', 'INT-FAV-2'], $favCodes);
    }

    public function testUnknownEmailThrowsCustomerNotFound(): void
    {
        $this->insertCustomer(['email' => 'present@example.com']);

        $this->expectException(CustomerNotFoundException::class);
        new AdminCustomerFetched(
            email: 'absent@example.com',
            adminSession: new FakeAdminSession(self::TEST_ADMIN_ID),
            customerQuery: new SqlCustomerQuery($this->pdo),
            orderQuery: new SqlOrderQuery($this->pdo),
            favorites: new SqlFavoriteStorage($this->pdo),
        );
    }

    public function testAnonymousAdminThrowsUnauthorized(): void
    {
        // Seed the customer to prove the AUTHZ check fires before any
        // existence probe — a 200-shape customer must still raise 403.
        $this->insertCustomer(['email' => 'authz@example.com']);

        $this->expectException(UnauthorizedAdminAccessException::class);
        new AdminCustomerFetched(
            email: 'authz@example.com',
            adminSession: new FakeAdminSession(null),
            customerQuery: new SqlCustomerQuery($this->pdo),
            orderQuery: new SqlOrderQuery($this->pdo),
            favorites: new SqlFavoriteStorage($this->pdo),
        );
    }
}
