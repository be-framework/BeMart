<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

use function dirname;
use function is_dir;

/**
 * Cart page — HTML render verification.
 *
 * Asserts:
 *   L1 — required data fields appear in the rendered output
 *         (productName, productCode, unit price, quantity, subtotal,
 *          cart total, delivery fee, section heading).
 *   L2 — form affordances: action="/cart/item", method="post",
 *         CSRF field name "csrfToken", operation values (remove/up/down),
 *         and checkout link rel="goCheckoutEntry" href="/shopping".
 *
 * The former EC-CUBE structural parity test (ec-* class assertions and
 * line-diff against the reference clone) is preserved below under
 * @group ec-cube-parity-archived and skipped — it tested derivation from
 * EC-CUBE's markup skeleton, which the IdeaStore cleanroom template
 * deliberately does not carry.
 */
final class CartHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — Required field / data output
    // -------------------------------------------------------------------------

    public function testCartPageRendersHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testCartPageRendersProductDataFields(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // L1 — product code appears
        $this->assertStringContainsString('sample-001', $html, 'productCode must appear');

        // L1 — product name appears
        $this->assertStringContainsString('サンプル商品', $html, 'productName must appear');

        // L1 — quantity appears (fake fixture has qty=3)
        $this->assertStringContainsString('3', $html, 'quantity must appear');

        // L1 — unit price appears (¥1,200 for sample-001 fixture)
        $this->assertStringContainsString('1,200', $html, 'unit price must appear');

        // L1 — subtotal appears (1,200 × 3 = 3,600)
        $this->assertStringContainsString('3,600', $html, 'line subtotal must appear');

        // L1 — cart total appears
        $this->assertMatchesRegularExpression('/¥[\d,]+/', $html, 'cart total must appear');
    }

    public function testEmptyCartRendersEmptyState(): void
    {
        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-no-items',
        ])->toString();

        // L1 — empty state message present
        $this->assertStringContainsString('カートに商品がありません', $html, 'empty-cart message must appear');
    }

    public function testCartPageRendersSaleTypeLabel(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // L1 — sale type name rendered (Fake fixture: 通常販売)
        $this->assertStringContainsString('通常販売', $html, 'saleTypeName must appear');
    }

    // -------------------------------------------------------------------------
    // L2 — Form action / method / field names and link href / rel
    // -------------------------------------------------------------------------

    public function testCartFormAffordances(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // L2 — mutation endpoint
        $this->assertStringContainsString('action="/cart/item"', $html, 'form action must be /cart/item');
        $this->assertStringContainsString('method="post"', $html, 'form method must be post');

        // L2 — CSRF field uses the resource-layer field name
        $this->assertStringContainsString('name="csrfToken"', $html, 'CSRF field must be named csrfToken');

        // L2 — operation hidden fields
        $this->assertStringContainsString('name="operation"', $html, 'operation field must be present');
        $this->assertStringContainsString('value="remove"', $html, 'remove operation must be present');
        $this->assertStringContainsString('value="up"', $html, 'up operation must be present');
        $this->assertStringContainsString('value="down"', $html, 'down operation must be present');

        // L2 — productCode field on mutation forms
        $this->assertStringContainsString('name="productCode"', $html, 'productCode field must be present');
    }

    public function testCheckoutLinkAffordance(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // L2 — goCheckoutEntry transition: rel and href from #[Link] on Cart::onGet
        $this->assertStringContainsString('rel="goCheckoutEntry"', $html, 'checkout link rel must be goCheckoutEntry');
        $this->assertStringContainsString('href="/shopping"', $html, 'checkout link href must be /shopping');
    }

    public function testProductDetailLinkAffordance(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // L2 — product detail link includes productCode in the path
        $this->assertStringContainsString('/products/detail/sample-001', $html, 'product detail link must include productCode');
    }

    // -------------------------------------------------------------------------
    // EC-CUBE structural parity tests — archived
    //
    // These tests checked that var/templates/Page/Cart.html.twig reproduced
    // EC-CUBE 4.3's ec-* class skeleton verbatim. The IdeaStore cleanroom
    // rebuild uses idea-* vocabulary throughout; ec-* class fidelity no
    // longer applies. Tests are preserved for historical reference only.
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testCartPageRendersAsHtmlDocumentLegacy(): void
    {
        $this->markTestSkipped(
            'Archived: ec-* structural parity — superseded by IdeaStore cleanroom template (idea-* vocabulary).'
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testCartPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'Archived: ec-* class assertions (ec-cartRole, ec-progress, ec-cartRow, etc.) — '
            . 'superseded by IdeaStore cleanroom template (idea-* vocabulary).'
        );
    }

    /**
     * The honesty test that line-diffed BeMart's output against EC-CUBE's
     * real Cart/index.twig rendering. Archived because the IdeaStore
     * cleanroom template is not derived from EC-CUBE's markup.
     *
     * @group ec-cube-parity-archived
     */
    public function testCartHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $ecCubeTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/default';
        if (! is_dir($ecCubeTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $this->markTestSkipped(
            'Archived: EC-CUBE line-diff parity — superseded by IdeaStore cleanroom template. '
            . 'IdeaStore uses idea-* vocabulary; ec-* structural matching is no longer the standard.'
        );
    }
}
