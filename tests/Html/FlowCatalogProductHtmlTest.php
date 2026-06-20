<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML regression walk for the storefront catalog: clicking a product from the
 * listing must open its detail page, not 404 (the original "トップで商品を
 * クリックしても404" bug).
 *
 * Self-contained: eccubedb_test carries no stable public product, so it publishes
 * one through the admin editor, then walks the storefront as a visitor would —
 * list -> open the product -> read its rendered name. The detail GET returning
 * 200 (not 404) is the regression assertion.
 */
final class FlowCatalogProductHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-catalog-product-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'flow-catalog-product-csrf-token';

    private static string $productCode;
    private static string $productName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$productCode = 'catalog-' . $suffix;
        self::$productName = 'Catalog Product ' . $suffix;
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8114',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('doCreateProduct')]
    public function testPublishesProduct(): void
    {
        $editor = $this->resource->get('page://self/admin/product/edit');
        $this->assertSame(Code::OK, $editor->code);

        $created = $this->submit($editor, 'doCreateProduct', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => '1480',
            'productStatus' => '1',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'publishing the product failed: ' . (string) ($created->view ?? $created->code),
        );
    }

    #[Depends('testPublishesProduct')]
    #[Alps('goProductList')]
    public function testStorefrontListsProduct(): ResourceObject
    {
        $list = $this->resource->get('page://self/products', ['nameKeyword' => self::$productName]);

        $this->assertSame(Code::OK, $list->code);
        $this->assertStringContainsString(self::$productCode, (string) ($list->view ?? ''));

        return $list;
    }

    #[Depends('testStorefrontListsProduct')]
    #[Alps('goProduct')]
    public function testStorefrontProductDetailOpens(): void
    {
        $product = $this->resource->get('page://self/product', ['productCode' => self::$productCode]);

        // The regression: the detail must open (200), not 404. The storefront
        // detail shows the name as display copy (a heading), not a control, so a
        // presence check fits — assertState() is for control/class-rendered state.
        $this->assertSame(Code::OK, $product->code, (string) ($product->view ?? $product->code));
        $this->assertStringContainsString(self::$productName, (string) ($product->view ?? ''));
    }
}
