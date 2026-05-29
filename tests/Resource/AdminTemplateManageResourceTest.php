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
 * Resource coverage for the design-template management verbs on
 * /template-list (select PUT / delete DELETE / download POST) and the
 * install POST on /template-add.
 */
final class AdminTemplateManageResourceTest extends TestCase
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

    public function testSelect(): void
    {
        $ro = $this->resource->put('page://self/admin/template/template-list', [
            'templateId' => 'default',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doSelectTemplate', $ro->body['transitionId']);
    }

    public function testSelectUnknownReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/template/template-list', [
            'templateId' => 'no-such',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDelete(): void
    {
        $ro = $this->resource->delete('page://self/admin/template/template-list', [
            'templateId' => 'default',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doDeleteTemplate', $ro->body['transitionId']);
    }

    public function testDownload(): void
    {
        $ro = $this->resource->post('page://self/admin/template/template-list', [
            'templateId' => 'default',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('Content-Disposition', $ro->headers);
    }

    public function testInstall(): void
    {
        $ro = $this->resource->post('page://self/admin/template/template-add', [
            'templateCode' => 'mytheme',
            'templateName' => 'My Theme',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doInstallTemplate', $ro->body['transitionId']);
    }

    public function testSelectMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/admin/template/template-list', ['templateId' => 'default']);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testInstallAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/template/template-add', [
            'templateCode' => 'x',
            'templateName' => 'y',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
