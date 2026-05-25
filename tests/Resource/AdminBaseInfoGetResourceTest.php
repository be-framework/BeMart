<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminShopMasterForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 9ι — goBaseInfo resource coverage. Safe read pair to
 * Wave 8ε doUpdateBaseInfo on the same URI.
 */
final class AdminBaseInfoGetResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeBaseInfoStorage $storage;

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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->storage = $injector->getInstance(FakeBaseInfoStorage::class);
    }

    public function testOnGetReturnsSeedBaseInfo(): void
    {
        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $ro->code);
        $seed = $this->storage->get();
        $this->assertSame($seed->shopName, $ro->body['shopName']);
        $this->assertSame($seed->companyName, $ro->body['companyName']);
        $this->assertSame($seed->pref, $ro->body['pref']);
        $this->assertSame($seed->shopMessage, $ro->body['shopMessage']);
        // changed flag (write-only field) MUST NOT leak into the read body.
        $this->assertArrayNotHasKey('changed', $ro->body);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnGetCarriesShopMasterForm(): void
    {
        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminShopMasterForm::class, $ro->body['form']);
    }
}
