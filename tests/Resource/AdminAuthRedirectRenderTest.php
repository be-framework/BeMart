<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Render-time admin firewall UX (AdminAuthRedirectRenderer).
 *
 * The resource-level contract stays 403 + message; only the HTML transfer
 * turns an anonymous Page\Admin response into a 303 to the login page, so
 * browsers never see a page skeleton rendered with an empty body (the
 * "zeroed KPI dashboard" regression).
 */
final class AdminAuthRedirectRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession();
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testAnonymousAdminPageKeeps403AtResourceLevel(): void
    {
        $ro = $this->resource->get('page://self/admin/index');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testAnonymousAdminPageRendersAsRedirectToLogin(): void
    {
        $ro = $this->resource->get('page://self/admin/index');

        $ro->toString(); // trigger render (transfer-time decoration)

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/login', $ro->headers['Location']);
    }
}
