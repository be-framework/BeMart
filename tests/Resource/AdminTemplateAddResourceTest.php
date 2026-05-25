<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminTemplateAddForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 — resource-layer coverage for the admin テンプレート登録
 * Store Tier-2 page (`admin/Store/template_add.twig`).
 *
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Template\TemplateAdd} is a
 * thin Pattern-B GET renderer for EC-CUBE's design-template upload
 * screen: it exposes a blank {@see AdminTemplateAddForm} with no Be
 * transition invoked, and the AUTHZ guard rejects anonymous admins.
 */
final class AdminTemplateAddResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private function resource(string|null $adminId): ResourceInterface
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetRendersUploadScreen(): void
    {
        $ro = $this->resource(self::TEST_ADMIN_ID)->get('page://self/admin/template/template-add');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminTemplateAddForm::class, $ro->body['form']);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $ro = $this->resource(null)->get('page://self/admin/template/template-add');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
