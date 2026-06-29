<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Phase 3 — HTML render check for the admin Product editor.
 *
 * Scope: functional / semantic verification of the clean-room
 * idea-admin template.
 *
 * L1 — required data fields present in rendered output.
 * L2 — action/method contract and link rel attributes correct.
 * Frame — idea-admin-shell / idea-admin-content landmark present.
 *
 * EC-CUBE parity rendering tests (exact pixel/markup match against
 * an EC-CUBE 4.3 reference clone) are archived below; they are
 * skipped until the reference clone lands in tools/ec-cube-source/.
 *
 * @group admin-product
 */
final class AdminProductEditHtmlRenderTest extends TestCase
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

    // ── Frame ────────────────────────────────────────────────────────────

    /**
     * Frame landmark: the page is a full HTML document inside the
     * idea-admin-shell / idea-admin-content structure.
     */
    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/product/edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * Frame landmark: idea-admin-shell wraps the page; the main region
     * is the idea-admin-content area from admin-base.html.twig.
     */
    public function testFrameLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'frame: idea-admin-shell required');
        $this->assertStringContainsString('idea-admin-content', $html, 'frame: idea-admin-content required');
    }

    // ── L1: required data fields ─────────────────────────────────────────

    /**
     * L1 — productName input bound to the form contract.
     */
    public function testL1ProductNameFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('id="product_name"', $html, 'L1: productName field id required');
    }

    /**
     * L1 — price02 input bound to the form contract.
     */
    public function testL1Price02FieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('id="product_price02"', $html, 'L1: price02 field id required');
    }

    /**
     * L1 — section headings: 基本情報 and 価格・在庫.
     */
    public function testL1SectionHeadingsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('基本情報', $html, 'L1: 基本情報 section heading required');
        $this->assertStringContainsString('価格・在庫', $html, 'L1: 価格・在庫 section heading required');
    }

    /**
     * L1 — CSRF hidden field present.
     */
    public function testL1CsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'L1: csrfToken hidden field required');
    }

    // ── L2: action/method contract and link rel ──────────────────────────

    /**
     * L2 — form id present (used by tests and accessibility tooling).
     */
    public function testL2FormIdPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('id="idea-product-form"', $html, 'L2: form id required');
    }

    /**
     * L2 — form method must be POST (PUT is tunnelled via _method).
     */
    public function testL2FormMethodPost(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('method="post"', $html, 'L2: form method must be POST');
    }

    /**
     * L2 — form action points at /admin/product (doUpdateProduct endpoint).
     */
    public function testL2FormActionCorrect(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('action="/admin/product"', $html, 'L2: form action must be /admin/product');
    }

    /**
     * L2 — back link carries rel="goProductList" (ALPS transition).
     * Available in both create and edit mode.
     */
    public function testL2GoProductListLinkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/product/edit')->toString();

        $this->assertStringContainsString('rel="goProductList"', $html, 'L2: goProductList link rel required');
    }

    /**
     * L2 — delete action carries rel="doDeleteProduct".
     * These affordances are only rendered in edit mode (productCode supplied).
     * Inject a stub ProductQueryInterface to enable edit mode.
     */
    public function testL2DoDeleteProductRelPresent(): void
    {
        $html = $this->fetchEditModeHtml('p-test-001');

        $this->assertStringContainsString('rel="doDeleteProduct"', $html, 'L2: doDeleteProduct rel required in edit mode');
    }

    /**
     * L2 — copy action carries rel="doCopyProduct".
     * These affordances are only rendered in edit mode (productCode supplied).
     * Inject a stub ProductQueryInterface to enable edit mode.
     */
    public function testL2DoCopyProductRelPresent(): void
    {
        $html = $this->fetchEditModeHtml('p-test-001');

        $this->assertStringContainsString('rel="doCopyProduct"', $html, 'L2: doCopyProduct rel required in edit mode');
    }

    /**
     * Returns the rendered HTML for product edit mode by injecting a stub
     * ProductQueryInterface that returns a seeded product.
     */
    private function fetchEditModeHtml(string $productCode): string
    {
        $fakeProduct = new ProductEntity(
            productCode: $productCode,
            productName: 'テスト商品',
            price02: 1000,
            stock: 10,
        );
        $fakeQuery = new class ($fakeProduct) implements ProductQueryInterface {
            public function __construct(private readonly ProductEntity $product)
            {
            }

            public function item(string $productCode): ProductEntity|null
            {
                return $this->product->productCode === $productCode ? $this->product : null;
            }

            /** @return list<ProductEntity> */
            public function list(int $limit, int $offset = 0): array
            {
                return [$this->product];
            }

            /** @return list<ProductEntity> */
            public function search(?string $nameKeyword, int $limit = 50): array
            {
                return [$this->product];
            }

            /** @return list<ProductEntity> */
            public function listForExport(int $limit = 100, int $offset = 0): array
            {
                return [$this->product];
            }
        };
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(
            new class ($session, $fakeQuery) extends AbstractModule {
                public function __construct(
                    private readonly FakeAdminSession $session,
                    private readonly ProductQueryInterface $productQuery,
                ) {
                    parent::__construct();
                }

                protected function configure(): void
                {
                    $this->bind(AdminSession::class)->toInstance($this->session);
                    $this->bind(ProductQueryInterface::class)->toInstance($this->productQuery);
                }
            },
        );

        return $injector->getInstance(ResourceInterface::class)
            ->get('page://self/admin/product/edit', ['productCode' => $productCode])
            ->toString();
    }

    // ── @group ec-cube-parity-archived ───────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     * Exact markup parity with EC-CUBE 4.3 admin/Product/product.twig
     * is deferred until the reference clone lands in tools/ec-cube-source/.
     */
    public function testEcCubeMarkupParity(): void
    {
        $this->markTestSkipped(
            'EC-CUBE 4.3 reference clone not present; parity test archived.'
        );
    }
}
