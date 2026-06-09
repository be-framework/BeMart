<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminOrderFulfillmentTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-order-fulfillment';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-order-csrf-token';

    private static string $orderNo;
    private static string $paymentId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
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

    #[Alps('goOrderList')]
    public function testOrderList(): ResourceObject
    {
        $payment = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'Workflow Order Payment ' . bin2hex(random_bytes(4)),
            'charge' => 0,
            'ruleMin' => 0,
            'ruleMax' => 999999,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $payment->code);
        self::$paymentId = $this->bodyString($payment, 'paymentId');

        $order = $this->resource->post('page://self/admin/order/create', [
            'customerId' => 'workflow-customer-' . bin2hex(random_bytes(4)),
            'paymentMethodId' => (int) self::$paymentId,
            'orderItems' => [
                [
                    'productCode' => 'workflow-order-' . bin2hex(random_bytes(4)),
                    'productName' => 'Workflow Order Item',
                    'unitPrice' => 1200,
                    'quantity' => 2,
                ],
            ],
            'deliveryFeeTotal' => 500,
            'charge' => 0,
            'discount' => 0,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $order->code);
        self::$orderNo = $this->bodyString($order, 'orderNo');

        $response = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('goOrder')]
    #[Depends('testOrderList')]
    public function testOrder(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrder', ['orderNo' => self::$orderNo]);
    }

    #[Alps('doUpdateOrder')]
    #[Depends('testOrder')]
    public function testUpdatesOrder(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/order', [
            'orderNo' => self::$orderNo,
            'discount' => 100,
            'charge' => 50,
            'usePoint' => 0,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($updated, 'orderNo'));

        return $updated;
    }

    #[Alps('doUpdateOrderStatus')]
    #[Depends('testUpdatesOrder')]
    public function testUpdatesOrderStatus(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::$orderNo,
            'orderStatus' => 4,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($updated, 'orderNo'));
        $this->assertSame(4, $this->bodyValue($updated, 'orderStatus'));

        return $updated;
    }

    #[Alps('goOrderShippingAddress')]
    #[Depends('testUpdatesOrderStatus')]
    public function testOrderShippingAddress(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderShippingAddress', ['orderNo' => self::$orderNo]);
    }

    #[Alps('doUpdateOrderShippingAddress')]
    #[Depends('testOrderShippingAddress')]
    public function testUpdatesOrderShippingAddress(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => self::$orderNo,
            'name01' => '配送',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($updated, 'orderNo'));

        return $updated;
    }

    #[Alps('doUpdateTrackingNumber')]
    #[Depends('testUpdatesOrderShippingAddress')]
    public function testUpdatesTrackingNumber(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::$orderNo,
            'trackingNumber' => 'TRK' . self::$paymentId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($updated, 'orderNo'));

        return $updated;
    }

    #[Alps('goOrderMail')]
    #[Depends('testUpdatesTrackingNumber')]
    public function testOrderMail(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderMail', ['orderNo' => self::$orderNo]);
    }

    #[Alps('goOrderMailConfirm')]
    #[Depends('testOrderMail')]
    public function testOrderMailConfirm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderMailConfirm', ['orderNo' => self::$orderNo]);
    }

    #[Alps('doSendOrderMail')]
    #[Depends('testOrderMailConfirm')]
    public function testSendsOrderMail(ResourceObject $response): ResourceObject
    {
        $sent = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => self::$orderNo,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $sent->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($sent, 'orderNo'));

        return $sent;
    }

    #[Alps('goExportOrderPdf')]
    #[Depends('testSendsOrderMail')]
    public function testExportsOrderPdf(ResourceObject $response): ResourceObject
    {
        $this->follow($response, 'goExportOrderPdf', ['orderNo' => self::$orderNo]);

        return $response;
    }

    #[Alps('goExportOrder')]
    #[Depends('testExportsOrderPdf')]
    public function testExportsOrderCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportOrder');
    }

    #[Alps('goExportShipping')]
    #[Depends('testExportsOrderCsv')]
    public function testExportsShippingCsv(ResourceObject $response): void
    {
        $this->follow($response, 'goExportShipping');
    }
}
