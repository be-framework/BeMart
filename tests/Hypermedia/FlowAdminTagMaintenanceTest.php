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
use function in_array;
use function random_bytes;

class FlowAdminTagMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-tag-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-tag-csrf-token';

    private static string $tagName;
    private static string $tagId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$tagName = 'Workflow Tag ' . bin2hex(random_bytes(4));
        self::$tagId = '';
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

        return self::$dbSession->resource();
    }

    #[Alps('goTagList')]
    public function testOpensAdminTagList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/tag/tag-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateTag')]
    #[Depends('testOpensAdminTagList')]
    public function testCreatesTag(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateTag'), [
            'tagName' => self::$tagName,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($created->code, [Code::CREATED, Code::SEE_OTHER]);
        self::$tagId = (string) $this->bodyValue($created, 'tagId');
        $this->assertNotSame('', self::$tagId);

        return $created;
    }

    #[Alps('goTagList')]
    #[Depends('testCreatesTag')]
    public function testFindsCreatedTag(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response);
        $tags = $this->bodyValue($list, 'tags');

        $this->assertIsArray($tags);
        $this->assertTrue(in_array(self::$tagId, array_column($tags, 'tagId'), true));
        $this->assertTrue(in_array(self::$tagName, array_column($tags, 'tagName'), true));

        return $list;
    }

    #[Alps('doDeleteTag')]
    #[Depends('testFindsCreatedTag')]
    public function testDeletesTag(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteTag'), [
            'tagId' => self::$tagId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($deleted->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$tagId, $this->bodyValue($deleted, 'tagId'));

        return $deleted;
    }

    #[Alps('goTagList')]
    #[Depends('testDeletesTag')]
    public function testConfirmsTagRemoved(ResourceObject $response): void
    {
        $list = $this->followLocation($response);
        $tags = $this->bodyValue($list, 'tags');

        $this->assertIsArray($tags);
        $this->assertFalse(in_array(self::$tagId, array_column($tags, 'tagId'), true));
    }
}
