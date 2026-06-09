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
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowTestSession;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowCustomerAccountMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-account-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-account-csrf-token';
    private const PASSWORD = 'workflow-account-password-2026';
    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $email;
    private static string $customerId;
    private static string $productCode;
    private static string $productName;
    private static WorkflowTestSession|null $session = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$email = 'workflow-account-' . $suffix . '@example.com';
        self::$productCode = 'workflow-account-' . $suffix;
        self::$productName = 'Workflow Account Favorite Product ' . self::$productCode;
        self::$session = WorkflowTestSession::fromCurrent();
        self::$session->loginAsAdmin(self::ADMIN_ID, self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();

        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        self::$session?->restore();

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;
        self::$session = null;

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

    #[Alps('goLogin')]
    public function testLoginForm(): ResourceObject
    {
        $registered = $this->resource->post('page://self/entry', [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => 'Account',
            'name02' => 'Customer',
            'kana01' => 'アカウント',
            'kana02' => 'カスタマー',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $registered->code);
        $this->assertIsString($registered->body['customerId'] ?? null);

        self::$customerId = (string) $registered->body['customerId'];
        assert(self::$session instanceof WorkflowTestSession);
        self::$session->setCustomerId(self::$customerId);

        $created = $this->resource->post('page://self/admin/product', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 2345,
            'stock' => 7,
            'productStatus' => 1,
            'description' => 'DB-backed workflow account favorite product.',
            'searchWord' => 'workflow account favorite product',
            'note' => 'Created as flow-customer-account-maintenance precondition.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $created->code);

        $response = $this->resource->get('page://self/login');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doLogin')]
    #[Depends('testLoginForm')]
    public function testLogsIn(ResourceObject $response): ResourceObject
    {
        $loggedIn = $this->resource->post('page://self/login', [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedIn->code);
        $this->assertSame(self::$email, $this->bodyValue($loggedIn, 'email'));
        assert(self::$session instanceof WorkflowTestSession);
        self::$session->setCustomerId((string) $this->bodyValue($loggedIn, 'customerId'));

        return $loggedIn;
    }

    #[Alps('goMypage')]
    #[Depends('testLogsIn')]
    public function testMypage(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMypage');
    }

    #[Alps('goMypageChange')]
    #[Depends('testMypage')]
    public function testChangeForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMypageChange');
    }

    #[Alps('doUpdateCustomer')]
    #[Depends('testChangeForm')]
    public function testUpdatesCustomer(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/mypage/change', [
            'email' => self::$email,
            'name01' => 'Account',
            'name02' => 'Customer Updated',
            'kana01' => 'アカウント',
            'kana02' => 'カスタマー',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-2',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$email, $this->bodyValue($updated, 'email'));

        return $updated;
    }

    #[Alps('MypageChangeComplete')]
    #[Depends('testUpdatesCustomer')]
    public function testChangeComplete(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMypageChangeComplete');
    }

    #[Alps('goCustomerAddressList')]
    #[Depends('testChangeComplete')]
    public function testAddressList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerAddressList');
    }

    #[Alps('doCreateCustomerAddress')]
    #[Depends('testAddressList')]
    public function testCreatesAddress(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '配送',
            'name02' => '太郎',
            'kana01' => 'ハイソウ',
            'kana02' => 'タロウ',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertIsString($this->bodyValue($created, 'addressId'));

        return $created;
    }

    #[Alps('doUpdateCustomerAddress')]
    #[Depends('testCreatesAddress')]
    public function testUpdatesAddress(ResourceObject $response): ResourceObject
    {
        $addressId = $this->bodyValue($response, 'addressId');
        $this->assertIsString($addressId);

        $updated = $this->resource->put('page://self/mypage/address', [
            'addressId' => $addressId,
            'name01' => '配送',
            'name02' => '次郎',
            'kana01' => 'ハイソウ',
            'kana02' => 'ジロウ',
            'postalCode' => '1500002',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー2-2-2',
            'phoneNumber' => '0312345678',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($addressId, $this->bodyValue($updated, 'addressId'));

        return $updated;
    }

    #[Alps('doDeleteCustomerAddress')]
    #[Depends('testUpdatesAddress')]
    public function testDeletesAddress(ResourceObject $response): ResourceObject
    {
        $addressId = $this->bodyValue($response, 'addressId');
        $this->assertIsString($addressId);

        $deleted = $this->resource->delete('page://self/mypage/address', [
            'addressId' => $addressId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($addressId, $this->bodyValue($deleted, 'addressId'));

        return $deleted;
    }

    #[Alps('goFavoriteList')]
    #[Depends('testDeletesAddress')]
    public function testFavoriteList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goFavoriteList');
    }

    #[Alps('doAddFavorite')]
    #[Depends('testFavoriteList')]
    public function testAddsFavorite(ResourceObject $response): ResourceObject
    {
        $favorite = $this->resource->post('page://self/mypage/favorite', [
            'productCode' => self::$productCode,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $favorite->code);
        $this->assertSame(self::$productCode, $this->bodyValue($favorite, 'productCode'));

        return $favorite;
    }

    #[Alps('doRemoveFavorite')]
    #[Depends('testAddsFavorite')]
    public function testRemovesFavorite(ResourceObject $response): ResourceObject
    {
        $removed = $this->resource->delete('page://self/mypage/favorite', [
            'productCode' => self::$productCode,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $removed->code);
        $this->assertSame(self::$productCode, $this->bodyValue($removed, 'productCode'));

        return $removed;
    }

    #[Alps('goMypageWithdraw')]
    #[Depends('testRemovesFavorite')]
    public function testWithdrawForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMypageWithdraw');
    }

    #[Alps('doWithdrawCustomer')]
    #[Depends('testWithdrawForm')]
    public function testWithdrawsCustomer(ResourceObject $response): ResourceObject
    {
        $withdrawn = $this->resource->post('page://self/mypage/withdraw', [
            'sessionPrefix' => 'workflow-account',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $withdrawn->code);
        $this->assertSame(self::$customerId, $this->bodyValue($withdrawn, 'customerId'));

        return $withdrawn;
    }

    #[Alps('MypageWithdrawComplete')]
    #[Depends('testWithdrawsCustomer')]
    public function testWithdrawComplete(ResourceObject $response): void
    {
        $complete = $this->follow($response, 'goMypageWithdrawComplete');

        $this->assertSame(Code::OK, $complete->code);
    }
}
