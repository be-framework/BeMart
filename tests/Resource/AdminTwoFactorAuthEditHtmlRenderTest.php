<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * HTML render smoke test — admin 二要素認証デバイス設定 (TwoFactorAuthEdit).
 *
 * L1: required data fields present in rendered output.
 * L2: action / method / link href + rel semantics.
 * Frame: idea-admin-shell landmark structure.
 */
final class AdminTwoFactorAuthEditHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
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

    /** GET returns 200 and emits a well-formed HTML document. */
    public function testGetReturnsOkWithHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1 — required data: device token input, hidden authKey input.
     * The form renders both fields from AdminTwoFactorAuthForm.
     */
    public function testL1RequiredFormFieldsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth-edit')->toString();

        // deviceToken text input (id set by AdminTwoFactorAuthForm)
        $this->assertStringContainsString('id="admin_two_factor_auth_device_token"', $html);
        $this->assertStringContainsString('name="deviceToken"', $html);

        // authKey hidden input
        $this->assertStringContainsString('id="admin_two_factor_auth_auth_key"', $html);
        $this->assertStringContainsString('name="authKey"', $html);
        $this->assertStringContainsString('type="hidden"', $html);

        // CSRF hidden input
        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /**
     * L2 — action / method: form posts to the correct endpoint.
     * TwoFactorAuthEdit has no onPost in this resource; the form action
     * targets /admin/two-factor-auth-edit and uses POST.
     */
    public function testL2FormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth-edit')->toString();

        $this->assertStringContainsString('action="/admin/two-factor-auth-edit"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * L2 — back link href + rel (goAdminHome).
     */
    public function testL2BackLinkGoAdminHome(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth-edit')->toString();

        $this->assertMatchesRegularExpression(
            '#href="/admin/index"[^>]*rel="goAdminHome"|rel="goAdminHome"[^>]*href="/admin/index"#',
            $html,
        );
    }

    /**
     * Frame landmark — idea-admin-shell / idea-admin-content present.
     */
    public function testFrameLandmarksPresent(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth-edit')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }
}
