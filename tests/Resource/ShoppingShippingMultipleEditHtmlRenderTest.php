<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * HTML render verification for the Shopping shipping-multiple-edit page
 * (goShoppingShippingMultipleEdit / ALPS doAddMultipleShippingAddress).
 *
 * This is the "新規お届け先を追加する" form linked from the multi-destination
 * shipping screen. The resource (src/Resource/Page/Shopping/ShippingMultipleEdit.php)
 * exposes a ShoppingShippingEditForm as body.form; on POST it redirects to
 * page://self/shopping/shipping-multiple.
 *
 * Test levels:
 *  L1 — required fields present in rendered HTML (semantic / data output)
 *  L2 — form action/method and navigation link href/rel (hypermedia contract)
 *
 * EC-CUBE parity tests are archived in the @group ec-cube-parity-archived
 * group and skipped; they verify structural identity with the EC-CUBE 4.3
 * default-theme template which this page no longer mirrors.
 */
final class ShoppingShippingMultipleEditHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L0 — document envelope
    // -------------------------------------------------------------------------

    public function testShippingMultipleEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-multiple-edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // -------------------------------------------------------------------------
    // L1 — required fields / semantic data output
    // -------------------------------------------------------------------------

    /** All address form fields must be present as real <input> elements. */
    public function testShippingMultipleEditRendersAllRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple-edit')->toString();

        foreach (['name01', 'name02', 'kana01', 'kana02', 'companyName', 'postalCode', 'pref', 'addr01', 'addr02', 'phoneNumber'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "Form field missing: {$field}");
        }
    }

    /** The page heading must identify the purpose of the page. */
    public function testShippingMultipleEditRendersPageHeading(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple-edit')->toString();

        $this->assertStringContainsString('お届け先の追加', $html);
    }

    /** The CSRF hidden field must be present for POST protection. */
    public function testShippingMultipleEditRendersCsrfHiddenField(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple-edit')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — hypermedia contract (form action/method, link href)
    // -------------------------------------------------------------------------

    /**
     * The form must POST to the resource URI that handles doAddMultipleShippingAddress.
     * Source: #[Link(rel: 'doAddMultipleShippingAddress', href: 'page://self/shopping/shipping-multiple-edit', method: 'post')]
     */
    public function testShippingMultipleEditFormPostsToCorrectEndpoint(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple-edit')->toString();

        $this->assertStringContainsString('action="/shopping/shipping-multiple-edit"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * The back-navigation link must point to the multi-destination shipping page.
     * Source: #[Link(rel: 'goShoppingShippingMultiple', href: 'page://self/shopping/shipping-multiple')]
     */
    public function testShippingMultipleEditHasBackLinkToMultipleShipping(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple-edit')->toString();

        $this->assertStringContainsString('href="/shopping/shipping-multiple"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity tests — archived
    //
    // These tests verified structural identity with the EC-CUBE 4.3
    // default-theme template. The template has been rewritten in the IdeaStore
    // design language; EC-CUBE markup classes (ec-registerRole, ec-borderedDefs,
    // ec-halfInput, etc.) are no longer present.
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testShippingMultipleEditPreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: template rewritten in IdeaStore design language. '
            . 'ec-* classes replaced by idea-* classes.',
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testShippingMultipleEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: template rewritten in IdeaStore design language. '
            . 'Structural diff against EC-CUBE default-theme is no longer meaningful.',
        );
    }
}
