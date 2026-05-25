<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doEnablePlugin — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminPluginEnableResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Failure ladder: 400 / 403 (CSRF) / 403 (AUTHZ) / 404 (unknown) /
 * 409 (uninstalled). Plus happy-path + idempotent-replay coverage.
 *
 * Same URIs, same body-shape assertions, same failure ladder as the
 * Fake-backed sibling; the only difference is the storage binding
 * (PluginStorageInterface → SqlPluginStorage), layered via the base
 * class's sqlOverrideModule.
 *
 * The 409 (uninstalled) case: the Fake-backed sibling reaches the
 * registered-but-not-installed state via reflection on the Fake's
 * private `byCode` map. The SQL sibling reaches the SAME state honestly
 * — `insertPlugin(['initialized' => 0])` seeds a dtb_plugin row that is
 * present but not installed (initialized=0), exactly the shape the real
 * EC-CUBE store carries while a plugin is between download and install.
 * No reflection required.
 */
final class AdminPluginEnableResourceSqlTest extends AbstractResourceSqlTestCase
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

    /** @param non-empty-string|null $adminId */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnPostHappyPathEnablesDisabledPlugin(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['enabled']);
        $this->assertTrue($ro->body['changed']);

        // A follow-up list observes the now-enabled row.
        $list = $this->resource->get('page://self/admin/plugin-list');
        $this->assertTrue($list->body['plugins'][0]['enabled']);
        $this->assertSame('Sample/DisabledPlugin', $list->body['plugins'][0]['pluginCode']);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $this->seedPlugins();

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
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostUninstalledPluginReturns409(): void
    {
        $this->seedPlugins();

        // Seed a registered-but-not-installed row directly — a dtb_plugin
        // row with initialized=0. This is the SQL-honest equivalent of
        // the Fake-backed sibling's reflection write; it models "plugin
        // is in the registry but not installed yet", the same state the
        // real EC-CUBE store sees between a plugin's download and install
        // steps. setEnabled refuses it → PluginNotInstalledException →
        // 409.
        $this->insertPlugin([
            'code' => 'Partial/Plugin',
            'name' => 'Partial',
            'version' => '1.0.0',
            'initialized' => 0,
            'enabled' => 0,
        ]);

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Partial/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
        $this->assertStringContainsString('インストール', $ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/DisabledPlugin',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->seedPlugins();
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-enable', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
