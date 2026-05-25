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
 * Wave 8 — doUninstallPlugin resource coverage.
 *
 * DELETE on page://self/admin/plugin. Idempotent — unknown /
 * already-uninstalled plugins return 200 with wasInstalled=false.
 */
final class AdminPluginResourceTest extends TestCase
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

    public function testOnDeleteHappyPathRemovesInstalledPlugin(): void
    {
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => FakePluginStorage::SEED_DISABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(FakePluginStorage::SEED_DISABLED_CODE, $ro->body['pluginCode']);
        $this->assertTrue($ro->body['wasInstalled']);

        $this->assertNull($this->storage->findByCode(FakePluginStorage::SEED_DISABLED_CODE));
    }

    public function testOnDeleteUnknownPluginReturnsWasInstalledFalse(): void
    {
        // Idempotent: unknown plugin → 200, wasInstalled=false.
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['wasInstalled']);
    }

    public function testOnDeleteReplayReturnsWasInstalledFalse(): void
    {
        // First call: actually uninstalls.
        $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => FakePluginStorage::SEED_ENABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Replay: now wasInstalled=false.
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => FakePluginStorage::SEED_ENABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['wasInstalled']);
    }

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => FakePluginStorage::SEED_DISABLED_CODE,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
        $this->assertNotNull($this->storage->findByCode(FakePluginStorage::SEED_DISABLED_CODE));
    }

    public function testOnDeleteWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => FakePluginStorage::SEED_DISABLED_CODE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
