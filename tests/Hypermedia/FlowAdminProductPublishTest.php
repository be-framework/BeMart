<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminProductPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-product-publish';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';
    private static string $productCode;
    private static string $copiedProductCode;
    private static string $productName;
    private static string $updatedProductName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$productCode = 'workflow-product-' . bin2hex(random_bytes(4));
        self::$copiedProductCode = self::$productCode . '-copy';
        self::$productName = 'Workflow Product Publish ' . self::$productCode;
        self::$updatedProductName = 'Workflow Product Published ' . self::$productCode;
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

        return self::$dbSession->resource();
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
        $created = $this->resource->post($this->linkHref($response, 'doCreateProduct'), [
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateProduct'), [
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

    #[Alps('goProduct')]
    #[Depends('testUpdatesProduct')]
    public function testReadsUpdatedProductInAdmin(ResourceObject $response): ResourceObject
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::$productCode, $this->bodyValue($read, 'productCode'));
        $this->assertSame(self::$updatedProductName, $this->bodyValue($read, 'productName'));
        $this->assertSame(1, $this->bodyValue($read, 'productStatus'));

        return $read;
    }

    #[Alps('doCopyProduct')]
    #[Depends('testReadsUpdatedProductInAdmin')]
    public function testCopiesProduct(ResourceObject $response): ResourceObject
    {
        $copied = $this->resource->post($this->linkHref($response, 'doCopyProduct'), [
            'productCode' => self::$productCode,
            'newProductCode' => self::$copiedProductCode,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $copied->code);
        $this->assertSame(self::$productCode, $this->bodyValue($copied, 'productCode'));
        $this->assertSame(self::$copiedProductCode, $this->bodyValue($copied, 'newProductCode'));

        return $copied;
    }

    #[Alps('goProduct')]
    #[Depends('testCopiesProduct')]
    public function testReadsCopiedProductInAdmin(ResourceObject $response): ResourceObject
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::$copiedProductCode, $this->bodyValue($read, 'productCode'));
        $this->assertSame('(コピー) ' . self::$updatedProductName, $this->bodyValue($read, 'productName'));
        $this->assertSame(1, $this->bodyValue($read, 'productStatus'));

        return $read;
    }

    #[Alps('doBulkUpdateProductStatus')]
    #[Depends('testReadsCopiedProductInAdmin')]
    public function testBulkUnpublishesCopiedProduct(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$copiedProductCode]);

        $updated = $this->resource->post($this->linkHref($list, 'doBulkUpdateProductStatus'), [
            'productCodes' => [self::$copiedProductCode],
            'productStatus' => 2,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame([self::$copiedProductCode], $this->bodyValue($updated, 'productCodes'));
        $this->assertSame(2, $this->bodyValue($updated, 'productStatus'));
        $this->assertSame(1, $this->bodyValue($updated, 'requestedCount'));
        $this->assertSame(1, $this->bodyValue($updated, 'changedCount'));

        return $updated;
    }

    #[Alps('goProduct')]
    #[Depends('testBulkUnpublishesCopiedProduct')]
    public function testReadsUnpublishedCopiedProductInAdmin(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$copiedProductCode]);
        $read = $this->follow($list, 'goProduct', ['productCode' => self::$copiedProductCode]);

        $this->assertSame(self::$copiedProductCode, $this->bodyValue($read, 'productCode'));
        $this->assertSame(2, $this->bodyValue($read, 'productStatus'));

        return $read;
    }

    #[Alps('doDeleteProduct')]
    #[Depends('testReadsUnpublishedCopiedProductInAdmin')]
    public function testDeletesCopiedProduct(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteProduct'), [
            'productCode' => self::$copiedProductCode,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame(self::$copiedProductCode, $this->bodyValue($deleted, 'productCode'));
        $this->assertFalse($this->bodyValue($deleted, 'alreadyDeleted'));

        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$copiedProductCode]);
        $read = $this->follow($list, 'goProduct', ['productCode' => self::$copiedProductCode]);
        $this->assertSame(3, $this->bodyValue($read, 'productStatus'));

        return $read;
    }

    #[Alps('goProductList')]
    #[Depends('testUpdatesProduct')]
    public function testFindsProductInStorefrontList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $products = $this->bodyValue($list, 'products');
        $this->assertIsArray($products);
        $this->assertContains(self::$productCode, array_column($products, 'productCode'));

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
