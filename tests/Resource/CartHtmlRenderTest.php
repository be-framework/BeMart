<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 Step 1 — HTML-layer hypermedia test for the goCart pilot page.
 *
 * The JSON-context tests ({@see CartResourceTest}) assert on `$ro->body`
 * arrays. This is the presentation-layer analogue: it drives the same
 * Cart resource through the `html` context (HtmlModule -> TwigModule ->
 * TwigRenderer) and asserts that `$ro->toString()` produces a real HTML
 * document whose markup actually carries the resource body's data.
 *
 * Proving render correctness end to end:
 *   - the response is a full HTML document (DOCTYPE + structural tags);
 *   - body scalars (cartCount, totalPrice) appear in the rendered markup;
 *   - the carts loop renders one <section> per cart with its saleTypeName;
 *   - the nested items loop renders real product data after a cart item is
 *     added (the Fake cart fixture starts item-empty, so an item is POSTed
 *     first — FakeCartStorage is a request-scoped singleton, so the GET
 *     sees it).
 */
final class CartHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testCartPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        // A real HTML document, not a JSON blob.
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<title>カート | BeMart</title>', $html);
        $this->assertStringContainsString('</body>', $html);

        // The Twig renderer set an HTML content type on the resource.
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testCartPageHtmlCarriesBodyData(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $html = $ro->toString();

        // Body scalars surface in the markup.
        $this->assertSame(2, $ro->body['cartCount']);
        $this->assertStringContainsString('<span class="cart-count">2</span>', $html);

        // The carts[] loop renders one <section> per cart, keyed + labelled
        // from the body (saleTypeName / cartKey).
        $this->assertStringContainsString('data-cart-key="session-prefix-1_1"', $html);
        $this->assertStringContainsString('通常販売', $html);
        $this->assertStringContainsString('data-cart-key="session-prefix-1_2"', $html);
        $this->assertStringContainsString('予約販売', $html);
    }

    public function testCartPageHtmlRendersItemRowsForPopulatedCart(): void
    {
        // The Fake cart fixture has no items, so the nested items loop would
        // never execute on a bare GET. Add a real item first (same injector
        // -> same singleton FakeCartStorage), then render.
        $post = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $post->code);

        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);
        $html = $ro->toString();

        // The nested items loop rendered a real row from the cart body.
        $this->assertStringContainsString('<table class="cart-items">', $html);
        $this->assertStringContainsString('<td class="item-code">sample-001</td>', $html);
        $this->assertStringContainsString('<td class="item-quantity">3</td>', $html);
        // unitPrice 1200 * quantity 3 -> subtotal computed in-template.
        $this->assertStringContainsString('<td class="item-subtotal">3600</td>', $html);
    }
}
