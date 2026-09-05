<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Functional / semantic render verification for the Login (goLogin) page.
 *
 * The page was rebuilt as a cleanroom IdeaStore design-language template
 * (idea-* classes, IdeaStore/layout/base.html.twig). The former EC-CUBE
 * parity diff tests are retired to @group ec-cube-parity-archived.
 *
 * L1 — required fields and data output present.
 * L2 — form action / method and navigation link href / rel contract.
 */
final class LoginHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -----------------------------------------------------------------------
    // L1: required fields / data output
    // -----------------------------------------------------------------------

    public function testLoginPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/login');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testLoginPageTitleContainsIdeaStore(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
        $this->assertStringContainsString('ログイン', $html);
    }

    public function testLoginPageRendersEmailField(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        // The email field must be present with its name attribute.
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('id="email"', $html);
    }

    public function testLoginPageRendersPasswordField(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('id="password"', $html);
        $this->assertStringContainsString('type="password"', $html);
    }

    public function testLoginFormShipsNoCredentials(): void
    {
        // The login page is anonymous-reachable, so it must never hand a
        // working member credential to the visitor.
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringNotContainsString('value="login-test@example.com"', $html);
        $this->assertStringNotContainsString('local-dev-member-password', $html);
    }

    public function testLoginPageRendersCsrfHiddenField(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    public function testLoginPageRendersModeHiddenField(): void
    {
        // mode=login is the browser-form marker that triggers PRG / error
        // re-render in Login::onPost — must be present.
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringContainsString('name="mode"', $html);
        $this->assertStringContainsString('value="login"', $html);
    }

    public function testLoginPageRendersSubmitButton(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('ログイン', $html);
    }

    // -----------------------------------------------------------------------
    // L2: form action / method and navigation link href
    // -----------------------------------------------------------------------

    public function testLoginFormPostsToLoginEndpoint(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        // action must match the resource Link(rel="doLogin", href="page://self/login", method="post")
        $this->assertMatchesRegularExpression('/action=["\']\/login["\']/', $html);
        $this->assertMatchesRegularExpression('/method=["\']post["\']/', $html);
    }

    public function testLoginPageLinksToForgotPassword(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        // rel="doRequestPasswordReset" -> href="page://self/forgot-password"
        $this->assertStringContainsString('href="/forgot-password"', $html);
    }

    public function testLoginPageLinksToRegistration(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        // rel="goCustomerRegistration" -> href="page://self/entry"
        $this->assertStringContainsString('href="/entry"', $html);
    }

    public function testLoginPageUsesIdeaStoreLayout(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        // Must extend IdeaStore base (header landmark present, idea-* classes used).
        $this->assertStringContainsString('idea-store', $html);
        $this->assertStringContainsString('idea-button', $html);
        $this->assertStringContainsString('idea-field', $html);
    }

    // -----------------------------------------------------------------------
    // EC-CUBE parity archived
    // -----------------------------------------------------------------------

    /**
     * The former EC-CUBE structural parity diff test is retired. The template
     * is now a cleanroom IdeaStore design — EC-CUBE class/DOM parity is not
     * a goal. Functional equivalence is verified by the L1/L2 tests above.
     */
    #[Group('ec-cube-parity-archived')]
    public function testLoginHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity diff retired: Login.html.twig rebuilt as cleanroom '
            . 'IdeaStore design. Functional coverage moved to L1/L2 tests above.',
        );
    }

    /**
     * Retired: EC-CUBE class names (ec-role, ec-pageHeader, etc.) are no
     * longer present — the template uses idea-* vocabulary exclusively.
     */
    #[Group('ec-cube-parity-archived')]
    public function testLoginPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE class parity retired: template rebuilt with idea-* classes.',
        );
    }
}
