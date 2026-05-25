<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeTradeLawStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 — doUpdateTradeLaw resource coverage.
 */
final class AdminTradeLawResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private Injector $injector;
    private FakeTradeLawStorage $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(FakeTradeLawStorage::class);
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

        $this->assertStringContainsString('新会社', $this->storage->get()->body);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $current = $this->storage->get();
        $ro = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => $current->body,
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
