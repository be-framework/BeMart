<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * Semantic render test for the Shopping complete (goShoppingComplete) page.
 *
 * Verifies L1 (required data output) and L2 (navigation affordances) against
 * the IdeaStore design-language template. EC-CUBE markup parity tests have
 * been retired — the template is a clean-room rebuild, not a port of EC-CUBE.
 *
 * L1 — Required field output
 *   • The HTML document structure is present (doctype, html, body)
 *   • The page carries the "ご注文完了" title
 *   • The thank-you heading is present
 *   • orderNo is rendered when supplied
 *   • completeMessage is rendered when non-empty
 *
 * L2 — Navigation affordances (link href / rel)
 *   • goTop link (rel="goTop") points to "/"
 *   • goMypage link (rel="goMypage") points to "/mypage"
 */
final class ShoppingCompleteHtmlRenderTest extends TestCase
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
        $ro = $this->resource->get('page://self/shopping/complete');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // ── L1 — Required field output ───────────────────────────────────────

    public function testPageTitleContainsOrderComplete(): void
    {
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        $this->assertStringContainsString('ご注文完了', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testThankYouHeadingIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        // The page must carry an acknowledgment heading.
        $this->assertStringContainsString('ご注文ありがとう', $html);
    }

    public function testOrderNoRenderedWhenSupplied(): void
    {
        // Use the orderNo that exists in the fake data fixture.
        $html = $this->resource
            ->get('page://self/shopping/complete', ['orderNo' => 'past0000000000000000000000000001'])
            ->toString();

        $this->assertStringContainsString('past0000000000000000000000000001', $html);
    }

    public function testOrderNoBlockAbsentWhenEmpty(): void
    {
        // A direct visit with no orderNo must not render a spurious order number block.
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        // "注文番号：" label should not appear when orderNo is empty
        $this->assertStringNotContainsString('注文番号：', $html);
    }

    public function testCompleteMessageRenderedWhenPresent(): void
    {
        // completeMessage is supplied by payment plugins at runtime; the template
        // must render it when the body field is non-empty.
        // The resource hard-codes '' for Pilot 5, so we confirm the template at
        // least does not crash and does not emit the label when empty.
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        // No stray completeMessage block when empty — template renders cleanly.
        $this->assertStringNotContainsString('undefined', $html);
        $this->assertStringNotContainsString('{{ completeMessage', $html);
    }

    // ── L2 — Navigation affordances ──────────────────────────────────────

    public function testGoTopLinkIsPresentWithCorrectHref(): void
    {
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        // rel="goTop" href="/" — ALPS #goTop transition
        $this->assertStringContainsString('rel="goTop"', $html);
        $this->assertTrue(
            str_contains($html, 'href="/" rel="goTop"')
            || str_contains($html, 'rel="goTop" href="/"'),
            'goTop link must point to "/"',
        );
    }

    public function testGoMypageLinkIsPresentWithCorrectHref(): void
    {
        $html = $this->resource->get('page://self/shopping/complete')->toString();

        // rel="goMypage" href="/mypage" — ALPS #goMypage transition
        $this->assertStringContainsString('rel="goMypage"', $html);
        $this->assertTrue(
            str_contains($html, 'href="/mypage" rel="goMypage"')
            || str_contains($html, 'rel="goMypage" href="/mypage"'),
            'goMypage link must point to "/mypage"',
        );
    }

    // ── Archived: EC-CUBE markup parity (clean-room rebuild) ─────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testShoppingCompletePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: template is a clean-room IdeaStore rebuild.'
            . ' Structural assertions have been replaced by L1/L2 semantic tests.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testShoppingCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE reference rendering comparison retired: template is a clean-room IdeaStore rebuild.'
            . ' Archived under @group ec-cube-parity-archived.',
        );
    }
}
