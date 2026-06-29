<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * Phase 3 — functional and semantic render check for Help/About (goHelpAbout).
 *
 * IdeaStore clean-room rebuild: the EC-CUBE markup parity test has been
 * retired. Remaining assertions verify:
 *
 *   L1 — required fields/data present in the rendered document.
 *   L2 — navigation link href and rel attributes match the resource contract.
 *
 * EC-CUBE parity diff test (testHelpAboutHtmlMatchesEcCubeRenderingWithinResidualAllowlist)
 * is archived below with @group ec-cube-parity-archived.
 */
final class HelpAboutHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — required fields / data output
    // -------------------------------------------------------------------------

    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/help/about');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testPageTitleContainsSiteName(): void
    {
        $html = $this->resource->get('page://self/help/about')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testPageHeadingPresent(): void
    {
        $html = $this->resource->get('page://self/help/about')->toString();

        $this->assertStringContainsString('当サイトについて', $html);
    }

    /**
     * When staticContent.sections is empty (current Wave 3H state), the page
     * must render the "準備中" placeholder instead of an empty definition list.
     */
    public function testEmptySectionsRendersPlaceholder(): void
    {
        $html = $this->resource->get('page://self/help/about')->toString();

        $this->assertStringContainsString('id="idea-about-empty"', $html);
        $this->assertStringNotContainsString('id="idea-about-sections"', $html);
    }

    public function testUsesIdeaStoreDesignLanguage(): void
    {
        $html = $this->resource->get('page://self/help/about')->toString();

        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('idea-section', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — link href / rel matches resource contract (#[Link] in About.php)
    // -------------------------------------------------------------------------

    /**
     * About.php declares #[Link(rel: 'goTop', href: 'page://self/')].
     * The template must surface this as href="/" with rel="goTop".
     */
    public function testGoTopLinkHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/help/about')->toString();

        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('rel="goTop"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity archived
    // -------------------------------------------------------------------------

    /**
     * EC-CUBE markup structure parity check — retired when the template was
     * rebuilt with IdeaStore design language (idea-* classes).
     *
     * The original test asserted ec-role / ec-pageHeader / ec-off1Grid /
     * ec-borderedDefs nodes. Those nodes no longer exist in the IdeaStore
     * clean-room template and the assertion would always fail.
     *
     * @group ec-cube-parity-archived
     */
    public function testHelpAboutPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: template rebuilt with IdeaStore '
            . 'design language (idea-* classes). Structural assertions converted '
            . 'to L1/L2 functional tests above.'
        );
    }

    /**
     * EC-CUBE line-diff parity test — retired when the template was rebuilt
     * with IdeaStore design language.
     *
     * The original test diffed BeMart's rendered HTML against EC-CUBE 4.3's
     * real Help/about.twig (loaded from tools/ec-cube-source). The IdeaStore
     * clean-room template deliberately diverges from EC-CUBE's DOM, so a
     * structural line-diff is not meaningful.
     *
     * @group ec-cube-parity-archived
     */
    public function testHelpAboutHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity diff retired: template rebuilt clean-room in '
            . 'IdeaStore design language. EC-CUBE DOM structure intentionally '
            . 'absent. See testGoTopLinkHrefAndRel / testPageHeadingPresent '
            . 'for live functional coverage.'
        );
    }
}
