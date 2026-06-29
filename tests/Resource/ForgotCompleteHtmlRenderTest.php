<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * Semantic render test for ForgotComplete (goForgotComplete).
 *
 * Verifies:
 *   L1 — required fields and data output present in the rendered page.
 *   L2 — hypermedia links carry correct href and rel attributes.
 *
 * EC-CUBE parity diff tests have been retired:
 * @see testForgotCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist
 */
final class ForgotCompleteHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0: HTTP contract ───────────────────────────────────────────────────

    public function testResponseIsOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/forgot-complete');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: required fields / data output ──────────────────────────────────

    public function testRenderedHtmlIsADocument(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testPageTitleContainsBrandName(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testPageHeadingDescribesPasswordResetCompletion(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        // The page must communicate that the reset email was sent.
        $this->assertStringContainsString('パスワード', $html);
        $this->assertStringContainsString('メール', $html);
    }

    public function testPageExplainsNextStepForUser(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        // User must be told to check their email.
        $this->assertStringContainsString('メールアドレス', $html);
    }

    public function testIdeaStoreLayoutIsUsed(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        $this->assertStringContainsString('idea-store.css', $html);
        $this->assertStringContainsString('class="idea-store"', $html);
    }

    // ── L2: hypermedia — link href and rel ─────────────────────────────────

    public function testGoLoginLinkPointsToLoginPage(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        $this->assertStringContainsString('href="/login"', $html);
        $this->assertStringContainsString('rel="goLogin"', $html);
    }

    public function testGoTopLinkPointsToRoot(): void
    {
        $html = $this->resource->get('page://self/forgot-complete')->toString();

        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('rel="goTop"', $html);
    }

    // ── Retired: EC-CUBE parity ─────────────────────────────────────────────

    /**
     * EC-CUBE DOM parity check — retired after IdeaStore rebuild.
     *
     * The page was rewritten in the IdeaStore design language (idea-*
     * classes) as a clean-room implementation. It no longer shares DOM
     * structure with EC-CUBE's Forgot/complete.twig and therefore cannot
     * be diffed against it.
     *
     * @group ec-cube-parity-archived
     */
    public function testForgotCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity diff retired: page rebuilt in IdeaStore design language.'
        );
    }
}
