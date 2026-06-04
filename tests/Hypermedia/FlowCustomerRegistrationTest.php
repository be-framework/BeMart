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

class FlowCustomerRegistrationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-registration';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration open storefront top.');
    }

    #[Alps('goCustomerRegistration')]
    #[Depends('testIndex')]
    public function testRegistrationForm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration open registration form.');
    }

    #[Alps('goCustomerRegistrationConfirm')]
    #[Depends('testRegistrationForm')]
    public function testRegistrationConfirm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration open registration confirmation.');
    }

    #[Alps('doRegisterCustomer')]
    #[Depends('testRegistrationConfirm')]
    public function testRegistersCustomer(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration register customer.');
    }

    #[Alps('CustomerRegistrationComplete')]
    #[Depends('testRegistersCustomer')]
    public function testRegistrationComplete(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration verify registration completion.');
    }

    #[Alps('doActivateCustomer')]
    #[Depends('testRegistrationComplete')]
    public function testActivatesCustomer(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration activate registered customer.');
    }

    #[Alps('doLogin')]
    #[Depends('testActivatesCustomer')]
    public function testLogsIn(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-registration login as registered customer.');
    }

    #[Alps('Mypage')]
    #[Depends('testLogsIn')]
    public function testMypage(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-customer-registration verify registered customer mypage.');
    }
}
