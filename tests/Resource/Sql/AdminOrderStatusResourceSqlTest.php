<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doUpdateOrderStatus (Phase 2b).
 *
 * Mirrors {@see \MyVendor\BeMart\Tests\Resource\AdminOrderStatusResourceTest}
 * but exercises the SQL backends end-to-end via
 * `ResourceInterface::post('page://self/admin/order-status', ...)`.
 *
 * Per G-23 this is the migration contract: the same resource URI must
 * produce the same body shape whether `OrderQueryInterface` /
 * `OrderCommandInterface` resolve to the Fake or the SQL pair. The
 * Fake-backed sibling stays untouched; this SQL sibling seeds rows via
 * the SqlFixturesTrait helpers (no `var/fake/orders.json` involvement).
 *
 * AdminSession is rebound per-test with a {@see FakeAdminSession}
 * carrying the desired adminId (or null for anonymous-as-admin) — the
 * same `rebindAdminSession` pattern the Fake-backed sibling uses,
 * layered on top of the SQL override.
 */
final class AdminOrderStatusResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Swap the admin session adminId and rebuild the Resource client so
     * the new binding takes effect.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnPostHappyPathFlipsStatus(): void
    {
        $order = $this->insertOrder([
            'order_no' => 'SQL-STAT-ORD-1',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => $order['orderNo'],
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($order['orderNo'], $ro->body['orderNo']);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $ro->body['previousStatus']);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $ro->body['orderStatus']);
        $this->assertTrue($ro->body['changed']);

        // Read-after-write: the flip is durable through SqlOrderCommand.
        $stmt = $this->pdo->prepare(
            'SELECT order_status_id FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => $order['orderNo']]);
        $this->assertSame(
            FinalizedOrderEntity::STATUS_DELIVERED,
            (int) $stmt->fetchColumn(),
        );
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $order = $this->insertOrder([
            'order_no' => 'SQL-STAT-ORD-2',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        // First flip.
        $this->resource->post('page://self/admin/order-status', [
            'orderNo' => $order['orderNo'],
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Replay with the same status — no second write.
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => $order['orderNo'],
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $ro->body['previousStatus']);
    }

    public function testOnPostUnknownOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => 'nonexistentordernononononononono',
            'orderStatus' => FinalizedOrderEntity::STATUS_CANCEL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $order = $this->insertOrder([
            'order_no' => 'SQL-STAT-ORD-3',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => $order['orderNo'],
            'orderStatus' => FinalizedOrderEntity::STATUS_CANCEL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
