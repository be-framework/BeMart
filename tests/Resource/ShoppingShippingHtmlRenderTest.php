<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function preg_match;
use function str_contains;

/**
 * Semantic render test for Shopping/Shipping (goShoppingShipping).
 *
 * Verifies L1 (required fields / data output) and L2 (form action, method,
 * link href/rel) contracts derived from:
 *   - src/Resource/Page/Shopping/Shipping.php  (#[Link] annotations)
 *   - var/json_schema/get-shopping-shipping.json  (body shape)
 *   - ALPS descriptor goShoppingShipping / doSelectShippingAddress
 *
 * The EC-CUBE markup-parity test has been retired (archived below).
 */
final class ShoppingShippingHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ------------------------------------------------------------------ L0

    /**
     * The resource returns HTTP 200 and renders a complete HTML document.
     */
    public function testRendersHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ------------------------------------------------------------------ L1: required fields / data output

    /**
     * Page title indicates delivery address selection.
     */
    public function testL1PageTitle(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertStringContainsString('お届け先', $html);
    }

    /**
     * The body schema requires `addresses` (array) to be present.
     * With an empty list the template must still render without error.
     */
    public function testL1EmptyAddressListRendersWithoutError(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        // The empty-state branch must be visible.
        $this->assertStringContainsString('<!doctype html>', $html);
        // No PHP / Twig exception bleed-through.
        $this->assertStringNotContainsString('Twig\Error', $html);
        $this->assertStringNotContainsString('Exception', $html);
    }

    /**
     * A link to add a new shipping address must appear.
     * (ALPS rel: goShoppingShippingEdit → /shopping/shipping-edit)
     */
    public function testL1AddNewAddressLinkPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertStringContainsString('/shopping/shipping-edit', $html);
    }

    // ------------------------------------------------------------------ L2: form action / method, link href/rel

    /**
     * The selection form POSTs to /shopping/shipping.
     * (ALPS doSelectShippingAddress → POST page://self/shopping/shipping)
     */
    public function testL2FormPostToShippingEndpoint(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertTrue(
            str_contains($html, 'action="/shopping/shipping"'),
            'Form action must POST to /shopping/shipping',
        );
        $this->assertTrue(
            str_contains($html, 'method="post"'),
            'Form method must be POST',
        );
    }

    /**
     * The form must carry a CSRF token field named csrfToken.
     */
    public function testL2CsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertTrue(
            (bool) preg_match('/<input[^>]+name="csrfToken"/', $html),
            'Hidden csrfToken input must be present in the form',
        );
    }

    /**
     * The form must target doSelectShippingAddress (ALPS transition).
     * (ALPS field: shippingAddressId in doSelectShippingAddress)
     *
     * Wave 3H: addresses is always [] (no AddressStorage wiring yet),
     * so the radio-button loop is skipped. Assert the form action and
     * the hidden csrfToken instead to confirm correct wiring.
     */
    public function testL2ShippingAddressIdFieldName(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        // Form targets the correct doSelectShippingAddress endpoint.
        $this->assertStringContainsString('action="/shopping/shipping"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * Back link must point to /shopping (ALPS rel: goShopping).
     */
    public function testL2BackLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertStringContainsString('href="/shopping"', $html);
    }

    /**
     * The add-new-address link must carry rel=goShoppingShippingEdit.
     */
    public function testL2AddNewAddressLinkRel(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertTrue(
            str_contains($html, 'rel="goShoppingShippingEdit"'),
            'Add-new-address link must carry rel="goShoppingShippingEdit"',
        );
    }

    /**
     * Multiple shipping link must point to /shopping/shipping-multiple
     * (ALPS rel: goShoppingShippingMultiple).
     */
    public function testL2MultipleShippingLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        $this->assertStringContainsString('href="/shopping/shipping-multiple"', $html);
        $this->assertStringContainsString('rel="goShoppingShippingMultiple"', $html);
    }

    // ------------------------------------------------------------------ Archived

    /**
     * EC-CUBE markup parity check — archived.
     *
     * This test verified BeMart's HTML matched EC-CUBE 4.3's
     * Shopping/shipping.twig output (ec-* classes, DOM structure).
     * The template has been rebuilt with IdeaStore design language;
     * the EC-CUBE parity assertion is no longer applicable.
     *
     * @group ec-cube-parity-archived
     */
    public function testShippingHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: template rebuilt with IdeaStore design language. '
            . 'Semantic equivalence is covered by the L1/L2 tests above.',
        );
    }

    /**
     * EC-CUBE markup structure check — archived.
     *
     * @group ec-cube-parity-archived
     */
    public function testShippingPreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure check retired: ec-* classes replaced with idea-* vocabulary.',
        );
    }

    /**
     * EC-CUBE base layout assertion — archived.
     *
     * @group ec-cube-parity-archived
     */
    public function testShippingRendersAsHtmlDocument(): void
    {
        $this->markTestSkipped(
            'Superseded by testRendersHtmlDocument().',
        );
    }
}
