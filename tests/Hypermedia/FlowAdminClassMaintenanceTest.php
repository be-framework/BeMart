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

class FlowAdminClassMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-class-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-class-csrf-token';

    private static string $classNameLabel;
    private static string $updatedClassNameLabel;
    private static string $classCategoryName;
    private static string $updatedClassCategoryName;
    private static string $classNameId;
    private static string $classCategoryId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$classNameLabel = 'WF Class ' . $suffix;
        self::$updatedClassNameLabel = 'WF Class U ' . $suffix;
        self::$classCategoryName = 'WF Class Category ' . $suffix;
        self::$updatedClassCategoryName = 'WF Class Category U ' . $suffix;
        self::$classNameId = '';
        self::$classCategoryId = '';
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

    #[Alps('goClassNameList')]
    public function testOpensAdminClassNameList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateClassName')]
    #[Depends('testOpensAdminClassNameList')]
    public function testCreatesClassName(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateClassName'), [
            'classNameLabel' => self::$classNameLabel,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($created->code, [Code::CREATED, Code::SEE_OTHER]);
        self::$classNameId = (string) $this->bodyValue($created, 'classNameId');
        $this->assertNotSame('', self::$classNameId);
        $this->assertSame(self::$classNameLabel, $this->bodyValue($created, 'name'));

        return $created;
    }

    #[Alps('goClassNameList')]
    #[Depends('testCreatesClassName')]
    public function testFindsCreatedClassName(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response);
        $classNames = $this->bodyValue($list, 'classNames');

        $this->assertIsArray($classNames);
        $this->assertTrue(in_array(self::$classNameId, array_column($classNames, 'classNameId'), true));
        $this->assertTrue(in_array(self::$classNameLabel, array_column($classNames, 'name'), true));

        return $list;
    }

    #[Alps('doUpdateClassName')]
    #[Depends('testFindsCreatedClassName')]
    public function testUpdatesClassName(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateClassName'), [
            'classNameId' => self::$classNameId,
            'classNameLabel' => self::$updatedClassNameLabel,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($updated->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$classNameId, $this->bodyValue($updated, 'classNameId'));
        $this->assertSame(self::$updatedClassNameLabel, $this->bodyValue($updated, 'name'));

        return $updated;
    }

    #[Alps('goClassNameList')]
    #[Depends('testUpdatesClassName')]
    public function testFindsUpdatedClassName(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response);
        $classNames = $this->bodyValue($list, 'classNames');

        $this->assertIsArray($classNames);
        $this->assertTrue(in_array(self::$updatedClassNameLabel, array_column($classNames, 'name'), true));

        return $list;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testFindsUpdatedClassName')]
    public function testOpensClassCategoryList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goClassCategoryList', ['classNameId' => self::$classNameId]);
    }

    #[Alps('doCreateClassCategory')]
    #[Depends('testOpensClassCategoryList')]
    public function testCreatesClassCategory(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateClassCategory'), [
            'classNameId' => self::$classNameId,
            'classCategoryName' => self::$classCategoryName,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($created->code, [Code::CREATED, Code::SEE_OTHER]);
        self::$classCategoryId = (string) $this->bodyValue($created, 'classCategoryId');
        $this->assertNotSame('', self::$classCategoryId);
        $this->assertSame(self::$classNameId, $this->bodyValue($created, 'classNameId'));
        $this->assertSame(self::$classCategoryName, $this->bodyValue($created, 'name'));

        return $created;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testCreatesClassCategory')]
    public function testFindsCreatedClassCategory(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response);
        $classCategories = $this->bodyValue($list, 'classCategories');

        $this->assertIsArray($classCategories);
        $this->assertTrue(in_array(self::$classCategoryId, array_column($classCategories, 'classCategoryId'), true));
        $this->assertTrue(in_array(self::$classCategoryName, array_column($classCategories, 'name'), true));

        return $list;
    }

    #[Alps('doUpdateClassCategory')]
    #[Depends('testFindsCreatedClassCategory')]
    public function testUpdatesClassCategory(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateClassCategory'), [
            'classCategoryId' => self::$classCategoryId,
            'classCategoryName' => self::$updatedClassCategoryName,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($updated->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$classCategoryId, $this->bodyValue($updated, 'classCategoryId'));
        $this->assertSame(self::$classNameId, $this->bodyValue($updated, 'classNameId'));
        $this->assertSame(self::$updatedClassCategoryName, $this->bodyValue($updated, 'name'));

        return $updated;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testUpdatesClassCategory')]
    public function testFindsUpdatedClassCategory(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response);
        $classCategories = $this->bodyValue($list, 'classCategories');

        $this->assertIsArray($classCategories);
        $this->assertTrue(in_array(self::$updatedClassCategoryName, array_column($classCategories, 'name'), true));

        return $list;
    }

    #[Alps('doDeleteClassCategory')]
    #[Depends('testFindsUpdatedClassCategory')]
    public function testDeletesClassCategory(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteClassCategory'), [
            'classCategoryId' => self::$classCategoryId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($deleted->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$classCategoryId, $this->bodyValue($deleted, 'classCategoryId'));

        return $deleted;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testDeletesClassCategory')]
    public function testConfirmsClassCategoryRemoved(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation(
            $response,
            '/admin/class-category/class-category-list?classNameId=' . self::$classNameId,
        );
        $classCategories = $this->bodyValue($list, 'classCategories');

        $this->assertIsArray($classCategories);
        $this->assertFalse(in_array(self::$classCategoryId, array_column($classCategories, 'classCategoryId'), true));

        return $list;
    }

    #[Alps('goClassNameList')]
    #[Depends('testConfirmsClassCategoryRemoved')]
    public function testReturnsToClassNameList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goClassNameList');
    }

    #[Alps('doDeleteClassName')]
    #[Depends('testReturnsToClassNameList')]
    public function testDeletesClassName(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteClassName'), [
            'classNameId' => self::$classNameId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($deleted->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame(self::$classNameId, $this->bodyValue($deleted, 'classNameId'));

        return $deleted;
    }

    #[Alps('goClassNameList')]
    #[Depends('testDeletesClassName')]
    public function testConfirmsClassNameRemoved(ResourceObject $response): void
    {
        $list = $this->followLocation($response);
        $classNames = $this->bodyValue($list, 'classNames');

        $this->assertIsArray($classNames);
        $this->assertFalse(in_array(self::$classNameId, array_column($classNames, 'classNameId'), true));
    }
}
