<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * Semantic render test for the Shopping error (goShoppingError) page.
 *
 * Verifies L1 (required data output) and L2 (navigation affordances) against
 * the IdeaStore design-language template. EC-CUBE markup parity tests have
 * been retired — the template is a clean-room rebuild, not a port of EC-CUBE.
 *
 * Resource contract (src/Resource/Page/Shopping/Error.php):
 *   GET page://self/shopping/error — pure renderer, no form fields, no CSRF.
 *   #[Link(rel: 'goCart', href: 'page://self/cart')] — sole outbound transition.
 *
 * L1 — Required field output
 *   • HTML document structure present (doctype, html[lang=ja], body)
 *   • Page title contains "購入手続きエラー" and "IDEA STORE"
 *   • Page heading communicates an error state
 *
 * L2 — Navigation affordances (link href / rel)
 *   • goCart link (rel="goCart") points to "/cart" — ALPS #goCart transition
 */
final class ShoppingErrorHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0 — HTTP contract ───────────────────────────────────────────────

    public function testRendersHtmlDocumentWithOkStatus(): void
    {
        $ro = $this->resource->get('page://self/shopping/error');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // ── L1 — Required field output ───────────────────────────────────────

    public function testPageTitleIdentifiesErrorAndBrand(): void
    {
        $html = $this->resource->get('page://self/shopping/error')->toString();

        $this->assertStringContainsString('購入手続きエラー', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testPageHeadingCommunicatesErrorState(): void
    {
        $html = $this->resource->get('page://self/shopping/error')->toString();

        // The page must carry an h1 that communicates purchase failure.
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('購入手続き', $html);
    }

    public function testNoUnrenderedTwigExpressionsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/error')->toString();

        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('undefined', $html);
    }

    // ── L2 — Navigation affordances ──────────────────────────────────────

    public function testGoCartLinkIsPresentWithCorrectHref(): void
    {
        $html = $this->resource->get('page://self/shopping/error')->toString();

        // rel="goCart" href="/cart" — ALPS #goCart transition declared in
        // src/Resource/Page/Shopping/Error.php via #[Link(rel:'goCart', href:'page://self/cart')]
        $this->assertStringContainsString('rel="goCart"', $html);
        $this->assertTrue(
            str_contains($html, 'href="/cart" rel="goCart"')
            || str_contains($html, 'rel="goCart" href="/cart"'),
            'goCart link must point to "/cart"',
        );
    }

    // ── Archived: EC-CUBE markup parity (clean-room rebuild) ─────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testShoppingErrorRendersAsHtmlDocument(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: template is a clean-room IdeaStore rebuild.'
            . ' Structural assertions have been replaced by L0/L1/L2 semantic tests.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testShoppingErrorPreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: template is a clean-room IdeaStore rebuild.'
            . ' Structural assertions have been replaced by L1/L2 semantic tests.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testShoppingErrorHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE reference rendering comparison retired: template is a clean-room IdeaStore rebuild.'
            . ' Archived under @group ec-cube-parity-archived.',
        );
    }
}
