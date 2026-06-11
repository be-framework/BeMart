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

class FlowAdminCustomerMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-customer-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-admin-customer-csrf-token';
    private const PASSWORD = 'workflow-admin-customer-password-2026';

    private static string $email;
    private static string $customerId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = 'workflow-admin-customer-' . bin2hex(random_bytes(4)) . '@example.com';
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

    #[Alps('goCustomerList')]
    public function testCustomerList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateCustomer')]
    #[Depends('testCustomerList')]
    public function testCreatesCustomer(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateCustomer'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => '管理',
            'name02' => '顧客',
            'kana01' => 'カンリ',
            'kana02' => 'コキャク',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '管理1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$email, $this->bodyValue($created, 'email'));
        $this->assertSame(2, $this->bodyValue($created, 'customerStatus'));
        self::$customerId = $this->bodyString($created, 'customerId');

        return $created;
    }

    #[Alps('goCustomer')]
    #[Depends('testCreatesCustomer')]
    public function testReadsCreatedCustomer(ResourceObject $response): ResourceObject
    {
        $customer = $this->followLocation($response);

        $this->assertSame(self::$customerId, $this->bodyValue($customer, 'customerId'));
        $this->assertSame(self::$email, $this->bodyValue($customer, 'email'));

        return $customer;
    }

    #[Alps('goCustomerList')]
    #[Depends('testReadsCreatedCustomer')]
    public function testSearchesCreatedCustomer(ResourceObject $response): ResourceObject
    {
        $list = $this->follow($response, 'goCustomerList', ['emailKeyword' => self::$email]);
        $customers = $this->bodyValue($list, 'customers');
        $this->assertIsArray($customers);
        $this->assertContainsCustomer(self::$customerId, self::$email, $customers);

        return $list;
    }

    #[Alps('doDeleteCustomer')]
    #[Depends('testSearchesCreatedCustomer')]
    public function testDeletesCustomer(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->post($this->linkHref($response, 'doDeleteCustomer'), [
            'customerId' => self::$customerId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame(self::$customerId, $this->bodyValue($deleted, 'customerId'));
        $this->assertSame(self::$email, $this->bodyValue($deleted, 'originalEmail'));
        $this->assertFalse($this->bodyValue($deleted, 'alreadyDeleted'));

        return $deleted;
    }

    #[Alps('goCustomerList')]
    #[Depends('testDeletesCustomer')]
    public function testReturnsToCustomerList(ResourceObject $response): void
    {
        $list = $this->follow($response, 'goCustomerList');

        $this->assertIsArray($this->bodyValue($list, 'customers'));
    }

    /**
     * @param mixed $customers
     */
    private function assertContainsCustomer(string $customerId, string $email, mixed $customers): void
    {
        $this->assertIsArray($customers);
        foreach ($customers as $customer) {
            if (! is_array($customer)) {
                continue;
            }

            if (($customer['customerId'] ?? null) !== $customerId) {
                continue;
            }

            $this->assertSame($email, $customer['email'] ?? null);

            return;
        }

        $this->fail('Created customer should be visible from the admin customer list.');
    }
}
