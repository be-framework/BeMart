<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 9 — resource-layer coverage for the admin Layout endpoints.
 */
final class AdminLayoutResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private LayoutStorageInterface $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->storage = $injector->getInstance(LayoutStorageInterface::class);
    }

    public function testListIncludesSeed(): void
    {
        $ro = $this->resource->get('page://self/admin/layout/layout-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/layout/layout-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => 'lo-pc-default',
            'layoutName' => 'PC Refreshed',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('PC Refreshed', $ro->body['layoutName']);
    }

    public function testUpdateUnknownReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => 'nonexistent',
            'layoutName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => 'lo-pc-default',
            'layoutName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
