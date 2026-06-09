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

class FlowAdminShopConfigurationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-shop-configuration';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';

    private static string $paymentName;
    private static string $deliveryName;
    private static string $taxApplyDate;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$paymentName = 'Workflow Payment ' . $suffix;
        self::$deliveryName = 'Workflow Delivery ' . $suffix;
        self::$taxApplyDate = '2027-01-' . (string) (10 + (int) (hexdec($suffix[0]) % 9));
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
    public function testBaseInfo(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doUpdateBaseInfo')]
    #[Depends('testBaseInfo')]
    public function testUpdatesBaseInfo(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/admin/base-info', [
            'shopName' => 'Workflow Shop Configuration',
            'shopKana' => 'ワークフローショップ',
            'shopNameEng' => 'Workflow Shop',
            'companyName' => 'Workflow Company',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'phoneNumber' => '0312345678',
            'businessHour' => '10:00-18:00',
            'shopEmail01' => 'workflow-shop@example.com',
            'shopMessage' => 'Updated by flow-admin-shop-configuration.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('Workflow Shop Configuration', $this->bodyValue($updated, 'shopName'));

        return $updated;
    }

    #[Alps('goPaymentList')]
    #[Depends('testUpdatesBaseInfo')]
    public function testPaymentList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goPaymentList');
    }

    #[Alps('doCreatePayment')]
    #[Depends('testPaymentList')]
    public function testCreatesPayment(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => self::$paymentName,
            'charge' => 220,
            'ruleMin' => 0,
            'ruleMax' => 999999,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$paymentName, $this->bodyValue($created, 'paymentMethodName'));

        return $created;
    }

    #[Alps('doUpdatePayment')]
    #[Depends('testCreatesPayment')]
    public function testUpdatesPayment(ResourceObject $response): ResourceObject
    {
        $paymentId = $this->bodyValue($response, 'paymentId');
        $this->assertIsString($paymentId);

        $updated = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => $paymentId,
            'paymentMethodName' => self::$paymentName . ' Updated',
            'charge' => 330,
            'ruleMin' => 100,
            'ruleMax' => 888888,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($paymentId, $this->bodyValue($updated, 'paymentId'));
        $this->assertSame(self::$paymentName . ' Updated', $this->bodyValue($updated, 'paymentMethodName'));

        return $updated;
    }

    #[Alps('doDeletePayment')]
    #[Depends('testUpdatesPayment')]
    public function testDeletesPayment(ResourceObject $response): ResourceObject
    {
        $paymentId = $this->bodyValue($response, 'paymentId');
        $this->assertIsString($paymentId);

        $deleted = $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $paymentId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($paymentId, $this->bodyValue($deleted, 'paymentId'));

        return $deleted;
    }

    #[Alps('goDeliveryList')]
    #[Depends('testDeletesPayment')]
    public function testDeliveryList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goDeliveryList');
    }

    #[Alps('doCreateDelivery')]
    #[Depends('testDeliveryList')]
    public function testCreatesDelivery(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => self::$deliveryName,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$deliveryName, $this->bodyValue($created, 'deliveryName'));

        return $created;
    }

    #[Alps('doUpdateDelivery')]
    #[Depends('testCreatesDelivery')]
    public function testUpdatesDelivery(ResourceObject $response): ResourceObject
    {
        $deliveryId = $this->bodyValue($response, 'deliveryId');
        $this->assertIsString($deliveryId);

        $updated = $this->resource->put('page://self/admin/delivery/delivery', [
            'deliveryId' => $deliveryId,
            'deliveryName' => self::$deliveryName . ' Updated',
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($deliveryId, $this->bodyValue($updated, 'deliveryId'));
        $this->assertSame(self::$deliveryName . ' Updated', $this->bodyValue($updated, 'deliveryName'));

        return $updated;
    }

    #[Alps('doDeleteDelivery')]
    #[Depends('testUpdatesDelivery')]
    public function testDeletesDelivery(ResourceObject $response): ResourceObject
    {
        $deliveryId = $this->bodyValue($response, 'deliveryId');
        $this->assertIsString($deliveryId);

        $deleted = $this->resource->delete('page://self/admin/delivery/delivery', [
            'deliveryId' => $deliveryId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($deliveryId, $this->bodyValue($deleted, 'deliveryId'));

        return $deleted;
    }

    #[Alps('goTaxRuleList')]
    #[Depends('testDeletesDelivery')]
    public function testTaxRuleList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTaxRuleList');
    }

    #[Alps('doCreateTaxRule')]
    #[Depends('testTaxRuleList')]
    public function testCreatesTaxRule(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 9.5,
            'applyDate' => self::$taxApplyDate,
            'roundingType' => 1,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$taxApplyDate, $this->bodyValue($created, 'applyDate'));

        return $created;
    }

    #[Alps('doDeleteTaxRule')]
    #[Depends('testCreatesTaxRule')]
    public function testDeletesTaxRule(ResourceObject $response): ResourceObject
    {
        $taxRuleId = $this->bodyValue($response, 'taxRuleId');
        $this->assertIsString($taxRuleId);

        $deleted = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => $taxRuleId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($taxRuleId, $this->bodyValue($deleted, 'taxRuleId'));

        return $deleted;
    }

    #[Alps('goCalendar')]
    #[Depends('testDeletesTaxRule')]
    public function testCalendar(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCalendar');
    }

    #[Alps('doCreateCalendarHoliday')]
    #[Depends('testCalendar')]
    public function testCreatesCalendarHoliday(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/calendar', [
            'operation' => 'create',
            'title' => 'Workflow Holiday',
            'holiday' => '2027-02-11',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame('doCreateCalendarHoliday', $this->bodyValue($created, 'transitionId'));

        return $created;
    }

    #[Alps('doUpdateCalendar')]
    #[Depends('testCreatesCalendarHoliday')]
    public function testUpdatesCalendar(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/admin/calendar', [
            'operation' => 'update',
            'title' => 'Workflow Holiday Updated',
            'holiday' => '2027-02-12',
            'calendarId' => 1,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateCalendar', $this->bodyValue($updated, 'transitionId'));

        return $updated;
    }

    #[Alps('doDeleteCalendarHoliday')]
    #[Depends('testUpdatesCalendar')]
    public function testDeletesCalendarHoliday(ResourceObject $response): void
    {
        $deleted = $this->resource->delete('page://self/admin/calendar', [
            'calendarId' => 1,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame('doDeleteCalendarHoliday', $this->bodyValue($deleted, 'transitionId'));
    }
}
