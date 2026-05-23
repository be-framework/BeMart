<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doUninstallPlugin — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminPluginResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * DELETE on page://self/admin/plugin. Idempotent — unknown /
 * already-uninstalled plugins return 200 with wasInstalled=false.
 *
 * Same URIs, same body-shape assertions, same AUTHN / CSRF branches as
 * the Fake-backed sibling; the only difference is the storage binding
 * (PluginStorageInterface → SqlPluginStorage), layered via the base
 * class's sqlOverrideModule.
 *
 * Per G-23 the Fake-backed test verifies the round-trip by reading the
 * storage back out of the injector; the SQL sibling cannot reach a
 * private injector, so it verifies the round-trip the way a client
 * would — a follow-up GET on plugin-list observes the post-delete row
 * set.
 */
final class AdminPluginResourceSqlTest extends AbstractResourceSqlTestCase
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

    /** @param non-empty-string|null $adminId */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnDeleteHappyPathRemovesInstalledPlugin(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Sample/DisabledPlugin', $ro->body['pluginCode']);
        $this->assertTrue($ro->body['wasInstalled']);

        // The row is gone — a follow-up list no longer surfaces it.
        $list = $this->resource->get('page://self/admin/plugin-list');
        $this->assertSame(1, $list->body['count']);
        $this->assertSame('Sample/SamplePlugin', $list->body['plugins'][0]['pluginCode']);
    }

    public function testOnDeleteUnknownPluginReturnsWasInstalledFalse(): void
    {
        $this->seedPlugins();

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
        $this->seedPlugins();

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

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/DisabledPlugin',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);

        // The plugin is still present — the rejected request did not
        // reach the storage.
        $list = $this->resource->get('page://self/admin/plugin-list');
        $this->assertSame(2, $list->body['count']);
    }

    public function testOnDeleteWithoutAdminSessionReturns403(): void
    {
        $this->seedPlugins();
        $this->rebindAdminSession(null);

        $ro = $this->resource->delete('page://self/admin/plugin', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
