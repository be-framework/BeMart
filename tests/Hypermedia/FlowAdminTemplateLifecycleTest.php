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

class FlowAdminTemplateLifecycleTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-template-lifecycle';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goTemplateList')]
    public function testTemplateList(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle open template list.');
    }

    #[Alps('goTemplateInstall')]
    #[Depends('testTemplateList')]
    public function testTemplateInstall(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle open template install.');
    }

    #[Alps('doInstallTemplate')]
    #[Depends('testTemplateInstall')]
    public function testInstallsTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle install template.');
    }

    #[Alps('doSelectTemplate')]
    #[Depends('testInstallsTemplate')]
    public function testSelectsTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle select template.');
    }

    #[Alps('doDownloadTemplate')]
    #[Depends('testSelectsTemplate')]
    public function testDownloadsTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle download template.');
    }

    #[Alps('doDeleteTemplate')]
    #[Depends('testDownloadsTemplate')]
    public function testDeletesTemplate(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle delete template.');
    }

    #[Alps('goTemplateList')]
    #[Depends('testDeletesTemplate')]
    public function testReturnsToTemplateList(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-template-lifecycle verify template list after delete.');
    }
}
