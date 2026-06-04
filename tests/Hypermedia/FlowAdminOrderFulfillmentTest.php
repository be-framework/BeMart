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

class FlowAdminOrderFulfillmentTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-order-fulfillment';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goOrderList')]
    public function testOrderList(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment open order list.');
    }

    #[Alps('goOrder')]
    #[Depends('testOrderList')]
    public function testOrder(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment open target order.');
    }

    #[Alps('doUpdateOrder')]
    #[Depends('testOrder')]
    public function testUpdatesOrder(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment update order.');
    }

    #[Alps('doUpdateOrderStatus')]
    #[Depends('testUpdatesOrder')]
    public function testUpdatesOrderStatus(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment update order status.');
    }

    #[Alps('goOrderShippingAddress')]
    #[Depends('testUpdatesOrderStatus')]
    public function testOrderShippingAddress(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment open order shipping address.');
    }

    #[Alps('doUpdateOrderShippingAddress')]
    #[Depends('testOrderShippingAddress')]
    public function testUpdatesOrderShippingAddress(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment update order shipping address.');
    }

    #[Alps('doUpdateTrackingNumber')]
    #[Depends('testUpdatesOrderShippingAddress')]
    public function testUpdatesTrackingNumber(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment update tracking number.');
    }

    #[Alps('goOrderMail')]
    #[Depends('testUpdatesTrackingNumber')]
    public function testOrderMail(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment open order mail form.');
    }

    #[Alps('goOrderMailConfirm')]
    #[Depends('testOrderMail')]
    public function testOrderMailConfirm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment confirm order mail.');
    }

    #[Alps('doSendOrderMail')]
    #[Depends('testOrderMailConfirm')]
    public function testSendsOrderMail(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment send order mail.');
    }

    #[Alps('goExportOrderPdf')]
    #[Depends('testSendsOrderMail')]
    public function testExportsOrderPdf(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment export order PDF.');
    }

    #[Alps('goExportOrder')]
    #[Depends('testExportsOrderPdf')]
    public function testExportsOrderCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment export order CSV.');
    }

    #[Alps('goExportShipping')]
    #[Depends('testExportsOrderCsv')]
    public function testExportsShippingCsv(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-order-fulfillment export shipping CSV.');
    }
}
