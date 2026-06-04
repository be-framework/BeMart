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

class FlowAdminShopConfigurationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-shop-configuration';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goBaseInfo')]
    public function testBaseInfo(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration open base info.');
    }

    #[Alps('doUpdateBaseInfo')]
    #[Depends('testBaseInfo')]
    public function testUpdatesBaseInfo(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration update base info.');
    }

    #[Alps('goPaymentList')]
    #[Depends('testUpdatesBaseInfo')]
    public function testPaymentList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration open payment list.');
    }

    #[Alps('doCreatePayment')]
    #[Depends('testPaymentList')]
    public function testCreatesPayment(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration create payment method.');
    }

    #[Alps('doUpdatePayment')]
    #[Depends('testCreatesPayment')]
    public function testUpdatesPayment(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration update payment method.');
    }

    #[Alps('doDeletePayment')]
    #[Depends('testUpdatesPayment')]
    public function testDeletesPayment(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration delete payment method.');
    }

    #[Alps('goDeliveryList')]
    #[Depends('testDeletesPayment')]
    public function testDeliveryList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration open delivery list.');
    }

    #[Alps('doCreateDelivery')]
    #[Depends('testDeliveryList')]
    public function testCreatesDelivery(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration create delivery method.');
    }

    #[Alps('doUpdateDelivery')]
    #[Depends('testCreatesDelivery')]
    public function testUpdatesDelivery(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration update delivery method.');
    }

    #[Alps('doDeleteDelivery')]
    #[Depends('testUpdatesDelivery')]
    public function testDeletesDelivery(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration delete delivery method.');
    }

    #[Alps('goTaxRuleList')]
    #[Depends('testDeletesDelivery')]
    public function testTaxRuleList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration open tax rule list.');
    }

    #[Alps('doCreateTaxRule')]
    #[Depends('testTaxRuleList')]
    public function testCreatesTaxRule(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration create tax rule.');
    }

    #[Alps('doDeleteTaxRule')]
    #[Depends('testCreatesTaxRule')]
    public function testDeletesTaxRule(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration delete tax rule.');
    }

    #[Alps('goCalendar')]
    #[Depends('testDeletesTaxRule')]
    public function testCalendar(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration open calendar.');
    }

    #[Alps('doCreateCalendarHoliday')]
    #[Depends('testCalendar')]
    public function testCreatesCalendarHoliday(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration create calendar holiday.');
    }

    #[Alps('doUpdateCalendar')]
    #[Depends('testCreatesCalendarHoliday')]
    public function testUpdatesCalendar(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration update calendar.');
    }

    #[Alps('doDeleteCalendarHoliday')]
    #[Depends('testUpdatesCalendar')]
    public function testDeletesCalendarHoliday(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-shop-configuration delete calendar holiday.');
    }
}
