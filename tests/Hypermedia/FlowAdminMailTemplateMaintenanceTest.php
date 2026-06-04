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

class FlowAdminMailTemplateMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-mail-template-maintenance';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goMailTemplateList')]
    public function testMailTemplateList(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance open mail template list.');
    }

    #[Alps('doUpdateMailTemplate')]
    #[Depends('testMailTemplateList')]
    public function testUpdatesMailTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance update mail template.');
    }

    #[Alps('goOrderMail')]
    #[Depends('testUpdatesMailTemplate')]
    public function testOrderMail(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance open order mail.');
    }

    #[Alps('goOrderMailConfirm')]
    #[Depends('testOrderMail')]
    public function testOrderMailConfirm(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance confirm order mail.');
    }

    #[Alps('doSendOrderMail')]
    #[Depends('testOrderMailConfirm')]
    public function testSendsOrderMail(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance send order mail.');
    }

    #[Alps('MailHistory')]
    #[Depends('testSendsOrderMail')]
    public function testMailHistory(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance verify mail history evidence.');
    }

    #[Alps('doDeleteMailTemplate')]
    #[Depends('testMailHistory')]
    public function testDeletesMailTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance delete temporary mail template.');
    }

    #[Alps('goMailTemplateList')]
    #[Depends('testDeletesMailTemplate')]
    public function testReturnsToMailTemplateList(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-mail-template-maintenance return to mail template list.');
    }
}
