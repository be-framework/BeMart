<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for goPluginList + doInstallPlugin —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminPluginListResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs, same body-shape assertions, same AUTHN / CSRF branches.
 * The only difference is the storage binding (PluginStorageInterface →
 * SqlPluginStorage), layered via the base class's sqlOverrideModule.
 *
 * dtb_plugin is empty in the structure-only dump and has no FK
 * constraints — every test seeds the two demo plugins
 * (`Sample/SamplePlugin` enabled, `Sample/DisabledPlugin` disabled) via
 * {@see \MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait::seedPlugins}, so
 * the SQL backing starts from the same baseline the Fake's constructor
 * seeds. `installed` maps onto `dtb_plugin.initialized` — both seeds are
 * written initialized=1.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. Fake green AND SQL green proves
 * the storage swap left the client-observable behaviour untouched.
 */
final class AdminPluginListResourceSqlTest extends AbstractResourceSqlTestCase
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

    /**
     * Swap the admin session adminId and rebuild the Resource client so
     * the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetHappyPathReturnsSeededPlugins(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->get('page://self/admin/plugin-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);

        // Sorted by pluginCode ascending → DisabledPlugin first.
        $this->assertSame('Sample/DisabledPlugin', $ro->body['plugins'][0]['pluginCode']);
        $this->assertTrue($ro->body['plugins'][0]['installed']);
        $this->assertFalse($ro->body['plugins'][0]['enabled']);

        $this->assertSame('Sample/SamplePlugin', $ro->body['plugins'][1]['pluginCode']);
        $this->assertTrue($ro->body['plugins'][1]['installed']);
        $this->assertTrue($ro->body['plugins'][1]['enabled']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->seedPlugins();
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/plugin-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostHappyPathInstallsPlugin(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-list', [
            'pluginCode' => 'NewVendor/Plugin',
            'pluginName' => '新規プラグイン',
            'pluginVersion' => '1.0.0',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('NewVendor/Plugin', $ro->body['pluginCode']);
        $this->assertTrue($ro->body['installed']);
        // Install always lands in enabled=false per EC-CUBE convention.
        $this->assertFalse($ro->body['enabled']);
        $this->assertFalse($ro->body['alreadyInstalled']);

        // A second GET observes the new row — the Becoming chain reached
        // SqlPluginStorage::install (client-observable round-trip).
        $list = $this->resource->get('page://self/admin/plugin-list');
        $this->assertSame(3, $list->body['count']);
    }

    public function testOnPostReinstallExistingPluginReturns200AlreadyInstalled(): void
    {
        $this->seedPlugins();

        // Seed plugin is already installed — re-installing must be a
        // no-op and surface alreadyInstalled=true with a 200, not 201.
        $ro = $this->resource->post('page://self/admin/plugin-list', [
            'pluginCode' => 'Sample/SamplePlugin',
            'pluginName' => 'Whatever',
            'pluginVersion' => '9.9.9',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyInstalled']);
        // The original metadata MUST survive — re-install does NOT
        // overwrite the existing row's name/version.
        $this->assertSame('Sample Plugin', $ro->body['pluginName']);
        $this->assertSame('1.0.0', $ro->body['pluginVersion']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-list', [
            'pluginCode' => 'NewVendor/Plugin',
            'pluginName' => '新規プラグイン',
            'pluginVersion' => '1.0.0',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostBadPluginCodeReturns400(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-list', [
            'pluginCode' => 'has spaces',
            'pluginName' => '新規プラグイン',
            'pluginVersion' => '1.0.0',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->seedPlugins();
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-list', [
            'pluginCode' => 'NewVendor/Plugin',
            'pluginName' => '新規プラグイン',
            'pluginVersion' => '1.0.0',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
