<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
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
    }

    public function testOnDeleteHappyPathRemovesInstalledPlugin(): void
    {
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Sample/DisabledPlugin', $ro->body['pluginCode']);
        $this->assertTrue($ro->body['wasInstalled']);

        // Persistence read-back belongs to the SQL suite. Fake context is
        // static Ray.FakeQuery fixtures and does not mutate query state.
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

    #[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
    public function testOnDeleteReplayReturnsWasInstalledFalse(): void
    {
        $this->markTestSkipped('Stateful uninstall replay is covered by the SQL suite.');

        // First call: actually uninstalls.
        $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Replay: now wasInstalled=false.
        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['wasInstalled']);
    }

    public function testOnDeleteWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
