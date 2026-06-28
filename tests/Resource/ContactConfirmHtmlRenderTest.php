<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3 — functional and semantic verification for the Contact confirm
 * (goContactConfirm) HTML page.
 *
 * The confirm screen re-shows the submitted inquiry as plain text and
 * carries all four modelled fields forward as HIDDEN inputs so the final
 * "送信する" submit re-posts the inquiry to `doSubmitContact`
 * (page://self/contact via POST /contact).
 *
 * L1 — required fields present in the rendered HTML.
 * L2 — form action / method and link href / rel semantics.
 *
 * The EC-CUBE rendering parity test is retired to the
 * `ec-cube-parity-archived` group now that the template is rebuilt in
 * IdeaStore design language.
 */
final class ContactConfirmHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /**
     * L1 — the page renders as a complete HTML document using the
     * IdeaStore layout.
     */
    public function testContactConfirmPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/contact/confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('idea-store.css', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1 — the page contains a meaningful title and heading for the
     * confirm step.
     */
    public function testContactConfirmPageHasTitleAndHeading(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
        $this->assertStringContainsString('<h1', $html);
        // The heading must communicate the confirm/review step.
        $this->assertMatchesRegularExpression(
            '/確認|confirm/i',
            $html,
            'Page heading must indicate this is the inquiry confirm step',
        );
    }

    /**
     * L1 — the confirm screen displays the three modelled inquiry
     * data labels so the customer can review before sending.
     */
    public function testContactConfirmPageDisplaysInquiryDataLabels(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        foreach ([
            'お名前',
            'メールアドレス',
            'お問い合わせ内容',
        ] as $label) {
            $this->assertStringContainsString($label, $html, "confirm screen must display label: {$label}");
        }
    }

    /**
     * L1 / L2 — the inquiry payload is carried forward as real hidden
     * inputs rendered by the form library, not static markup.
     */
    public function testContactConfirmPageRendersHiddenFormCarriers(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        $this->assertStringContainsString('<input type="hidden" name="contactName01"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactName02"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactEmail"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactContents"', $html);
    }

    /**
     * L2 — the form submits to the doSubmitContact endpoint:
     * POST /contact.
     */
    public function testContactConfirmFormTargetsDoSubmitContact(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/contact"', $html);
    }

    /**
     * L2 — the page provides both a submit action and a back action so
     * the customer can correct the inquiry before sending.
     */
    public function testContactConfirmPageHasSubmitAndBackActions(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        $this->assertStringContainsString('送信する', $html, 'confirm page must have a submit action');
        $this->assertStringContainsString('戻る', $html, 'confirm page must have a back action');
    }

    /**
     * EC-CUBE rendering parity — retired.
     *
     * The template is now rebuilt in IdeaStore design language (idea-*
     * classes); EC-CUBE structural parity is no longer the target.
     * Archived here so the test infrastructure is preserved if needed.
     *
     * @group ec-cube-parity-archived
     */
    public function testContactConfirmHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering parity retired: template rebuilt in IdeaStore design language (idea-* classes).',
        );
    }
}
