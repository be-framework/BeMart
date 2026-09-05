<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * Help/TradeLaw (goHelpTradeLaw) HTML render test.
 *
 * L1 — required fields and data output present in the document.
 * L2 — hypermedia: link href/rel correct.
 *
 * EC-CUBE DOM parity assertions have been retired:
 * @see testHelpTradeLawPagePreservesEcCubeMarkupStructure (archived below)
 * @see testHelpTradeLawHtmlMatchesEcCubeRenderingWithinResidualAllowlist (archived below)
 */
final class HelpTradeLawHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — Required fields / data output
    // -------------------------------------------------------------------------

    /** The resource responds with HTTP 200 and text/html. */
    public function testHelpTradeLawPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/help/trade-law');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** Page title contains the Japanese statutory label. */
    public function testPageTitleContainsStatutoryLabel(): void
    {
        $html = $this->resource->get('page://self/help/trade-law')->toString();

        $this->assertStringContainsString('特定商取引法に基づく表記', $html);
    }

    /** IdeaStore layout elements are present (base extends IdeaStore/layout/base.html.twig). */
    public function testIdeaStoreLayoutIsUsed(): void
    {
        $html = $this->resource->get('page://self/help/trade-law')->toString();

        // <title> block rendered by IdeaStore base
        $this->assertStringContainsString('IDEA STORE', $html);
        // idea-store CSS is linked
        $this->assertStringContainsString('idea-store.css', $html);
    }

    /** The page heading is rendered with an IdeaStore section-title class. */
    public function testPageHeadingRenderedWithIdeaSectionTitle(): void
    {
        $html = $this->resource->get('page://self/help/trade-law')->toString();

        $this->assertStringContainsString('idea-section-title', $html);
        $this->assertStringContainsString('特定商取引法に基づく表記', $html);
    }

    /** When staticContent.sections is empty the placeholder text is shown. */
    public function testEmptySectionsShowsPlaceholder(): void
    {
        $html = $this->resource->get('page://self/help/trade-law')->toString();

        // The resource currently returns no sections (Wave 3H pure renderer).
        // The template must render its empty-state message.
        $this->assertStringContainsString('idea-tradelaw-empty', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — Hypermedia: link href / rel
    // -------------------------------------------------------------------------

    /** The goTop back-link points to "/" with rel="goTop". */
    public function testGoTopLinkPresent(): void
    {
        $html = $this->resource->get('page://self/help/trade-law')->toString();

        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('rel="goTop"', $html);
    }

    // -------------------------------------------------------------------------
    // Archived: EC-CUBE DOM parity — retired in IdeaStore cleanroom rebuild
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testHelpTradeLawPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM parity retired: template rebuilt in IdeaStore design language. '
            . 'Structural assertions replaced by L1/L2 semantic tests above.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testHelpTradeLawHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE render diff retired: template rebuilt in IdeaStore design language. '
            . 'EC-CUBE reference rendering no longer applies.',
        );
    }
}
