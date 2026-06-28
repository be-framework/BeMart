<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * HTML render test for Shopping/ShippingEdit (goShoppingShippingEdit).
 *
 * L1 — required fields and data output present in rendered HTML.
 * L2 — form action/method contract and navigation link href/rel.
 *
 * The EC-CUBE DOM parity test is archived below (@group ec-cube-parity-archived).
 */
final class ShoppingShippingEditHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0: document frame ───────────────────────────────────────────────

    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: required fields and semantic content ─────────────────────────

    /**
     * All 10 address input fields declared in ShoppingShippingEditForm must
     * appear in the rendered HTML as real <input>/<select> elements produced
     * by the form library.
     */
    public function testAllAddressInputFieldsArePresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        foreach ([
            'name="name01"',
            'name="name02"',
            'name="kana01"',
            'name="kana02"',
            'name="companyName"',
            'name="postalCode"',
            'name="pref"',
            'name="addr01"',
            'name="addr02"',
            'name="phoneNumber"',
        ] as $field) {
            $this->assertStringContainsString($field, $html, "Required form field missing: {$field}");
        }
    }

    /**
     * The page must carry a meaningful Japanese heading that identifies it as
     * a delivery-address editing screen (semantic identity, not specific wording).
     */
    public function testPageHasDeliveryAddressHeading(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        // <h1> must be present and contain a delivery-address concept
        $this->assertMatchesRegularExpression(
            '/<h1[^>]*>[^<]*お届け先[^<]*<\/h1>/u',
            $html,
            'Page must have an <h1> identifying it as a delivery-address screen',
        );
    }

    /**
     * A CSRF hidden field must be present so the POST submission is protected.
     * Field name is csrfToken — confirmed by ShippingEdit resource body contract.
     */
    public function testCsrfHiddenFieldIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'CSRF hidden field name="csrfToken" must be present');
        $this->assertStringContainsString('type="hidden"', $html, 'CSRF hidden input type="hidden" must be present');
    }

    /**
     * IdeaStore design language: the page must use idea-* CSS classes for its
     * structure. No ec-* classes may appear (clean-room requirement).
     */
    public function testPageUsesIdeaStoreClassesAndNoEcCubeClasses(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('class="idea-', $html, 'Page must use idea-* design language classes');
        $this->assertDoesNotMatchRegularExpression('/class="ec-/', $html, 'Page must not contain any ec-* classes (clean-room)');
    }

    // ── L2: form action/method and navigation links ──────────────────────

    /**
     * The form must POST to /shopping/shipping-edit as declared by
     * ShippingEdit::onGet() #[Link rel='doUpdateShippingAddress'].
     */
    public function testFormActionAndMethodMatchResourceContract(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('method="post"', $html, 'Form method must be POST');
        $this->assertStringContainsString('action="/shopping/shipping-edit"', $html, 'Form action must be /shopping/shipping-edit');
    }

    /**
     * A back-navigation link to /shopping/shipping must be present, corresponding
     * to the #[Link rel='goShoppingShipping'] on the resource.
     */
    public function testBackLinkToShippingSelectionIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('href="/shopping/shipping"', $html, 'Back link to /shopping/shipping must be present');
    }

    /**
     * A submit button must be present so the user can save the address.
     */
    public function testSubmitButtonIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('type="submit"', $html, 'Submit button must be present');
    }

    // ── EC-CUBE DOM parity (archived) ────────────────────────────────────

    /**
     * EC-CUBE parity check archived: the template is now a clean-room
     * IdeaStore-design-language rebuild and intentionally diverges from
     * the EC-CUBE DOM structure.
     *
     * @group ec-cube-parity-archived
     */
    public function testShippingEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM parity retired: ShippingEdit.html.twig is now a clean-room '
            . 'IdeaStore rebuild. Functional equivalence is verified by the L1/L2 tests above.',
        );
    }
}
