<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * Phase 3 — functional/semantic render check for the Entry/Activate landing page
 * (email-verification-complete screen, ALPS goCustomerActivationComplete).
 *
 * The template has been rebuilt as an IdeaStore clean-room design; EC-CUBE
 * markup parity is no longer a goal for this page.
 *
 * L1 — required fields and data output:
 *   - The page must render as a valid HTML document
 *   - The page title must identify the page
 *   - A heading conveying completion must appear in the body
 *   - The IdeaStore layout (idea-* classes) must be present
 *
 * L2 — transitions (rel / href):
 *   - The goTop link (href="/") must be present and reachable
 *   - No form submissions appear (data page — no form)
 *
 * EC-CUBE markup parity test is archived below; it requires the EC-CUBE
 * 4.3 reference clone and is skipped in CI.
 */
final class EntryActivateHtmlRenderTest extends TestCase
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
        $ro = $this->resource->get('page://self/entry/activate');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testPageTitleIdentifiesPage(): void
    {
        $html = $this->resource->get('page://self/entry/activate')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
        // Title block must describe the page function
        $this->assertMatchesRegularExpression('/<title>[^<]*(?:認証|登録|完了)[^<]*IDEA STORE[^<]*<\/title>/', $html);
    }

    public function testCompletionHeadingAppearsInBody(): void
    {
        $html = $this->resource->get('page://self/entry/activate')->toString();

        // An h1 or h2 must signal successful registration / verification
        $this->assertMatchesRegularExpression('/<h[12][^>]*>[^<]*(?:完了|完成|メール認証|会員登録)[^<]*<\/h[12]>/', $html);
    }

    public function testIdeaStoreLayoutPresent(): void
    {
        $html = $this->resource->get('page://self/entry/activate')->toString();

        $this->assertStringContainsString('class="idea-store"', $html);
        // At least one idea-* class must appear inside <main>
        $this->assertMatchesRegularExpression('/<main>.*idea-[a-z].*<\/main>/s', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — transitions: rel / href
    // -------------------------------------------------------------------------

    public function testGoTopLinkPresent(): void
    {
        $html = $this->resource->get('page://self/entry/activate')->toString();

        // The goTop transition (ALPS #Link rel=goTop href=page://self/) must
        // appear as an anchor to "/" in the rendered HTML.
        $this->assertMatchesRegularExpression('/<a\s[^>]*href=["\']\/["\'][^>]*>/', $html);
    }

    public function testNoFormOnDataPage(): void
    {
        $html = $this->resource->get('page://self/entry/activate')->toString();

        // This is a pure data page — no POST form submission should be present.
        // (The IdeaStore layout header includes a GET search form, which is expected.)
        $this->assertStringNotContainsString('method="post"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity (archived)
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testEntryActivateHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: the Entry/Activate page has been '
            . 'rebuilt as a clean-room IdeaStore design. '
            . 'Functional/semantic coverage is provided by the L1/L2 tests above.',
        );
    }
}
