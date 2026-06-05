<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowSessionContext;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminProductPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-product-publish';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';
    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $productCode;
    private static string $productName;
    private static string $updatedProductName;
    private static WorkflowSessionContext|null $context = null;

    public static function setUpBeforeClass(): void
    {
        self::$productCode = 'workflow-product-' . bin2hex(random_bytes(4));
        self::$productName = 'Workflow Product Publish ' . self::$productCode;
        self::$updatedProductName = 'Workflow Product Published ' . self::$productCode;
        self::$context = WorkflowSessionContext::capture();
        self::$context->assumeAdminLoggedIn(self::ADMIN_ID, self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        self::$context?->restore();

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;
        self::$context = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        if (self::$dbResource instanceof ResourceInterface) {
            return self::$dbResource;
        }

        assert(self::$injector instanceof InjectorInterface);
        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;

        return $resource;
    }

    #[Alps('goProductList')]
    public function testOpensAdminProductList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/product-list', ['nameKeyword' => self::$productName]);

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateProduct')]
    #[Depends('testOpensAdminProductList')]
    public function testCreatesProduct(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/product', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 2468,
            'stock' => 8,
            'productStatus' => 1,
            'description' => 'DB-backed workflow product publish test.',
            'searchWord' => 'workflow product publish',
            'note' => 'Created by flow-admin-product-publish.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$productCode, $this->bodyValue($created, 'productCode'));

        return $created;
    }

    #[Alps('goProduct')]
    #[Depends('testCreatesProduct')]
    public function testReadsCreatedProductInAdmin(ResourceObject $response): ResourceObject
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::$productCode, $this->bodyValue($read, 'productCode'));
        $this->assertSame(self::$productName, $this->bodyValue($read, 'productName'));

        return $read;
    }

    #[Alps('doUpdateProduct')]
    #[Depends('testReadsCreatedProductInAdmin')]
    public function testUpdatesProduct(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/product', [
            'productCode' => self::$productCode,
            'productName' => self::$updatedProductName,
            'price02' => 3579,
            'stock' => 13,
            'productStatus' => 1,
            'description' => 'Updated DB-backed workflow product publish test.',
            'searchWord' => 'workflow product published',
            'note' => 'Updated by flow-admin-product-publish.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$productCode, $this->bodyValue($updated, 'productCode'));
        $this->assertSame(self::$updatedProductName, $this->bodyValue($updated, 'productName'));

        return $updated;
    }

    #[Alps('goProductList')]
    #[Depends('testUpdatesProduct')]
    public function testFindsProductInStorefrontList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goProduct')]
    #[Depends('testFindsProductInStorefrontList')]
    public function testOpensStorefrontProduct(ResourceObject $response): void
    {
        $product = $this->follow($response, 'goProduct', ['productCode' => self::$productCode]);

        $this->assertSame(self::$productCode, $this->bodyValue($product, 'productCode'));
        $this->assertSame(self::$updatedProductName, $this->bodyValue($product, 'productName'));
        $this->assertSame(3579, $this->bodyValue($product, 'price02'));
    }
}
