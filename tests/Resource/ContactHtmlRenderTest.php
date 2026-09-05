<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3 — semantic render verification for the Contact (goContactForm) page.
 *
 * The template is authored in IdeaStore design language (idea-* classes).
 * EC-CUBE parity assertions are archived below — they are no longer
 * meaningful once the template is rebuilt from resource contracts rather
 * than ported from EC-CUBE's default-theme Twig.
 *
 * L1 — required fields and data output.
 * L2 — form action / method and link href / rel.
 */
final class ContactHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — document structure and required field rendering
    // -------------------------------------------------------------------------

    public function testContactPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/contact');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testContactPageHasCorrectTitle(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        $this->assertStringContainsString('<title>お問い合わせ | IDEA STORE</title>', $html);
    }

    public function testContactPageRendersAllRequiredFields(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // L1: all four modelled fields must appear
        $this->assertStringContainsString('name="contactName01"', $html, 'contactName01 input missing');
        $this->assertStringContainsString('name="contactName02"', $html, 'contactName02 input missing');
        $this->assertStringContainsString('name="contactEmail"', $html, 'contactEmail input missing');
        $this->assertStringContainsString('name="contactContents"', $html, 'contactContents input missing');

        // L1: textarea for message content
        $this->assertStringContainsString('<textarea', $html, 'textarea element missing');
    }

    public function testContactPageRendersNamePlaceholders(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // ContactForm declares placeholder="姓" for contactName01
        $this->assertStringContainsString('placeholder="姓"', $html);
    }

    public function testContactPageContainsCsrfHiddenInput(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // CSRF token field must be present for POST protection
        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — form action / method and transition contract
    // -------------------------------------------------------------------------

    public function testContactFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // L2: form action must point to /contact (resource submitTo.href)
        $this->assertStringContainsString('action="/contact"', $html, 'form action must be /contact');

        // L2: HTTP method must be POST (submitTo.method = POST)
        $this->assertStringContainsString('method="post"', $html, 'form method must be post');
    }

    public function testContactFormCarriesModeFieldForBrowserPath(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // The resource's onPost uses $mode !== null to trigger the browser
        // validation path. The submit button must carry name="mode".
        $this->assertStringContainsString('name="mode"', $html, 'mode field missing from submit button');
        $this->assertStringContainsString('value="confirm"', $html, 'mode value must be confirm');
    }

    public function testContactPageUsesIdeaStoreLayout(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        // IdeaStore base layout markers
        $this->assertStringContainsString('idea-store.css', $html);
        $this->assertStringContainsString('class="idea-store"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity assertions — archived
    //
    // These tests compared BeMart's rendered HTML against EC-CUBE 4.3's
    // Contact/index.twig output line-by-line. They are no longer applicable
    // because the template has been rebuilt in IdeaStore design language
    // (idea-* classes) from resource contracts, not from EC-CUBE's DOM.
    //
    // The archived suite relied on an EC-CUBE 4.3 reference clone at
    // tools/ec-cube-source/ and Twig stubs that bridged EC-CUBE's FormView
    // API to BeMart's ContactForm. That infrastructure remains in the
    // codebase for other pages still under parity review.
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testContactHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check archived: Contact template rebuilt in IdeaStore '
            . 'design language from resource contracts (idea-* classes). '
            . 'Functional coverage is provided by L1/L2 tests above.'
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testContactPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure assertions archived: template no longer '
            . 'uses ec-* classes. See L1/L2 tests above for current coverage.'
        );
    }
}
