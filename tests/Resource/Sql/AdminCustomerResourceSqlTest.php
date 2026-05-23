<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for goCustomer (Phase 2a Step 5).
 *
 * Mirrors the shape of {@see \MyVendor\BeMart\Tests\Resource\AdminCustomerResourceTest}
 * but exercises the SQL backends end-to-end via
 * `ResourceInterface::get('page://self/admin/customer', ['email' => ...])`.
 *
 * The fixtures are inserted directly via the SqlFixturesTrait helpers
 * (no `var/fake/customers.json` involvement) — proving that the same
 * resource URI produces the same body shape regardless of which
 * storage backend is bound.
 *
 * AdminSession is rebound per-test with a {@see FakeAdminSession}
 * carrying the desired adminId (or null for anonymous-as-admin) —
 * same `rebindAdminSession` pattern as the Fake-backed sibling test,
 * just layered on top of the SQL override.
 */
final class AdminCustomerResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    protected function extraOverride(): AbstractModule|null
    {
        $adminId = $this->currentAdminId;

        return new class ($adminId) extends AbstractModule {
            /** @param non-empty-string|null $adminId */
            public function __construct(private readonly string|null $adminId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client
     * so the new binding takes effect — mirrors the customer-side
     * rebindSession helper used elsewhere in the suite.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetHappyPathAggregatesSqlBackends(): void
    {
        $customerId = $this->insertCustomer([
            'email' => 'sql-admin@example.com',
            'name01' => 'Yamada',
            'name02' => 'Hanako',
            'kana01' => 'ヤマダ',
            'kana02' => 'ハナコ',
            'phone_number' => '0312345678',
            'postal_code' => '1500001',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'birth' => '1985-06-15 00:00:00',
        ]);

        // 3 finalized orders — totalSpent 1000 + 2000 + 3000 = 6000.
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'SQL-ORD-A',
            'total' => 1000,
            'payment_total' => 1000,
            'order_date' => '2026-05-01 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'SQL-ORD-B',
            'total' => 2000,
            'payment_total' => 2000,
            'order_date' => '2026-05-05 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'SQL-ORD-C',
            'total' => 3000,
            'payment_total' => 3000,
            'order_date' => '2026-05-10 10:00:00',
        ]);

        // 2 favorites — each needs a product + default product_class row.
        $productA = $this->insertProduct([
            'name' => 'Apple',
            'product_code' => 'SQL-FAV-A',
            'price02' => 500,
        ]);
        $productB = $this->insertProduct([
            'name' => 'Banana',
            'product_code' => 'SQL-FAV-B',
            'price02' => 800,
        ]);
        $this->insertFavorite($customerId, $productA);
        $this->insertFavorite($customerId, $productB);

        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => 'sql-admin@example.com',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame((string) $customerId, $ro->body['customerId']);
        $this->assertSame('sql-admin@example.com', $ro->body['email']);
        $this->assertSame('Yamada', $ro->body['name01']);
        $this->assertSame('Hanako', $ro->body['name02']);
        $this->assertSame('渋谷区', $ro->body['addr01']);

        // Order aggregation through SqlOrderQuery.
        $this->assertSame(3, $ro->body['orderCount']);
        $this->assertSame(6000, $ro->body['totalSpent']);
        $this->assertCount(3, $ro->body['orders']);
        // ORDER BY order_date DESC — newest first.
        $this->assertSame('SQL-ORD-C', $ro->body['orders'][0]['orderNo']);

        // Favorites projection through SqlFavoriteStorage.
        $this->assertSame(2, $ro->body['favoriteCount']);
        $this->assertCount(2, $ro->body['favorites']);
        $favCodes = array_map(
            static fn (array $r): string => $r['productCode'],
            $ro->body['favorites'],
        );
        sort($favCodes);
        $this->assertSame(['SQL-FAV-A', 'SQL-FAV-B'], $favCodes);
    }

    public function testOnGetUnknownEmailReturns404(): void
    {
        $this->insertCustomer(['email' => 'present@example.com']);

        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => 'absent@example.com',
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertStringContainsString('会員', $ro->body['message']);
    }

    public function testOnGetNoAdminSessionReturns403(): void
    {
        // Seed the customer so the AUTHZ check fires before existence —
        // a 200-shape email must still raise 403 (anti-enumeration).
        $this->insertCustomer(['email' => 'authz@example.com']);

        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => 'authz@example.com',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
        $this->assertArrayNotHasKey('email', $ro->body);
    }
}
