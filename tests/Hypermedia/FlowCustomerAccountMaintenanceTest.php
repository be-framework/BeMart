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

class FlowCustomerAccountMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-account-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-account-csrf-token';
    private const PASSWORD = 'workflow-account-password-2026';
    private static string $email;
    private static string $customerId;
    private static string $productCode;
    private static string $productName;
    private static string $updatedProductName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$email = 'workflow-account-' . $suffix . '@example.com';
        self::$productCode = 'workflow-account-' . $suffix;
        self::$productName = 'Workflow Account Favorite Product ' . self::$productCode;
        self::$updatedProductName = 'Workflow Account Favorite Published ' . self::$productCode;
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
        return $this->follow($response, 'goProductList');
    }

    #[Alps('doCreateProduct')]
    #[Depends('testAdminProductList')]
    public function testCreatesProduct(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateProduct'), [
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
    public function testPublishesProduct(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$updatedProductName,
            'price02' => 2345,
            'stock' => 7,
            'productStatus' => 1,
            'description' => 'Published DB-backed workflow account favorite product.',
            'searchWord' => 'workflow account favorite published',
            'note' => 'Published as flow-customer-account-maintenance precondition.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$updatedProductName, $this->bodyValue($updated, 'productName'));

        return $updated;
    }

    #[Alps('goProductList')]
    #[Depends('testPublishesProduct')]
    public function testStorefrontProductList(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goProductList', ['nameKeyword' => self::$updatedProductName]);

        $this->assertSame(1, $this->bodyValue($list, 'totalItemCount'));

        return $list;
    }

    #[Alps('goTop')]
    #[Depends('testStorefrontProductList')]
    public function testReturnsTopForRegistration(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTop');
    }

    #[Alps('goCustomerRegistration')]
    #[Depends('testReturnsTopForRegistration')]
    public function testRegistrationForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistration');
    }

    #[Alps('goCustomerRegistrationConfirm')]
    #[Depends('testRegistrationForm')]
    public function testRegistrationConfirm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistrationConfirm');
    }

    #[Alps('doRegisterCustomer')]
    #[Depends('testRegistrationConfirm')]
    public function testRegistersCustomer(ResourceObject $response): ResourceObject
    {
        $registered = $this->resource->post($this->linkHref($response, 'doRegisterCustomer'), [
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
        self::$customerId = $this->bodyString($registered, 'customerId');

        return $registered;
    }

    #[Alps('CustomerRegistrationComplete')]
    #[Depends('testRegistersCustomer')]
    public function testRegistrationComplete(ResourceObject $response): ResourceObject
    {
        return $this->followLocation($response);
    }

    #[Alps('goTop')]
    #[Depends('testRegistrationComplete')]
    public function testReturnsTopForLogin(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTop');
    }

    #[Alps('goLogin')]
    #[Depends('testReturnsTopForLogin')]
    public function testLoginForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goLogin');
    }

    #[Alps('doLogin')]
    #[Depends('testLoginForm')]
    public function testLogsIn(ResourceObject $response): ResourceObject
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
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateCustomer'), [
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
        $created = $this->resource->post($this->linkHref($response, 'doCreateCustomerAddress'), [
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

        $addressList = $this->follow($response, 'goCustomerAddressList');
        $updated = $this->resource->put($this->linkHref($addressList, 'doUpdateCustomerAddress'), [
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

        $addressList = $this->follow($response, 'goCustomerAddressList');
        $deleted = $this->resource->delete($this->linkHref($addressList, 'doDeleteCustomerAddress'), [
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
        $favorite = $this->resource->post($this->linkHref($response, 'doAddFavorite'), [
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
        $removed = $this->resource->delete($this->linkHref($response, 'doRemoveFavorite'), [
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
        $withdrawn = $this->resource->post($this->linkHref($response, 'doWithdrawCustomer'), [
            'sessionPrefix' => 'workflow-account',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $withdrawn->code);
        $this->assertSame(self::$customerId, $this->bodyValue($withdrawn, 'customerId'));

        return $withdrawn;
    }

    #[Alps('MypageWithdrawComplete')]
    #[Depends('testWithdrawsCustomer')]
    public function testWithdrawComplete(ResourceObject $response): ResourceObject
    {
        $complete = $this->follow($response, 'goMypageWithdrawComplete');

        $this->assertSame(Code::OK, $complete->code);

        return $complete;
    }

    #[Alps('doLogout')]
    #[Depends('testWithdrawComplete')]
    public function testLogsOut(ResourceObject $response): void
    {
        $top = $this->follow($response, 'goTop');

        $loggedOut = $this->resource->post($this->linkHref($top, 'doLogout'), [
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $loggedOut->code);
    }
}
