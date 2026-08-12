<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

use function str_contains;

/**
 * Functional / semantic render checks for the admin login page.
 *
 * This test verifies what the resource contract requires:
 *   L1 — required data fields and inputs are present in the HTML output.
 *   L2 — form action, method, and CSRF field are wired correctly.
 *   Frame — the idea-admin-* shell landmarks are present.
 *
 * EC-CUBE parity comparison has been removed. The login template is a
 * clean-room design in the idea-admin-* vocabulary and intentionally does
 * not mirror EC-CUBE's markup, classes, or layout.
 */
final class AdminLoginHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ─── L1: required data / inputs ───────────────────────────────────────

    public function testLoginRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/login');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testLoginRendersLoginIdInput(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('id="loginId"', $html);
        $this->assertStringContainsString('name="loginId"', $html);
    }

    public function testLoginRendersPasswordInput(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('id="admin_login_password"', $html);
    }

    public function testLoginFormShipsNoCredentials(): void
    {
        // The login page is anonymous-reachable, so it must never hand a
        // working admin credential to the visitor.
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringNotContainsString('value="test-admin"', $html);
        $this->assertStringNotContainsString('local-dev-admin-password', $html);
    }

    // ─── L2: form action / method / CSRF ──────────────────────────────────

    public function testFormPostsToAdminLogin(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('action="/admin/login"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testFormContainsCsrfHiddenInput(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        // name="csrfToken" must be present; the runtime value varies.
        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
    }

    public function testFormContainsModeHiddenInput(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('name="mode"', $html);
        $this->assertStringContainsString('value="login"', $html);
    }

    // ─── Frame: idea-admin-* shell landmarks ──────────────────────────────

    public function testPageUsesLoginContextFrame(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        // admin-login-base.html.twig provides these landmarks.
        $this->assertStringContainsString('idea-admin-login-page', $html);
        $this->assertStringContainsString('idea-admin-login-wrap', $html);
    }

    public function testPageUsesIdeaAdminCss(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('idea-admin.css', $html);
    }

    // ─── Error state re-render ─────────────────────────────────────────────

    public function testFailedPostRepopulatesLoginId(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'bad-user',
            'password' => 'bad-password',
            'mode'     => 'login',
        ]);

        $html = $ro->toString();

        // loginId is repopulated on failed POST.
        $this->assertStringContainsString('value="bad-user"', $html);
    }

    public function testFailedPostRendersErrorMessage(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'nobody',
            'password' => 'wrong',
            'mode'     => 'login',
        ]);

        $html = $ro->toString();

        // A domain error message is rendered.
        $this->assertTrue(
            str_contains($html, 'idea-admin-login-alert')
            || str_contains($html, 'idea-admin-error'),
            'Expected an error landmark (idea-admin-login-alert or idea-admin-error) in the failed-POST HTML',
        );
    }

    // ─── Archived: EC-CUBE parity comparison ──────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testLoginHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: Login.html.twig is a clean-room idea-admin-* design '
            . 'and intentionally does not mirror EC-CUBE markup.',
        );
    }
}
