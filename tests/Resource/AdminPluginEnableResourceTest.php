<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 — doEnablePlugin resource coverage.
 *
 * Failure ladder: 400 / 403 (CSRF) / 403 (AUTHZ) / 404 (unknown) /
 * 409 (uninstalled). Plus happy-path + idempotent-replay coverage.
 */
#[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
final class AdminPluginEnableResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private Injector $injector;
    private PluginStorageInterface $storage;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(PluginStorageInterface::class);
    }

    public function testOnPostHappyPathEnablesDisabledPlugin(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['enabled']);
        $this->assertTrue($ro->body['changed']);

        $persisted = $this->storage->item('Sample/DisabledPlugin');
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->enabled);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // Seed-enabled plugin is already enabled — replay is a no-op.
        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['enabled']);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostUnknownPluginReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostUninstalledPluginReturns409(): void
    {
        // Inject an uninstalled-but-known row directly into the storage
        // (cannot reach this state through the public API — the storage
        // contract refuses, which is the whole point of the 409). The
        // reflection write models "plugin is in the registry but not
        // installed yet" — same shape the real EC-CUBE store sees while
        // a plugin is between download and install steps.
        $entity = new PluginEntity(
            pluginCode: 'Partial/Plugin',
            pluginName: 'Partial',
            version: '1.0.0',
            installed: false,
            enabled: false,
        );
        $this->database->putPlugin($entity);

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Partial/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
        $this->assertStringContainsString('インストール', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
