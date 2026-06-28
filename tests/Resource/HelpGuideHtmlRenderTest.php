<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * L1/L2 semantic render verification for Help/Guide (goHelpGuide).
 *
 * The template is a clean-room IdeaStore page — ec-* class parity checks
 * against EC-CUBE's default-theme have been retired (see @group below).
 *
 * L1 — required fields / data output:
 *   - HTTP 200, Content-Type text/html
 *   - IdeaStore base layout rendered (doctype, lang="ja", idea-store body)
 *   - Page title block contains the Japanese title + brand suffix
 *   - Guide heading visible in the page body
 *   - Empty-state placeholder rendered when staticContent.sections is empty
 *
 * L2 — link href / rel:
 *   - goTop link → href="/" rel="goTop"
 *   - Breadcrumb home link → href="/"
 */
final class HelpGuideHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — required data output / structural fields
    // -------------------------------------------------------------------------

    public function testResponseIsHttp200WithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/help/guide');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testIdeaStoreBaseLayoutIsRendered(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testPageTitleContainsJapaneseTitleAndBrandSuffix(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('<title>ご利用ガイド | IDEA STORE</title>', $html);
    }

    public function testGuideHeadingIsRenderedInPageBody(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('ご利用ガイド', $html);
        $this->assertStringContainsString('id="idea-guide-title"', $html);
    }

    /**
     * When staticContent.sections is empty (current Wave 3H state), the page
     * must render the placeholder instead of an empty definition list.
     */
    public function testEmptySectionsRendersPlaceholder(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('id="idea-guide-empty"', $html);
        $this->assertStringNotContainsString('id="idea-guide-sections"', $html);
    }

    public function testIdeaStoreClassesAreUsedInBody(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        foreach ([
            'idea-container',
            'idea-section',
            'idea-eyebrow',
            'idea-section-title',
        ] as $class) {
            $this->assertStringContainsString($class, $html, "Missing IdeaStore class: {$class}");
        }
    }

    // -------------------------------------------------------------------------
    // L2 — link href / rel
    // -------------------------------------------------------------------------

    /**
     * Guide.php declares #[Link(rel: 'goTop', href: 'page://self/')].
     * The template must surface this as href="/" with rel="goTop".
     */
    public function testGoTopLinkHasCorrectHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('rel="goTop"', $html);
    }

    public function testBreadcrumbContainsHomeLink(): void
    {
        $html = $this->resource->get('page://self/help/guide')->toString();

        $this->assertStringContainsString('idea-breadcrumb', $html);
        $this->assertStringContainsString('<a href="/">Home</a>', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity — archived
    // -------------------------------------------------------------------------

    /**
     * EC-CUBE markup structure parity check — retired when the template was
     * rebuilt with IdeaStore design language (idea-* classes).
     *
     * The original test asserted ec-role / ec-pageHeader nodes.
     * Those nodes no longer exist in the IdeaStore clean-room template.
     *
     * @group ec-cube-parity-archived
     */
    public function testHelpGuidePagePreservesEcCubeMarkupStructure(): void
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
     * real Help/guide.twig. The IdeaStore clean-room template deliberately
     * diverges from EC-CUBE's DOM, so a structural line-diff is not meaningful.
     *
     * @group ec-cube-parity-archived
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testHelpGuideHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity diff retired: template rebuilt clean-room in '
            . 'IdeaStore design language. EC-CUBE DOM structure intentionally '
            . 'absent. See testGoTopLinkHasCorrectHrefAndRel / '
            . 'testGuideHeadingIsRenderedInPageBody for live functional coverage.'
        );
    }
}
