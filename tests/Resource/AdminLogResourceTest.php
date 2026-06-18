<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminLogForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function mb_strlen;

/**
 * Resource-layer coverage for the admin ログ表示 Tier-2 page.
 *
 * The resource is a GET renderer: it exposes a stable {@see AdminLogForm}
 * and tails a module-fixed log path (no request-supplied filename, so no
 * traversal surface). `log` is always a list of strings — real tail lines,
 * or empty when the bound file is absent. The AUTHZ guard rejects
 * anonymous admins with 403.
 */
final class AdminLogResourceTest extends TestCase
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

    public function testOnGetReturnsFormAndLogList(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/log');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminLogForm::class, $ro->body['form']);
        // Real tail of the module-fixed log path: a list of strings (may be
        // empty when the bound file is absent), each within the schema bound.
        $this->assertIsArray($ro->body['log']);
        foreach ($ro->body['log'] as $line) {
            $this->assertIsString($line);
            $this->assertLessThanOrEqual(255, mb_strlen($line));
        }
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/log');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
