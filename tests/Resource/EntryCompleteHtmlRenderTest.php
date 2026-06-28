<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * Functional/semantic render test for the Entry complete screen
 * (ALPS: goCustomerRegistrationComplete).
 *
 * This is a static data page (no form). The Complete resource
 * (src/Resource/Page/Entry/Complete.php) is a thin pure renderer that
 * carries transitionId + staticContent + the outbound goTop link.
 *
 * L1 — required fields / data output
 * L2 — link href / rel
 *
 * EC-CUBE parity assertions have been archived below under
 * @group ec-cube-parity-archived.
 */
final class EntryCompleteHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0: document structure ────────────────────────────────────────────

    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/entry/complete');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // ── L1: required fields / data output ────────────────────────────────

    public function testPageTitleIsPresent(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testHeadingDescribesCompletionState(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        // The page must have a visible h1 conveying registration receipt.
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*登録.*<\/h1>/s', $html);
    }

    public function testConfirmationMessageIsPresent(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        // Users must know a confirmation email was sent.
        $this->assertTrue(
            str_contains($html, 'メール') && str_contains($html, '確認'),
            'Confirmation email instruction must be visible on the page',
        );
    }

    public function testNoEcCubeClassNamesInOutput(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        foreach (['ec-role', 'ec-pageHeader', 'ec-registerCompleteRole', 'ec-reportHeading',
            'ec-off3Grid', 'ec-reportDescription', 'ec-off4Grid', 'ec-blockBtn'] as $class) {
            $this->assertStringNotContainsString($class, $html, "EC-CUBE class must not appear: {$class}");
        }
    }

    // ── L2: link href / rel ───────────────────────────────────────────────

    public function testGoTopLinkIsPresentWithCorrectHref(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        // Resource declares #[Link(rel: 'goTop', href: 'page://self/')] → renders as /
        $this->assertMatchesRegularExpression('/<a[^>]+href=["\']\/["\'][^>]*>/', $html);
    }

    public function testGoTopLinkCarriesRelAttribute(): void
    {
        $html = $this->resource->get('page://self/entry/complete')->toString();

        $this->assertStringContainsString('rel="goTop"', $html);
    }

    // ── EC-CUBE parity (archived) ─────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEntryCompletePageRendersAsHtmlDocumentLegacy(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: template rebuilt in IdeaStore design language. '
            . 'Functional coverage provided by testRendersAsHtmlDocument().',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testEntryCompletePagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: IdeaStore design language uses idea-* classes. '
            . 'Structural assertions replaced by L1/L2 semantic tests above.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testEntryCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE diff comparison archived: template is an IdeaStore clean-room rebuild. '
            . 'Use functional/semantic tests (L1/L2) for ongoing verification.',
        );
    }
}
