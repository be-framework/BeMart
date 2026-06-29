<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * Functional render check for the admin 2FA challenge page.
 *
 * Verifies the idea-admin-* frame landmarks, the doVerifyTwoFactorAuth form
 * contract (action, method, required fields), and semantic structure.
 * EC-CUBE parity assertions have been removed: this page is a clean-room
 * redesign and no longer mirrors the EC-CUBE markup.
 */
final class AdminTwoFactorAuthHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** L1 — page renders a complete HTML document. */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** L1 — idea-admin-login-page frame landmark is present. */
    public function testRendersLoginFrameLandmark(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('idea-admin-login-page', $html);
        $this->assertStringContainsString('idea-admin-login-wrap', $html);
    }

    /** L1 — deviceToken field is rendered by the real form. */
    public function testRendersDeviceTokenField(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('id="admin_two_factor_auth_device_token"', $html);
        $this->assertStringContainsString('name="deviceToken"', $html);
        $this->assertStringContainsString('maxlength="6"', $html);
    }

    /** L2 — form action and method match the doVerifyTwoFactorAuth contract. */
    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('action="/admin/two-factor-auth"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /** L2 — CSRF token hidden field is rendered. */
    public function testCsrfTokenFieldIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** L2 — goAdminLogin back link is present. */
    public function testBackLinkToAdminLogin(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('/admin/login', $html);
    }

    /**
     * EC-CUBE parity check (markup mirror) — archived.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeParity(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity check archived: TwoFactorAuth.html.twig is a '
            . 'clean-room redesign in idea-admin-* vocabulary and no longer mirrors '
            . 'the EC-CUBE admin two_factor_auth.twig structure.',
        );
    }
}
