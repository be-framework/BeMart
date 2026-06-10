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

class FlowAdminMasterDataUpdateTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-master-data-update';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-master-data-csrf-token';
    private const MASTER_TYPE = 'tag';
    private const UPDATED_ROWS = [
        ['id' => 'workflow-tag-1', 'name' => 'Workflow Tag', 'sortNo' => 1],
    ];

    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
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
        $resource = self::$dbSession->resource();

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
        $submitTo = $this->bodyValue($response, 'submitTo');
        $this->assertIsArray($submitTo);
        $this->assertSame('doSelectMasterData', $submitTo['rel'] ?? null);
        $this->assertSame('PUT', $submitTo['method'] ?? null);
        $this->assertIsString($submitTo['href'] ?? null);

        $selected = $this->resource->put((string) $submitTo['href'], [
            'masterType' => self::MASTER_TYPE,
            'csrfToken' => self::CSRF_TOKEN,
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
        $submitTo = $this->bodyValue($response, 'submitTo');
        $this->assertIsArray($submitTo);
        $this->assertSame('doUpdateMasterData', $submitTo['rel'] ?? null);
        $this->assertSame('PUT', $submitTo['method'] ?? null);
        $this->assertIsString($submitTo['href'] ?? null);

        $updated = $this->resource->put((string) $submitTo['href'], [
            'masterType' => self::MASTER_TYPE,
            'rows' => self::UPDATED_ROWS,
            'csrfToken' => self::CSRF_TOKEN,
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
