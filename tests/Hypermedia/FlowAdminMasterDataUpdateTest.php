<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;

use function assert;

class FlowAdminMasterDataUpdateTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-master-data-update';

    private const MASTER_TYPE = 'tag';
    private const UPDATED_ROWS = [
        ['id' => 'workflow-tag-1', 'name' => 'Workflow Tag', 'sortNo' => 1],
    ];

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('admin-test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goMasterData')]
    public function testMasterData(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/master-data', ['masterType' => self::MASTER_TYPE]);

        $this->assertSame(Code::OK, $response->code);
        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($response, 'selectedMaster'));

        return $response;
    }

    #[Alps('doSelectMasterData')]
    #[Depends('testMasterData')]
    public function testSelectsMasterData(ResourceObject $response): ResourceObject
    {
        $selected = $this->resource->put('page://self/admin/master-data', [
            'masterType' => self::MASTER_TYPE,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $selected->code);
        $this->assertSame('doSelectMasterData', $this->bodyValue($selected, 'transitionId'));
        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($selected, 'selectedMaster'));

        return $selected;
    }

    #[Alps('doUpdateMasterData')]
    #[Depends('testSelectsMasterData')]
    public function testUpdatesMasterData(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/master-data-edit', [
            'masterType' => self::MASTER_TYPE,
            'rows' => self::UPDATED_ROWS,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateMasterData', $this->bodyValue($updated, 'transitionId'));
        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($updated, 'masterType'));
        $this->assertSame(1, $this->bodyValue($updated, 'count'));
        $this->assertSame('/admin/master-data', $this->header($updated, 'Location'));

        return $updated;
    }

    #[Alps('goMasterData')]
    #[Depends('testUpdatesMasterData')]
    public function testReadsUpdatedMasterData(ResourceObject $response): void
    {
        $read = $this->follow($response, 'goMasterData', ['masterType' => self::MASTER_TYPE]);

        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($read, 'selectedMaster'));
    }
}
