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

class FlowCustomerPurchaseTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-purchase';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase open storefront top.');
    }

    #[Alps('goProductList')]
    #[Depends('testIndex')]
    public function testProductList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase follow product list.');
    }

    #[Alps('goProduct')]
    #[Depends('testProductList')]
    public function testProductDetail(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase open product detail.');
    }

    #[Alps('doAddCartItem')]
    #[Depends('testProductDetail')]
    public function testAddsCartItem(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase add product to cart.');
    }

    #[Alps('goCart')]
    #[Depends('testAddsCartItem')]
    public function testCart(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase open cart with added product.');
    }

    #[Alps('goShoppingNonMember')]
    #[Depends('testCart')]
    public function testNonMemberForm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase open non-member checkout form.');
    }

    #[Alps('doSubmitNonMember')]
    #[Depends('testNonMemberForm')]
    public function testSubmitsNonMember(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase submit non-member checkout data.');
    }

    #[Alps('doConfirmOrder')]
    #[Depends('testSubmitsNonMember')]
    public function testConfirmsOrder(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase confirm order.');
    }

    #[Alps('doCheckout')]
    #[Depends('testConfirmsOrder')]
    public function testChecksOut(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-purchase checkout order.');
    }

    #[Alps('ShoppingComplete')]
    #[Depends('testChecksOut')]
    public function testShoppingComplete(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-customer-purchase verify shopping completion evidence.');
    }
}
