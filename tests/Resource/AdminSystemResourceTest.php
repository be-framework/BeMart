<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Resource-layer coverage for the admin システム情報 Tier-2 page.
 *
 * The resource is a thin GET renderer: it carries operational server
 * metadata in `body.info` and never invents server facts in Twig. The
 * AUTHZ guard mirrors every other Setting/System resource — an
 * anonymous admin gets 403.
 */
final class AdminSystemResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsSystemInfo(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/system');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('info', $ro->body);
        $this->assertFalse($ro->body['phpinfoEnabled']);

        $titles = array_column($ro->body['info'], 'title');
        $this->assertContains('PHP Version', $titles);
        $this->assertContains('Application', $titles);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/system');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
