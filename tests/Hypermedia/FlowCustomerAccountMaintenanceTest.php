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

class FlowCustomerAccountMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-account-maintenance';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goLogin')]
    public function testLoginForm(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open login form.');
    }

    #[Alps('doLogin')]
    #[Depends('testLoginForm')]
    public function testLogsIn(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance login customer.');
    }

    #[Alps('goMypage')]
    #[Depends('testLogsIn')]
    public function testMypage(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open mypage.');
    }

    #[Alps('goMypageChange')]
    #[Depends('testMypage')]
    public function testChangeForm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open profile change form.');
    }

    #[Alps('doUpdateCustomer')]
    #[Depends('testChangeForm')]
    public function testUpdatesCustomer(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance update customer profile.');
    }

    #[Alps('MypageChangeComplete')]
    #[Depends('testUpdatesCustomer')]
    public function testChangeComplete(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance verify profile change completion.');
    }

    #[Alps('goCustomerAddressList')]
    #[Depends('testChangeComplete')]
    public function testAddressList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open address list.');
    }

    #[Alps('doCreateCustomerAddress')]
    #[Depends('testAddressList')]
    public function testCreatesAddress(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance create customer address.');
    }

    #[Alps('doUpdateCustomerAddress')]
    #[Depends('testCreatesAddress')]
    public function testUpdatesAddress(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance update customer address.');
    }

    #[Alps('doDeleteCustomerAddress')]
    #[Depends('testUpdatesAddress')]
    public function testDeletesAddress(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance delete customer address.');
    }

    #[Alps('goFavoriteList')]
    #[Depends('testDeletesAddress')]
    public function testFavoriteList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open favorite list.');
    }

    #[Alps('doAddFavorite')]
    #[Depends('testFavoriteList')]
    public function testAddsFavorite(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance add favorite product.');
    }

    #[Alps('doRemoveFavorite')]
    #[Depends('testAddsFavorite')]
    public function testRemovesFavorite(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance remove favorite product.');
    }

    #[Alps('goMypageWithdraw')]
    #[Depends('testRemovesFavorite')]
    public function testWithdrawForm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance open withdraw form.');
    }

    #[Alps('doWithdrawCustomer')]
    #[Depends('testWithdrawForm')]
    public function testWithdrawsCustomer(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance withdraw customer.');
    }

    #[Alps('MypageWithdrawComplete')]
    #[Depends('testWithdrawsCustomer')]
    public function testWithdrawComplete(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-customer-account-maintenance verify withdraw completion.');
    }
}
