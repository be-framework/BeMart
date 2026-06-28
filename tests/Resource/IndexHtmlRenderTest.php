<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * Top page (goTop / page://self/) — IdeaStore HTML render verification.
 *
 * L1 — required structural fields that the resource contract guarantees.
 * L2 — hypermedia: link hrefs and rel values declared by #[Link] in
 *       src/Resource/Page/Index.php.
 *
 * EC-CUBE parity tests (comparing rendered output line-by-line against
 * EC-CUBE 4.3's index.twig) are archived below; they are no longer the
 * fidelity target now that the template is a clean-room IdeaStore build.
 */
final class IndexHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — structural / document contract
    // -------------------------------------------------------------------------

    public function testTopPageReturns200(): void
    {
        $ro = $this->resource->get('page://self/');

        $this->assertSame(Code::OK, $ro->code);
    }

    public function testTopPageRendersHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/');
        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTopPageRendersIdeaStoreBaseLayout(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        // IdeaStore base layout markers
        $this->assertStringContainsString('class="idea-store"', $html, 'base layout body class');
        $this->assertStringContainsString('class="idea-header"', $html, 'site header');
        $this->assertStringContainsString('class="idea-footer"', $html, 'site footer');
        $this->assertStringContainsString('<main>', $html, 'main landmark');
    }

    public function testTopPageTitleContainsIdeaStore(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('IDEA STORE', $html, '<title> must include IDEA STORE brand');
    }

    public function testTopPageContainsHero(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('idea-commerce-hero', $html, 'hero section');
        // h1 heading present inside content
        $this->assertMatchesRegularExpression('/<h1[^>]*>/', $html, 'h1 heading');
    }

    public function testTopPageContainsCategorySection(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        // Category grid must be present
        $this->assertStringContainsString('idea-category-card', $html, 'category card entries');
        // At least one canonical category
        $this->assertStringContainsString('収納', $html, 'sample category: 収納');
    }

    // -------------------------------------------------------------------------
    // L2 — hypermedia: hrefs and rel values from #[Link] in Index.php
    // -------------------------------------------------------------------------

    public function testGoProductListLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/products"', $html, 'goProductList href');
        $this->assertStringContainsString('rel="goProductList"', $html, 'goProductList rel');
    }

    public function testGoCartLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/cart"', $html, 'goCart href');
        $this->assertStringContainsString('rel="goCart"', $html, 'goCart rel');
    }

    public function testGoLoginLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/login"', $html, 'goLogin href');
        $this->assertStringContainsString('rel="goLogin"', $html, 'goLogin rel');
    }

    public function testGoCustomerRegistrationLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/entry"', $html, 'goCustomerRegistration href');
        $this->assertStringContainsString('rel="goCustomerRegistration"', $html, 'goCustomerRegistration rel');
    }

    public function testGoMypageLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/mypage"', $html, 'goMypage href');
        $this->assertStringContainsString('rel="goMypage"', $html, 'goMypage rel');
    }

    public function testGoContactFormLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/contact"', $html, 'goContactForm href');
        $this->assertStringContainsString('rel="goContactForm"', $html, 'goContactForm rel');
    }

    public function testGoHelpGuideLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/help/guide"', $html, 'goHelpGuide href');
        $this->assertStringContainsString('rel="goHelpGuide"', $html, 'goHelpGuide rel');
    }

    public function testGoHelpTradeLawLink(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/help/trade-law"', $html, 'goHelpTradeLaw href');
        $this->assertStringContainsString('rel="goHelpTradeLaw"', $html, 'goHelpTradeLaw rel');
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity tests — archived
    //
    // These tests compared BeMart's rendered output line-by-line against
    // EC-CUBE 4.3's index.twig using a standalone Twig environment with
    // EC-CUBE's runtime API stubbed.  They are no longer meaningful now
    // that the template is a clean-room IdeaStore build (idea-* classes,
    // no ec-* DOM, no slick slider, no EC-CUBE layout blocks).
    // -------------------------------------------------------------------------

    #[Group('ec-cube-parity-archived')]
    public function testTopHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity test archived: template rebuilt as clean-room IdeaStore design. '
            . 'Functional coverage is provided by L1/L2 tests in this class.',
        );
    }

    #[Group('ec-cube-parity-archived')]
    public function testTopPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure test archived: ec-sliderRole / slick-slide / ec-* classes '
            . 'removed in IdeaStore rebuild. Use testTopPageContainsHero() instead.',
        );
    }

    #[Group('ec-cube-parity-archived')]
    public function testTopPageRendersAsHtmlDocument(): void
    {
        $this->markTestSkipped(
            'Superseded by testTopPageRendersHtmlDocument() and testTopPageReturns200().',
        );
    }

    #[Group('ec-cube-parity-archived')]
    public function testTopPageRendersCriticalNavigationLinks(): void
    {
        $this->markTestSkipped(
            'Superseded by L2 link tests (testGoProductListLink, testGoCartLink).',
        );
    }
}
