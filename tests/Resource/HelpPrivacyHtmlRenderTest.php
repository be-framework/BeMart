<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * L1/L2 semantic render verification for Help/privacy (goHelpPrivacy).
 *
 * The template is a clean-room IdeaStore page — ec-* class parity checks
 * against EC-CUBE's default-theme have been retired (see @group below).
 *
 * L1 — required fields / data output:
 *   - HTTP 200, Content-Type text/html
 *   - IdeaStore base layout rendered (doctype, lang="ja", idea-store body)
 *   - Page title block contains the Japanese title + brand suffix
 *   - Privacy heading visible in the page body
 *   - At minimum one article/section heading present
 *   - Back-to-top link rendered with correct href
 *
 * L2 — link href / rel:
 *   - goTop link → href="/" rel="goTop"
 *   - Breadcrumb home link → href="/"
 */
final class HelpPrivacyHtmlRenderTest extends TestCase
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
        $ro = $this->resource->get('page://self/help/privacy');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testIdeaStoreBaseLayoutIsRendered(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testPageTitleContainsJapaneseTitleAndBrandSuffix(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        $this->assertStringContainsString('<title>プライバシーポリシー | IDEA STORE</title>', $html);
    }

    public function testPrivacyHeadingIsRenderedInPageBody(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        $this->assertStringContainsString('プライバシーポリシー', $html);
        $this->assertStringContainsString('id="idea-privacy-title"', $html);
    }

    public function testAtLeastOneArticleHeadingIsRendered(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        // When staticContent.sections is empty the template renders the
        // built-in BeMart privacy content; the definition section must appear.
        $this->assertStringContainsString('個人情報の定義', $html);
    }

    public function testIdeaStoreClassesAreUsedInBody(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        foreach ([
            'idea-container',
            'idea-section',
            'idea-checkout-panel',
            'idea-eyebrow',
        ] as $class) {
            $this->assertStringContainsString($class, $html, "Missing IdeaStore class: {$class}");
        }
    }

    // -------------------------------------------------------------------------
    // L2 — link href / rel
    // -------------------------------------------------------------------------

    public function testGoTopLinkHasCorrectHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        $this->assertStringContainsString('href="/" rel="goTop"', $html);
    }

    public function testBreadcrumbContainsHomeLink(): void
    {
        $html = $this->resource->get('page://self/help/privacy')->toString();

        $this->assertStringContainsString('idea-breadcrumb', $html);
        $this->assertStringContainsString('<a href="/">Home</a>', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity — archived
    // -------------------------------------------------------------------------

    /**
     * EC-CUBE structural/class parity assertions have been retired.
     * The template is a clean-room IdeaStore rebuild; ec-* classes and
     * EC-CUBE prose are intentionally absent.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE class/prose parity retired: template is a clean-room '
            . 'IdeaStore rebuild. ec-role / ec-pageHeader / ec-off1Grid / '
            . 'EC-CUBE default privacy text are no longer expected in the rendered output.'
        );
    }

    /**
     * EC-CUBE reference render diff test has been retired.
     * The template no longer targets zero-diff against EC-CUBE rendering.
     *
     * @group ec-cube-parity-archived
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testHelpPrivacyHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE render-diff test retired: Privacy template is a '
            . 'clean-room IdeaStore page. See testEcCubeMarkupParityArchived.'
        );
    }
}
