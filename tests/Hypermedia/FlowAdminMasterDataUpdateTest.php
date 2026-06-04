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

class FlowAdminMasterDataUpdateTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-master-data-update';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goMasterData')]
    public function testMasterData(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-master-data-update open master data.');
    }

    #[Alps('doSelectMasterData')]
    #[Depends('testMasterData')]
    public function testSelectsMasterData(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-master-data-update select master data.');
    }

    #[Alps('doUpdateMasterData')]
    #[Depends('testSelectsMasterData')]
    public function testUpdatesMasterData(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-master-data-update update master data.');
    }

    #[Alps('goMasterData')]
    #[Depends('testUpdatesMasterData')]
    public function testReadsUpdatedMasterData(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-master-data-update read updated master data.');
    }
}
