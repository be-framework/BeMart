<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doDisablePlugin — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminPluginDisableResourceTest}
 * (Phase 2b, G-23 storage migration contract). Mirror of
 * {@see AdminPluginEnableResourceSqlTest} with the opposite flag.
 *
 * Same URIs, same body-shape assertions, same AUTHN / CSRF / 404
 * branches as the Fake-backed sibling; the only difference is the
 * storage binding (PluginStorageInterface → SqlPluginStorage), layered
 * via the base class's sqlOverrideModule.
 */
final class AdminPluginDisableResourceSqlTest extends AbstractResourceSqlTestCase
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

    public function testOnPostHappyPathDisablesEnabledPlugin(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['enabled']);
        $this->assertTrue($ro->body['changed']);

        // A follow-up list observes the now-disabled row.
        $list = $this->resource->get('page://self/admin/plugin-list');
        $this->assertSame('Sample/SamplePlugin', $list->body['plugins'][1]['pluginCode']);
        $this->assertFalse($list->body['plugins'][1]['enabled']);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $this->seedPlugins();

        // Seed-disabled plugin is already disabled — replay is a no-op.
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/DisabledPlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['enabled']);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostUnknownPluginReturns404(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->seedPlugins();

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->seedPlugins();
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
