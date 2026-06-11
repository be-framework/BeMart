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

class FlowCustomerPurchaseTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-purchase';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';
    private const SESSION_PREFIX = 'workflow-purchase-session';
    private const MEMBER_SESSION_PREFIX = 'workflow-member-purchase-session';
    private const PASSWORD = 'workflow-password-2026';

    private static string $email;
    private static string $productCode;
    private static string $productName;
    private static string $updatedProductName;
    private static string $memberOrderNo;
    private static string $orderListHref;
    private static string $mypageHref;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$productCode = 'workflow-purchase-' . $suffix;
        self::$productName = 'Workflow Purchase Product ' . $suffix;
        self::$updatedProductName = 'Workflow Purchase Published ' . $suffix;
        self::$email = 'workflow-purchase-' . $suffix . '@example.com';
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

    #[Alps('goProductList')]
    #[Depends('testShopConfigurationEntry')]
    public function testAdminProductList(ResourceObject $response): ResourceObject
    {
        self::$orderListHref = $this->linkHref($response, 'goOrderList');

        return $this->follow($response, 'goProductList');
    }

    #[Alps('doCreateProduct')]
    #[Depends('testAdminProductList')]
    public function testCreatesProduct(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 2468,
            'stock' => 9,
            'productStatus' => 1,
            'description' => 'Created by flow-customer-purchase so checkout has a real product.',
            'searchWord' => 'workflow purchase product',
            'note' => 'Created through admin hypermedia before customer checkout.',
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

        return $read;
    }

    #[Alps('doUpdateProduct')]
    #[Depends('testReadsCreatedProductInAdmin')]
    public function testPublishesProductToStorefront(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$updatedProductName,
            'price02' => 2468,
            'stock' => 9,
            'productStatus' => 1,
            'description' => 'Published by flow-customer-purchase.',
            'searchWord' => 'workflow purchase published',
            'note' => 'Updated through admin hypermedia before customer checkout.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$updatedProductName, $this->bodyValue($updated, 'productName'));

        return $updated;
    }

    #[Alps('goProductList')]
    #[Depends('testPublishesProductToStorefront')]
    public function testProductList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goProduct')]
    #[Depends('testProductList')]
    public function testProductDetail(ResourceObject $response): ResourceObject
    {
        $product = $this->follow($response, 'goProduct', ['productCode' => self::$productCode]);

        $this->assertSame(self::$productCode, $this->bodyValue($product, 'productCode'));
        $this->assertSame(self::$updatedProductName, $this->bodyValue($product, 'productName'));

        return $product;
    }

    #[Alps('doAddCartItem')]
    #[Depends('testProductDetail')]
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
            'name01' => '非会員',
            'name02' => '太郎',
            'kana01' => 'ヒカイイン',
            'kana02' => 'タロウ',
            'email' => 'workflow-non-member@example.com',
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
        $this->assertIsString($this->bodyValue($checkedOut, 'orderNo'));

        return $checkedOut;
    }

    #[Alps('ShoppingComplete')]
    #[Depends('testChecksOut')]
    public function testShoppingComplete(ResourceObject $response): ResourceObject
    {
        $complete = $this->followLocation($response);

        $this->assertSame($this->bodyValue($response, 'orderNo'), $this->bodyValue($complete, 'orderNo'));

        return $complete;
    }

    #[Alps('goTop')]
    #[Depends('testShoppingComplete')]
    public function testReturnsTopForMemberRegistration(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTop');
    }

    #[Alps('goCustomerRegistration')]
    #[Depends('testReturnsTopForMemberRegistration')]
    public function testMemberRegistrationForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistration');
    }

    #[Alps('goCustomerRegistrationConfirm')]
    #[Depends('testMemberRegistrationForm')]
    public function testMemberRegistrationConfirm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistrationConfirm');
    }

    #[Alps('doRegisterCustomer')]
    #[Depends('testMemberRegistrationConfirm')]
    public function testRegistersMember(ResourceObject $response): ResourceObject
    {
        $registered = $this->resource->post($this->linkHref($response, 'doRegisterCustomer'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => 'Workflow',
            'name02' => 'Member',
            'kana01' => 'ワークフロー',
            'kana02' => 'メンバー',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $registered->code);
        $this->assertSame(self::$email, $this->bodyValue($registered, 'email'));

        return $registered;
    }

    #[Alps('CustomerRegistrationComplete')]
    #[Depends('testRegistersMember')]
    public function testMemberRegistrationComplete(ResourceObject $response): ResourceObject
    {
        return $this->followLocation($response);
    }

    #[Alps('goTop')]
    #[Depends('testMemberRegistrationComplete')]
    public function testReturnsTopForMemberLogin(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTop');
    }

    #[Alps('goLogin')]
    #[Depends('testReturnsTopForMemberLogin')]
    public function testMemberLoginForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goLogin');
    }

    #[Alps('doLogin')]
    #[Depends('testMemberLoginForm')]
    public function testLogsInMember(ResourceObject $response): ResourceObject
    {
        $loggedIn = $this->resource->post($this->linkHref($response, 'doLogin'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedIn->code);
        $this->assertSame(self::$email, $this->bodyValue($loggedIn, 'email'));

        assert(self::$dbSession instanceof WorkflowDbSession);
        self::$dbSession->session()->setCustomerId((string) $this->bodyValue($loggedIn, 'customerId'));

        return $loggedIn;
    }

    #[Alps('goMypage')]
    #[Depends('testLogsInMember')]
    public function testMemberMypage(ResourceObject $response): ResourceObject
    {
        $mypage = $this->follow($response, 'goMypage');

        $this->assertSame(self::$email, $this->bodyValue($mypage, 'email'));

        return $mypage;
    }

    #[Alps('goProductList')]
    #[Depends('testMemberMypage')]
    public function testMemberProductList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goProduct')]
    #[Depends('testMemberProductList')]
    public function testMemberProductDetail(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goProduct', ['productCode' => self::$productCode]);
    }

    #[Alps('doAddCartItem')]
    #[Depends('testMemberProductDetail')]
    public function testAddsMemberCartItem(ResourceObject $response): ResourceObject
    {
        $added = $this->resource->post($this->linkHref($response, 'doAddCartItem'), [
            'productCode' => self::$productCode,
            'quantity' => 1,
            'sessionPrefix' => self::MEMBER_SESSION_PREFIX,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $added->code);

        return $added;
    }

    #[Alps('goCart')]
    #[Depends('testAddsMemberCartItem')]
    public function testMemberCart(ResourceObject $response): ResourceObject
    {
        $cart = $this->follow($response, 'goCart', ['sessionPrefix' => self::MEMBER_SESSION_PREFIX]);

        $this->assertSame(1, $this->bodyValue($cart, 'cartCount'));

        return $cart;
    }

    #[Alps('goCheckoutEntry')]
    #[Depends('testMemberCart')]
    public function testMemberCheckoutEntry(ResourceObject $response): ResourceObject
    {
        $entry = $this->resource->get($this->linkHref($response, 'goCheckoutEntry'), [
            'sessionPrefix' => self::MEMBER_SESSION_PREFIX,
        ]);

        $this->assertSame(Code::OK, $entry->code);
        $this->assertTrue((bool) $this->bodyValue($entry, 'canCheckout'));

        return $entry;
    }

    #[Alps('doConfirmOrder')]
    #[Depends('testMemberCheckoutEntry')]
    public function testConfirmsMemberOrder(ResourceObject $response): ResourceObject
    {
        $paymentMethods = $this->bodyValue($response, 'paymentMethods');
        $this->assertIsArray($paymentMethods);
        $paymentMethod = $paymentMethods[0] ?? null;
        $this->assertIsArray($paymentMethod);

        $confirmed = $this->resource->post($this->linkHref($response, 'doConfirmOrder'), [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'payment' => $paymentMethod['paymentMethodId'],
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $confirmed->code);

        return $confirmed;
    }

    #[Alps('doCheckout')]
    #[Depends('testConfirmsMemberOrder')]
    public function testChecksOutMemberOrder(ResourceObject $response): ResourceObject
    {
        $checkedOut = $this->resource->post($this->linkHref($response, 'doCheckout'), [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $checkedOut->code);
        self::$memberOrderNo = $this->bodyString($checkedOut, 'orderNo');

        return $checkedOut;
    }

    #[Alps('ShoppingComplete')]
    #[Depends('testChecksOutMemberOrder')]
    public function testMemberShoppingComplete(ResourceObject $response): ResourceObject
    {
        $complete = $this->followLocation($response);

        $this->assertSame(self::$memberOrderNo, $this->bodyValue($complete, 'orderNo'));
        self::$mypageHref = $this->linkHref($complete, 'goMypage');

        return $complete;
    }

    #[Alps('goOrderList')]
    #[Depends('testMemberShoppingComplete')]
    public function testAdminOrderListAfterMemberCheckout(ResourceObject $response): ResourceObject
    {
        $orderList = $this->resource->get(self::$orderListHref);

        $this->assertSame(Code::OK, $orderList->code);

        return $orderList;
    }

    #[Alps('goOrder')]
    #[Depends('testAdminOrderListAfterMemberCheckout')]
    public function testAdminOrderForMemberPurchase(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrder', ['orderNo' => self::$memberOrderNo]);
    }

    #[Alps('goOrderShippingAddress')]
    #[Depends('testAdminOrderForMemberPurchase')]
    public function testAdminOrderShippingAddressForMemberPurchase(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderShippingAddress', ['orderNo' => self::$memberOrderNo]);
    }

    #[Alps('doUpdateOrderShippingAddress')]
    #[Depends('testAdminOrderShippingAddressForMemberPurchase')]
    public function testAdminUpdatesShippingAddressForMemberPurchase(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateOrderShippingAddress'), [
            'orderNo' => self::$memberOrderNo,
            'name01' => '履歴',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '履歴1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$memberOrderNo, $this->bodyValue($updated, 'orderNo'));

        return $updated;
    }

    #[Alps('doUpdateTrackingNumber')]
    #[Depends('testAdminUpdatesShippingAddressForMemberPurchase')]
    public function testAdminUpdatesTrackingNumberForMemberPurchase(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateTrackingNumber'), [
            'orderNo' => self::$memberOrderNo,
            'trackingNumber' => 'TRK-MEMBER-HISTORY',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('OK', $this->bodyValue($updated, 'status'));
        $this->assertSame(self::$memberOrderNo, $this->bodyValue($updated, 'orderNo'));

        return $updated;
    }

    #[Alps('goOrderMail')]
    #[Depends('testAdminUpdatesTrackingNumberForMemberPurchase')]
    public function testAdminOrderMailForMemberPurchase(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderMail', ['orderNo' => self::$memberOrderNo]);
    }

    #[Alps('goOrderMailConfirm')]
    #[Depends('testAdminOrderMailForMemberPurchase')]
    public function testAdminOrderMailConfirmForMemberPurchase(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderMailConfirm', ['orderNo' => self::$memberOrderNo]);
    }

    #[Alps('doSendOrderMail')]
    #[Depends('testAdminOrderMailConfirmForMemberPurchase')]
    public function testAdminSendsOrderMailForMemberPurchase(ResourceObject $response): ResourceObject
    {
        $sent = $this->resource->post($this->linkHref($response, 'doSendOrderMail'), [
            'orderNo' => self::$memberOrderNo,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $sent->code);
        $this->assertSame(self::$memberOrderNo, $this->bodyValue($sent, 'orderNo'));

        return $sent;
    }

    #[Alps('goMypage')]
    #[Depends('testAdminSendsOrderMailForMemberPurchase')]
    public function testMemberMypageAfterAdminOrderMail(ResourceObject $response): ResourceObject
    {
        $mypage = $this->resource->get(self::$mypageHref);

        $this->assertSame(Code::OK, $mypage->code);

        return $mypage;
    }

    #[Alps('goOrderHistory')]
    #[Depends('testMemberMypageAfterAdminOrderMail')]
    public function testMemberOrderHistory(ResourceObject $response): ResourceObject
    {
        $history = $this->follow($response, 'goOrderHistory');

        $this->assertGreaterThan(0, $this->bodyValue($history, 'orderCount'));

        return $history;
    }

    #[Alps('goMypageHistory')]
    #[Depends('testMemberOrderHistory')]
    public function testMemberOrderHistoryDetail(ResourceObject $response): ResourceObject
    {
        $detail = $this->follow($response, 'goMypageHistory', ['orderNo' => self::$memberOrderNo]);

        $this->assertSame(self::$memberOrderNo, $this->bodyValue($detail, 'orderNo'));

        return $detail;
    }

    #[Alps('doReorder')]
    #[Depends('testMemberOrderHistoryDetail')]
    public function testReordersMemberOrder(ResourceObject $response): ResourceObject
    {
        $reordered = $this->resource->post($this->linkHref($response, 'doReorder'), [
            'orderNo' => self::$memberOrderNo,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $reordered->code);
        $this->assertGreaterThan(0, $this->bodyValue($reordered, 'addedCount'));

        return $reordered;
    }

    #[Alps('goCart')]
    #[Depends('testReordersMemberOrder')]
    public function testCartContainsReorderedItems(ResourceObject $response): void
    {
        $cart = $this->followLocation($response, '/cart');

        $this->assertGreaterThan(0, $this->bodyValue($cart, 'cartCount'));
    }
}
