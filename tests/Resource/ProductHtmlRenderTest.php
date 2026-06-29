<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * Product detail page (goProduct) — IdeaStore HTML rendering verification.
 *
 * The template (var/templates/Page/Product.html.twig) is a clean-room
 * IdeaStore rebuild — not a port of EC-CUBE's default theme. Tests verify
 * the semantic contract (L1: required fields rendered, L2: form action /
 * method / link href / rel) rather than EC-CUBE markup structure.
 *
 * Data contract (src/Resource/Page/Product.php + var/json_schema/get-product.json):
 *   Required: productCode, productName, price02, stockFind
 *   Optional: description, categoryNames, tagNames, classNames, mainImage, csrfToken
 *   Form:     form (AddCartForm — html context only)
 *
 * Transitions on Product::onGet:
 *   goProductList    GET    /products
 *   doAddCartItem    POST   /cart/item
 */
final class ProductHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L0 — HTTP + document shell
    // -------------------------------------------------------------------------

    public function testReturnsOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/product', ['productCode' => 'sample-001']);

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testRendersValidHtmlDocumentShell(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // -------------------------------------------------------------------------
    // L1 — Required fields rendered
    // -------------------------------------------------------------------------

    public function testProductNameAppearsInTitleAndBody(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        // <title> block
        $this->assertStringContainsString('サンプル商品 A | IDEA STORE', $html);
        // body h1
        $this->assertStringContainsString('サンプル商品 A', $html);
    }

    public function testPriceRenderedWithYenSign(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        // price02 must appear formatted with ¥ prefix
        $this->assertMatchesRegularExpression('/¥[\d,]+/', $html);
    }

    public function testProductCodeRenderedInSpecTable(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('sample-001', $html);
    }

    public function testStockFindControlsBuyboxState(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        // sample-001 is in-stock (stockFind=true): submit button must be present
        $this->assertStringContainsString('カートに入れる', $html);
        $this->assertStringNotContainsString('ただいま品切れ中です', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — Form action / method, link href / rel
    // -------------------------------------------------------------------------

    public function testAddCartFormHasCorrectActionAndMethod(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        // action must route to /cart/item (doAddCartItem POST /cart/item)
        $this->assertStringContainsString('action="/cart/item"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testAddCartFormCarriesRelAttribute(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('rel="doAddCartItem"', $html);
    }

    public function testBreadcrumbProductListLinkPresent(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        // goProductList -> /products (or /products/list)
        $this->assertMatchesRegularExpression('#href="/products[^"]*"#', $html);
        $this->assertStringContainsString('rel="goProductList"', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — Form fields (AddCartForm)
    // -------------------------------------------------------------------------

    public function testQuantityInputRenderedWithRequiredAttributes(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('name="quantity"', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="1"', $html);
    }

    public function testProductCodeHiddenInputSeededCorrectly(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('type="hidden" name="productCode" value="sample-001"', $html);
    }

    // -------------------------------------------------------------------------
    // L1 — IdeaStore design language: idea-* classes present
    // -------------------------------------------------------------------------

    public function testIdeaStoreLayoutClassesPresent(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        foreach ([
            'class="idea-store"',
            'idea-container',
            'idea-product-detail',
            'idea-product-buybox',
            'idea-section-title',
            'idea-cart-form',
            'idea-button',
            'idea-spec-list',
            'idea-spec-row',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "IdeaStore class missing: {$needle}");
        }
    }

    public function testNoEcCubeClassesPresent(): void
    {
        $html = $this->resource
            ->get('page://self/product', ['productCode' => 'sample-001'])
            ->toString();

        foreach ([
            'ec-productRole',
            'ec-grid2',
            'ec-sliderItemRole',
            'ec-price',
            'ec-numberInput',
            'ec-blockBtn',
            'ec-modal',
            'ec-headingTitle',
        ] as $ecClass) {
            $this->assertStringNotContainsString($ecClass, $html, "EC-CUBE class must not appear: {$ecClass}");
        }
    }

    // -------------------------------------------------------------------------
    // Archived: EC-CUBE parity comparison
    // These tests compared BeMart's rendering against EC-CUBE 4.3's
    // default-theme detail.twig. The template is now a clean-room IdeaStore
    // design; EC-CUBE structural parity is no longer the target.
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testProductDetailHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: Product.html.twig is now a clean-room '
            . 'IdeaStore design, not a port of EC-CUBE default-theme detail.twig. '
            . 'Structural fidelity to EC-CUBE markup is no longer the contract; '
            . 'semantic/functional verification lives in the L1/L2 tests above.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testProductDetailPageRendersAsHtmlDocument(): void
    {
        $this->markTestSkipped('Superseded by testRendersValidHtmlDocumentShell (ec-cube-parity-archived).');
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testProductDetailPagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure (ec-productRole, ec-grid2, ec-sliderItemRole …) '
            . 'is no longer the target. IdeaStore idea-* structure is verified by '
            . 'testIdeaStoreLayoutClassesPresent (ec-cube-parity-archived).',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testProductDetailPageRendersRealAddCartFormInputs(): void
    {
        $this->markTestSkipped(
            'Superseded by testQuantityInputRenderedWithRequiredAttributes and '
            . 'testProductCodeHiddenInputSeededCorrectly (ec-cube-parity-archived).',
        );
    }
}
