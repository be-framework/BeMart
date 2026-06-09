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

class FlowCustomerPurchaseTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-purchase';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';
    private const SESSION_PREFIX = 'session-prefix-1_1';

    private static string $productCode;
    private static string $productName;
    private static string $paymentId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$productCode = 'workflow-purchase-' . bin2hex(random_bytes(4));
        self::$productName = 'Workflow Purchase Product ' . self::$productCode;
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

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        $payment = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'Workflow Purchase Payment ' . bin2hex(random_bytes(4)),
            'charge' => 0,
            'ruleMin' => null,
            'ruleMax' => null,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $payment->code);
        self::$paymentId = $this->bodyString($payment, 'paymentId');

        $created = $this->resource->post('page://self/admin/product', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 1234,
            'stock' => 9,
            'productStatus' => 1,
            'description' => 'DB-backed workflow purchase product.',
            'searchWord' => 'workflow purchase product',
            'note' => 'Created as flow-customer-purchase precondition.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $created->code);

        $response = $this->resource->get('page://self/');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('goProductList')]
    #[Depends('testIndex')]
    public function testProductList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$productName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goProduct')]
    #[Depends('testProductList')]
    public function testProductDetail(ResourceObject $response): ResourceObject
    {
        $product = $this->follow($response, 'goProduct', ['productCode' => self::$productCode]);

        $this->assertSame(self::$productCode, $this->bodyValue($product, 'productCode'));
        $this->assertSame(self::$productName, $this->bodyValue($product, 'productName'));

        return $product;
    }

    #[Alps('doAddCartItem')]
    #[Depends('testProductDetail')]
    public function testAddsCartItem(ResourceObject $response): ResourceObject
    {
        $added = $this->resource->post('page://self/cart/item', [
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

    #[Alps('goShoppingNonMember')]
    #[Depends('testCart')]
    public function testNonMemberForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goShoppingNonMember');
    }

    #[Alps('doSubmitNonMember')]
    #[Depends('testNonMemberForm')]
    public function testSubmitsNonMember(ResourceObject $response): ResourceObject
    {
        $submitted = $this->resource->post('page://self/shopping/non-member', [
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
        $confirmed = $this->resource->get('page://self/shopping/confirm', [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'paymentMethodId' => (int) self::$paymentId,
        ]);

        $this->assertSame(Code::OK, $confirmed->code);
        $this->assertSame($this->bodyValue($response, 'preOrderId'), $this->bodyValue($confirmed, 'preOrderId'));

        return $confirmed;
    }

    #[Alps('doCheckout')]
    #[Depends('testConfirmsOrder')]
    public function testChecksOut(ResourceObject $response): ResourceObject
    {
        $checkedOut = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => $this->bodyValue($response, 'preOrderId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $checkedOut->code);
        $this->assertIsString($this->bodyValue($checkedOut, 'orderNo'));

        return $checkedOut;
    }

    #[Alps('ShoppingComplete')]
    #[Depends('testChecksOut')]
    public function testShoppingComplete(ResourceObject $response): void
    {
        $complete = $this->followLocation($response);

        $this->assertSame($this->bodyValue($response, 'orderNo'), $this->bodyValue($complete, 'orderNo'));
    }
}
