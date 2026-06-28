<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Phase 3 — HTML render check for the admin Product Variant (SKU) editor.
 *
 * L1: required data output (productCode context, form fields)
 * L2: action contract (form action/method, navigation link href/rel)
 * Frame: idea-admin-shell / idea-admin-content landmarks from admin-base.html.twig
 *
 * EC-CUBE markup-parity assertions have been removed — the template is
 * a clean-room idea-admin-* design and does not mirror EC-CUBE DOM structure.
 * If EC-CUBE visual fidelity tests are needed in future, annotate them with
 * @group ec-cube-parity-archived and call markTestSkipped().
 */
final class AdminProductClassHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** L0: resource returns 200 with text/html content type */
    public function testRendersOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/product/product-class');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L0: admin-base.html.twig frame landmarks are present */
    public function testFrameLandmarksPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/product-class')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        // idea-admin-shell wraps the entire admin layout (topbar + sidebar + content)
        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        // idea-admin-content is the main content landmark
        $this->assertStringContainsString('class="idea-admin-content"', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /**
     * L1: form fields derived from AdminProductClassForm must be rendered.
     * Fields: price02 (required), stock, stock_unlimited, product_code, delivery_fee.
     */
    public function testRequiredFormFieldsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/product/product-class')->toString();

        $this->assertStringContainsString('id="product_class_price02"', $html, 'price02 field missing');
        $this->assertStringContainsString('id="product_class_stock"', $html, 'stock field missing');
        $this->assertStringContainsString('id="product_class_stock_unlimited"', $html, 'stock_unlimited checkbox missing');
        $this->assertStringContainsString('id="product_class_code"', $html, 'product_code field missing');
        $this->assertStringContainsString('id="product_class_delivery_fee"', $html, 'delivery_fee field missing');
    }

    /**
     * L2: form action contract — the add-variant form must POST to the
     * correct endpoint as defined by the resource routing convention.
     */
    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/product/product-class')->toString();

        $this->assertStringContainsString('action="/admin/product/product-class"', $html, 'form action missing');
        $this->assertStringContainsString('method="post"', $html, 'form method missing');
        $this->assertStringContainsString('id="idea-product-class-form"', $html, 'form id missing');
    }

    /**
     * L2: navigation link contract — toolbar must carry goProductList href and rel.
     */
    public function testProductListNavLinkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/product-class')->toString();

        $this->assertStringContainsString('href="/admin/product-list"', $html, 'goProductList href missing');
        $this->assertStringContainsString('rel="goProductList"', $html, 'goProductList rel missing');
    }

    /**
     * L2: with productCode query param, goProduct back-link href and rel must appear.
     */
    public function testProductEditBackLinkWithProductCode(): void
    {
        $html = $this->resource
            ->get('page://self/admin/product/product-class', ['productCode' => 'sample-001'])
            ->toString();

        $this->assertStringContainsString('/admin/product/edit', $html, 'goProduct href missing');
        $this->assertStringContainsString('rel="goProduct"', $html, 'goProduct rel missing');
        $this->assertStringContainsString('sample-001', $html, 'productCode context missing from page');
    }

    /**
     * L1: when classes array is empty the empty-state element must be present.
     * Renders with blank Fake storage, so no seed is needed.
     */
    public function testEmptyStateRenderedWhenNoClasses(): void
    {
        $html = $this->resource->get('page://self/admin/product/product-class')->toString();

        // The resource initialises classes as [] in onGet, so the empty branch fires.
        $this->assertStringContainsString('idea-admin-empty', $html, 'empty-state element missing');
    }
}
