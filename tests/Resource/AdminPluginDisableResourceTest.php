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
 * Wave 8 — doDisablePlugin resource coverage. Mirror of
 * AdminPluginEnableResourceTest with the opposite flag.
 */
final class AdminPluginDisableResourceTest extends TestCase
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

    public function testOnPostHappyPathDisablesEnabledPlugin(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['enabled']);
        $this->assertTrue($ro->body['changed']);

        // Persistence read-back belongs to the SQL suite. Fake context is
        // static Ray.FakeQuery fixtures and does not mutate query state.
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
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
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'NoSuch/Plugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/plugin-disable', [
            'pluginCode' => 'Sample/SamplePlugin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
