<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin TradeLaw POST endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminTradeLawResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/trade-law`), same body-shape assertions,
 * same AUTHZ + CSRF + SemanticVariable branches. The differences:
 *
 *  - the storage binding (TradeLawStorageInterface → SqlTradeLawStorage)
 *    is layered via the base class's sqlOverrideModule; persistence is
 *    against the real `dtb_tradelaw` carrier row id=1 (singleton blob).
 *
 *  - the Fake-backed sibling reads the Fake storage directly
 *    (`$this->storage->item()->body`) to confirm the write landed; the
 *    SQL sibling instead reads back through the Resource layer (a
 *    follow-up GET) so the assertion exercises the full Becoming chain
 *    and proves persistence reached `dtb_tradelaw` — same pattern as
 *    {@see AdminBaseInfoResourceSqlTest}.
 *
 *  - the idempotent-replay assertion needs the SAME default on both
 *    sides. SqlTradeLawStorage::get returns TradeLawStorageInterface's
 *    installer-default body when `dtb_tradelaw` is empty (the first
 *    submit in the test triggers that fall-through). Re-submitting that
 *    exact body makes the Final report `changed=false` — same shape as
 *    the Fake-backed sibling.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminTradeLawResourceSqlTest extends AbstractResourceSqlTestCase
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
                $this->bind(AdminSession::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client
     * so the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnPostHappyPathUpdatesBody(): void
    {
        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => "販売業者: 新会社\n所在地: 東京都\n連絡先: 03-1234-5678",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertStringContainsString('新会社', $ro->body['tradeLawBody']);
        $this->assertTrue($ro->body['changed']);

        // Read-back through the Resource layer to confirm persistence
        // landed in dtb_tradelaw (vs only living in the Final).
        $next = $this->resource->get('page://self/admin/trade-law');
        $this->assertSame(Code::OK, $next->code);
        $this->assertStringContainsString('新会社', $next->body['tradeLawBody']);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // First GET hits SqlTradeLawStorage's installer-default
        // fall-through (dtb_tradelaw empty). Submit that exact body
        // back — the Final compares value-equal and reports
        // changed=false WITHOUT calling update(). Same shape as the
        // Fake-backed sibling.
        $seed = $this->resource->get('page://self/admin/trade-law');
        $this->assertSame(Code::OK, $seed->code);

        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => $seed->body['tradeLawBody'],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostEmptyBodyReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => 'whatever',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => 'whatever non-empty',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
