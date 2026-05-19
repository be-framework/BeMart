<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakePluginStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 — doDisablePlugin resource coverage. Mirror of
 * AdminPluginEnableResourceTest with the opposite flag.
 */
final class AdminPluginDisableResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private Injector $injector;
    private FakePluginStorage $storage;

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
        $this->storage = $this->injector->getInstance(FakePluginStorage::class);
    }

    public function testOnPostHappyPathDisablesEnabledPlugin(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => FakePluginStorage::SEED_ENABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['enabled']);
        $this->assertTrue($ro->body['changed']);

        $persisted = $this->storage->findByCode(FakePluginStorage::SEED_ENABLED_CODE);
        $this->assertNotNull($persisted);
        $this->assertFalse($persisted->enabled);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // Seed-disabled plugin is already disabled — replay is a no-op.
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => FakePluginStorage::SEED_DISABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['enabled']);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostUnknownPluginReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => FakePluginStorage::SEED_ENABLED_CODE,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => FakePluginStorage::SEED_ENABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
