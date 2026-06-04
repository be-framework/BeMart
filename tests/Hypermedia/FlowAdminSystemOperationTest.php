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

class FlowAdminSystemOperationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-system-operation';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goAdminLogin')]
    public function testAdminLoginForm(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open admin login form.');
    }

    #[Alps('doAdminLogin')]
    #[Depends('testAdminLoginForm')]
    public function testAdminLogsIn(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation login admin.');
    }

    #[Alps('goAdminTop')]
    #[Depends('testAdminLogsIn')]
    public function testAdminTop(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open admin top.');
    }

    #[Alps('goMemberList')]
    #[Depends('testAdminTop')]
    public function testMemberList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open member list.');
    }

    #[Alps('doCreateMember')]
    #[Depends('testMemberList')]
    public function testCreatesMember(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation create member.');
    }

    #[Alps('goMember')]
    #[Depends('testCreatesMember')]
    public function testMember(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation read member.');
    }

    #[Alps('doUpdateMember')]
    #[Depends('testMember')]
    public function testUpdatesMember(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation update member.');
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testUpdatesMember')]
    public function testUpdatesAuthorityRole(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation update authority role.');
    }

    #[Alps('goLoginHistoryList')]
    #[Depends('testUpdatesAuthorityRole')]
    public function testLoginHistoryList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open login history list.');
    }

    #[Alps('goSecurity')]
    #[Depends('testLoginHistoryList')]
    public function testSecurity(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open security settings.');
    }

    #[Alps('doUpdateSecurity')]
    #[Depends('testSecurity')]
    public function testUpdatesSecurity(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation update security settings.');
    }

    #[Alps('goTwoFactorAuthSet')]
    #[Depends('testUpdatesSecurity')]
    public function testTwoFactorAuthSet(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open two factor auth setup.');
    }

    #[Alps('doSetTwoFactorAuth')]
    #[Depends('testTwoFactorAuthSet')]
    public function testSetsTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation set two factor auth.');
    }

    #[Alps('goTwoFactorAuth')]
    #[Depends('testSetsTwoFactorAuth')]
    public function testTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open two factor auth verification.');
    }

    #[Alps('doVerifyTwoFactorAuth')]
    #[Depends('testTwoFactorAuth')]
    public function testVerifiesTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation verify two factor auth.');
    }

    #[Alps('goContentCache')]
    #[Depends('testVerifiesTwoFactorAuth')]
    public function testContentCache(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open content cache.');
    }

    #[Alps('doClearCache')]
    #[Depends('testContentCache')]
    public function testClearsCache(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation clear cache.');
    }

    #[Alps('goMaintenance')]
    #[Depends('testClearsCache')]
    public function testMaintenance(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open maintenance settings.');
    }

    #[Alps('doToggleMaintenance')]
    #[Depends('testMaintenance')]
    public function testTogglesMaintenance(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation toggle maintenance.');
    }

    #[Alps('goSystemInfo')]
    #[Depends('testTogglesMaintenance')]
    public function testSystemInfo(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation open system info.');
    }

    #[Alps('doAdminLogout')]
    #[Depends('testSystemInfo')]
    public function testAdminLogsOut(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-system-operation logout admin.');
    }
}
