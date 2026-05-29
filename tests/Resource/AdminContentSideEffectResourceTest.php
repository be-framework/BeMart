<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource coverage for the content/file side-effect admin pages
 * (cache / css / js / maintenance) — onPut drives the Be transitions.
 */
final class AdminContentSideEffectResourceTest extends TestCase
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

    public function testClearCache(): void
    {
        $ro = $this->resource->put('page://self/admin/content/cache', ['csrfToken' => FakeCsrfToken::TOKEN]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doClearCache', $ro->body['transitionId']);
    }

    public function testUpdateCss(): void
    {
        $ro = $this->resource->put('page://self/admin/content/css', ['css' => 'body{}', 'csrfToken' => FakeCsrfToken::TOKEN]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateContentCss', $ro->body['transitionId']);
    }

    public function testUpdateJs(): void
    {
        $ro = $this->resource->put('page://self/admin/content/js', ['js' => 'void 0', 'csrfToken' => FakeCsrfToken::TOKEN]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateContentJs', $ro->body['transitionId']);
    }

    public function testToggleMaintenance(): void
    {
        $ro = $this->resource->put('page://self/admin/content/maintenance', ['enabled' => true, 'csrfToken' => FakeCsrfToken::TOKEN]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doToggleMaintenance', $ro->body['transitionId']);
        $this->assertTrue($ro->body['isMaintenance']);
    }

    public function testCacheMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/admin/content/cache', []);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCssAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->put('page://self/admin/content/css', ['css' => 'x', 'csrfToken' => FakeCsrfToken::TOKEN]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
