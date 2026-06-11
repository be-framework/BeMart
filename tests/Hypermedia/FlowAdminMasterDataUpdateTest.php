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

use function array_column;
use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminMasterDataUpdateTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-master-data-update';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-master-data-csrf-token';
    private const MASTER_TYPE = 'payment';

    private static WorkflowDbSession|null $dbSession = null;
    private static string $updatedName;

    public static function setUpBeforeClass(): void
    {
        self::$updatedName = 'Workflow Payment ' . bin2hex(random_bytes(4));
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

        $rows = $this->bodyValue($response, 'rows');
        $this->assertIsArray($rows);
        $this->assertNotSame([], $rows);
        $first = $rows[0];
        $this->assertIsArray($first);
        $this->assertIsString($first['id'] ?? null);

        $updated = $this->resource->put((string) $submitTo['href'], [
            'masterType' => self::MASTER_TYPE,
            'rows' => [
                ['id' => $first['id'], 'name' => self::$updatedName],
            ],
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($updated->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame('doUpdateMasterData', $this->bodyValue($updated, 'transitionId'));
        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($updated, 'masterType'));
        $this->assertSame(1, $this->bodyValue($updated, 'count'));
        $this->assertSame('/admin/master-data?masterType=payment', $this->header($updated, 'Location'));

        return $updated;
    }

    #[Alps('goMasterData')]
    #[Depends('testUpdatesMasterData')]
    public function testReadsUpdatedMasterData(ResourceObject $response): void
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::MASTER_TYPE, $this->bodyValue($read, 'selectedMaster'));
        $rows = $this->bodyValue($read, 'rows');
        $this->assertIsArray($rows);
        $this->assertContains(self::$updatedName, array_column($rows, 'name'));
    }
}
