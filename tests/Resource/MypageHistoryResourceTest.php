<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for goMypageHistory.
 *
 * Mirrors ReorderResourceTest's rebindSession pattern. SEED_ORDER_NO
 * belongs to `customer-001`, so the happy-path session matches that
 * id; AUTHZ / AUTHN edge cases rebind to a foreign / null session.
 */
final class MypageHistoryResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindSession('customer-001');
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetHappyPathReturns200(): void
    {
        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(FakeFinalizedOrderStorage::SEED_ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(12700, $ro->body['total']);
        $this->assertSame(127, $ro->body['addPoint']);
        $this->assertCount(2, $ro->body['items']);
        $this->assertSame('sample-001', $ro->body['items'][0]['productCode']);
    }

    public function testOnGetUnknownOrderReturns404(): void
    {
        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'never00000000000000000000000000z',
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('never00000000000000000000000000z', $ro->body['orderNo']);
    }

    public function testOnGetWrongOwnerReturns403(): void
    {
        $this->rebindSession('customer-999');

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertSame(FakeFinalizedOrderStorage::SEED_ORDER_NO, $ro->body['orderNo']);
    }

    public function testOnGetAnonymousReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }
}
