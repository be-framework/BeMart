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

class FlowAdminOrderFulfillmentTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-order-fulfillment';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-order-csrf-token';
    private const SESSION_PREFIX = 'workflow-order-session';

    private static string $orderNo;
    private static string $orderListHref;
    private static string $email;
    private static string $paymentId;
    private static string $productCode;
    private static string $productName;
    private static string $updatedProductName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$email = 'workflow-order-' . $suffix . '@example.com';
        self::$productCode = 'workflow-order-product-' . $suffix;
        self::$productName = 'Workflow Order Product ' . $suffix;
        self::$updatedProductName = 'Workflow Order Published ' . $suffix;
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

    #[Alps('goBaseInfo')]
    public function testShopConfigurationEntry(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('goPaymentList')]
    #[Depends('testShopConfigurationEntry')]
    public function testPaymentList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goPaymentList');
    }

    #[Alps('doCreatePayment')]
    #[Depends('testPaymentList')]
    public function testCreatesPayment(ResourceObject $response): ResourceObject
    {
        $payment = $this->resource->post($this->linkHref($response, 'doCreatePayment'), [
            'paymentMethodName' => 'Workflow Order Payment ' . bin2hex(random_bytes(4)),
            'charge' => 0,
            'ruleMin' => 0,
            'ruleMax' => 999999,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $payment->code);
        self::$paymentId = $this->bodyString($payment, 'paymentId');

        return $payment;
    }

    #[Alps('goOrderList')]
    #[Depends('testCreatesPayment')]
    public function testReturnsToPaymentList(ResourceObject $response): ResourceObject
    {
        $paymentList = $this->follow($response, 'goPaymentList');
        self::$orderListHref = $this->linkHref($paymentList, 'goOrderList');

        return $paymentList;
    }

    #[Alps('goProductList')]
    #[Depends('testReturnsToPaymentList')]
    public function testAdminProductList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goProductList', ['nameKeyword' => self::$productName]);
    }

    #[Alps('doCreateProduct')]
    #[Depends('testAdminProductList')]
    public function testCreatesProduct(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 1200,
            'stock' => 7,
            'productStatus' => 1,
            'description' => 'Created by flow-admin-order-fulfillment so fulfillment uses a real customer checkout order.',
            'searchWord' => 'workflow order fulfillment product',
            'note' => 'Created through admin hypermedia before storefront checkout.',
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
    public function testPublishesProductForCheckout(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$updatedProductName,
            'price02' => 1200,
            'stock' => 7,
            'productStatus' => 1,
            'description' => 'Published by flow-admin-order-fulfillment for storefront checkout.',
            'searchWord' => 'workflow order fulfillment published',
            'note' => 'Updated through admin hypermedia before storefront checkout.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$updatedProductName, $this->bodyValue($updated, 'productName'));

        return $updated;
    }

    #[Alps('goProductList')]
    #[Depends('testPublishesProductForCheckout')]
    public function testStorefrontProductList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goProduct')]
    #[Depends('testStorefrontProductList')]
    public function testStorefrontProductDetail(ResourceObject $response): ResourceObject
    {
        $product = $this->follow($response, 'goProduct', ['productCode' => self::$productCode]);

        $this->assertSame(self::$productCode, $this->bodyValue($product, 'productCode'));
        $this->assertSame(self::$updatedProductName, $this->bodyValue($product, 'productName'));

        return $product;
    }

    #[Alps('doAddCartItem')]
    #[Depends('testStorefrontProductDetail')]
    public function testAddsCartItem(ResourceObject $response): ResourceObject
    {
        $added = $this->resource->post($this->linkHref($response, 'doAddCartItem'), [
            'productCode' => self::$productCode,
            'quantity' => 2,
            'sessionPrefix' => self::SESSION_PREFIX,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $added->code);
        $this->assertSame(self::$productCode, $this->bodyValue($added, 'productCode'));

        return $added;
    }

    #[Alps('goCart')]
    #[Depends('testAddsCartItem')]
    public function testCart(ResourceObject $response): ResourceObject
    {
        $cart = $this->follow($response, 'goCart', ['sessionPrefix' => self::SESSION_PREFIX]);

        $this->assertSame(1, $this->bodyValue($cart, 'cartCount'));

        return $cart;
    }

    #[Alps('goCheckoutEntry')]
    #[Depends('testCart')]
    public function testCheckoutEntryRedirectsAnonymousToShoppingLogin(ResourceObject $response): ResourceObject
    {
        $entry = $this->resource->get($this->linkHref($response, 'goCheckoutEntry'));

        $this->assertSame(Code::SEE_OTHER, $entry->code);

        return $this->followLocation($entry, '/shopping/login');
    }

    #[Alps('goShoppingNonMember')]
    #[Depends('testCheckoutEntryRedirectsAnonymousToShoppingLogin')]
    public function testNonMemberForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goShoppingNonMember');
    }

    #[Alps('doSubmitNonMember')]
    #[Depends('testNonMemberForm')]
    public function testSubmitsNonMember(ResourceObject $response): ResourceObject
    {
        $submitted = $this->resource->post($this->linkHref($response, 'doSubmitNonMember'), [
            'name01' => '受注',
            'name02' => '購入者',
            'kana01' => 'ジュチュウ',
            'kana02' => 'コウニュウシャ',
            'email' => self::$email,
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'sessionPrefix' => self::SESSION_PREFIX,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $submitted->code);
        $this->assertIsString($this->bodyValue($submitted, 'preOrderId'));

        return $submitted;
    }

    #[Alps('doConfirmOrder')]
    #[Depends('testSubmitsNonMember')]
    public function testConfirmsOrder(ResourceObject $response): ResourceObject
    {
        $confirmed = $this->resource->post($this->linkHref($response, 'doConfirmOrder'), [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'payment' => $this->bodyValue($response, 'paymentMethodId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $confirmed->code);
        $this->assertSame($this->bodyValue($response, 'preOrderId'), $this->bodyValue($confirmed, 'preOrderId'));

        return $confirmed;
    }

    #[Alps('doCheckout')]
    #[Depends('testConfirmsOrder')]
    public function testChecksOut(ResourceObject $response): ResourceObject
    {
        $checkedOut = $this->resource->post($this->linkHref($response, 'doCheckout'), [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $checkedOut->code);
        self::$orderNo = $this->bodyString($checkedOut, 'orderNo');

        return $checkedOut;
    }

    #[Alps('ShoppingComplete')]
    #[Depends('testChecksOut')]
    public function testShoppingComplete(ResourceObject $response): ResourceObject
    {
        $complete = $this->followLocation($response);

        $this->assertSame(self::$orderNo, $this->bodyValue($complete, 'orderNo'));

        return $complete;
    }

    #[Alps('goOrderList')]
    #[Depends('testShoppingComplete')]
    public function testOrderList(ResourceObject $response): ResourceObject
    {
        $orderList = $this->resource->get(self::$orderListHref);

        $this->assertSame(Code::OK, $orderList->code);

        return $orderList;
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateOrder'), [
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
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateOrderStatus'), [
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateOrderShippingAddress'), [
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateTrackingNumber'), [
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
        $sent = $this->resource->post($this->linkHref($response, 'doSendOrderMail'), [
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
