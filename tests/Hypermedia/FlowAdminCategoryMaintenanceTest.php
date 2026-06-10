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

class FlowAdminCategoryMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-category-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-category-csrf-token';

    private static string $categoryName;
    private static string $updatedCategoryName;
    private static string $categoryId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$categoryName = 'WF Category ' . $suffix;
        self::$updatedCategoryName = 'WF Category U ' . $suffix;
        self::$categoryId = '';
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

    #[Alps('goCategoryList')]
    public function testOpensAdminCategoryList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/category/category-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateCategory')]
    #[Depends('testOpensAdminCategoryList')]
    public function testCreatesCategory(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateCategory'), [
            'categoryName' => self::$categoryName,
            'sortNo' => 90,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($created->code, [Code::CREATED, Code::SEE_OTHER]);
        self::$categoryId = (string) $this->bodyValue($created, 'categoryId');
        $this->assertNotSame('', self::$categoryId);

        return $created;
    }

    #[Alps('goCategory')]
    #[Depends('testCreatesCategory')]
    public function testReadsCreatedCategory(ResourceObject $response): ResourceObject
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::$categoryId, $this->bodyValue($read, 'categoryId'));
        $this->assertSame(self::$categoryName, $this->bodyValue($read, 'categoryName'));

        return $read;
    }

    #[Alps('doUpdateCategory')]
    #[Depends('testReadsCreatedCategory')]
    public function testUpdatesCategory(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateCategory'), [
            'categoryId' => self::$categoryId,
            'categoryName' => self::$updatedCategoryName,
            'sortNo' => 91,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($updated->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$categoryId, $this->bodyValue($updated, 'categoryId'));
        $this->assertSame(self::$updatedCategoryName, $this->bodyValue($updated, 'categoryName'));

        return $updated;
    }

    #[Alps('goCategory')]
    #[Depends('testUpdatesCategory')]
    public function testReadsUpdatedCategory(ResourceObject $response): ResourceObject
    {
        $read = $this->followLocation($response);

        $this->assertSame(self::$categoryId, $this->bodyValue($read, 'categoryId'));
        $this->assertSame(self::$updatedCategoryName, $this->bodyValue($read, 'categoryName'));

        return $read;
    }

    #[Alps('doDeleteCategory')]
    #[Depends('testReadsUpdatedCategory')]
    public function testDeletesCategory(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteCategory'), [
            'categoryId' => self::$categoryId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($deleted->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$categoryId, $this->bodyValue($deleted, 'categoryId'));

        return $deleted;
    }

    #[Alps('goCategoryList')]
    #[Depends('testDeletesCategory')]
    public function testConfirmsCategoryRemoved(ResourceObject $response): void
    {
        $list = $this->followLocation($response);
        $categories = $this->bodyValue($list, 'categories');

        $this->assertIsArray($categories);
        $this->assertFalse(in_array(self::$categoryId, array_column($categories, 'categoryId'), true));
    }
}
