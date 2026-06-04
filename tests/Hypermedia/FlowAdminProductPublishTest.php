<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;

use function assert;

class FlowAdminProductPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-product-publish';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goProductList')]
    public function testOpensAdminProductList(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish open admin product list.');
    }

    #[Alps('doCreateProduct')]
    #[Depends('testOpensAdminProductList')]
    public function testCreatesProduct(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish create product.');
    }

    #[Alps('goProduct')]
    #[Depends('testCreatesProduct')]
    public function testReadsCreatedProductInAdmin(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish read created product in admin.');
    }

    #[Alps('doUpdateProduct')]
    #[Depends('testReadsCreatedProductInAdmin')]
    public function testUpdatesProduct(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish update product.');
    }

    #[Alps('goProductList')]
    #[Depends('testUpdatesProduct')]
    public function testFindsProductInStorefrontList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish find edited product in storefront list.');
    }

    #[Alps('goProduct')]
    #[Depends('testFindsProductInStorefrontList')]
    public function testOpensStorefrontProduct(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-product-publish open edited storefront product.');
    }
}
