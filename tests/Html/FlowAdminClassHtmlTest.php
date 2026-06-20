<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML hypermedia walk of the admin ClassName + ClassCategory maintenance —
 * driven entirely by the rendered HTML's ALPS affordances (class/rel) over
 * real HTTP.
 *
 * Path C: does NOT extend the Hypermedia workflow class; walks the rendered
 * HTML the way a browser would, resolving transitions from ALPS class/rel
 * tokens on forms and anchors.
 *
 * Journey mirrored from FlowAdminClassMaintenanceTest (Hypermedia):
 *   1. GET  /admin/class-name/class-name-list  → assertAffordance doCreateClassName
 *   2. submit doCreateClassName (POST)          → 201/303 + verify list contains new name
 *   3. GET  /admin/class-name/class-name-list  → assertAffordance doUpdateClassName
 *   4. submit doUpdateClassName (POST+_method=put) → 200/303 + verify updated name
 *   5. GET  /admin/class-category/class-category-list?classNameId=… → assertAffordance doCreateClassCategory
 *   6. submit doCreateClassCategory (POST)      → 201/303 + verify list contains new category
 *   7. GET  /admin/class-category/class-category-list?classNameId=… → assertAffordance doUpdateClassCategory
 *   8. submit doUpdateClassCategory (POST+_method=put) → 200/303 + verify updated name
 *
 * Steps skipped (no HTML affordance rendered):
 *   - doDeleteClassName / doDeleteClassCategory: rendered as JS token-for-anchor
 *     <a data-url="…&_method=delete"> wired to a Bootstrap modal. No <form> is
 *     emitted, so submit() cannot resolve them.
 *   - bodyValue() checks (classNameId, classCategoryId returned in JSON body):
 *     the HTML response body is not JSON; these fields are visible only in the
 *     Hypermedia (in-process) test. The HTML walk verifies state through the
 *     rendered page text instead.
 */
final class FlowAdminClassHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-class-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-class-html-csrf-token';

    private static string $classNameLabel;
    private static string $updatedClassNameLabel;
    private static string $classCategoryName;
    private static string $updatedClassCategoryName;
    private static string $classNameId = '';
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$classNameLabel = 'HTML Class ' . $suffix;
        self::$updatedClassNameLabel = 'HTML Class U ' . $suffix;
        self::$classCategoryName = 'HTML ClassCat ' . $suffix;
        self::$updatedClassCategoryName = 'HTML ClassCat U ' . $suffix;
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

        return new HttpResource(
            '127.0.0.1:8126',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goClassNameList')]
    public function testOpensAdminClassNameList(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertAffordance($list, 'doCreateClassName');

        return $list;
    }

    #[Alps('doCreateClassName')]
    #[Depends('testOpensAdminClassNameList')]
    public function testCreatesClassName(ResourceObject $list): ResourceObject
    {
        $created = $this->submit($list, 'doCreateClassName', [
            'classNameLabel' => self::$classNameLabel,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateClassName did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goClassNameList')]
    #[Depends('testCreatesClassName')]
    public function testConfirmsCreatedClassNameInList(ResourceObject $created): ResourceObject
    {
        // Follow Location (303) back to the list and check the new name appears.
        $list = $created->code === Code::SEE_OTHER
            ? $this->followLocation($created)
            : $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(
            self::$classNameLabel,
            (string) ($list->view ?? ''),
            'Created class-name label not found in list page',
        );

        return $list;
    }

    #[Alps('doUpdateClassName')]
    #[Depends('testConfirmsCreatedClassNameInList')]
    public function testUpdatesClassName(ResourceObject $list): ResourceObject
    {
        // The doUpdateClassName form is rendered (d-none / mode-edit) once per
        // row; submit() scans all <form> tags and picks the first one whose
        // class attribute contains the ALPS token. The action encodes classNameId
        // in the query-string, so only classNameLabel needs to be in $fields.
        $this->assertAffordance($list, 'doUpdateClassName');

        $updated = $this->submit($list, 'doUpdateClassName', [
            'classNameLabel' => self::$updatedClassNameLabel,
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateClassName did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    #[Alps('goClassNameList')]
    #[Depends('testUpdatesClassName')]
    public function testConfirmsUpdatedClassNameInList(ResourceObject $updated): ResourceObject
    {
        $list = $updated->code === Code::SEE_OTHER
            ? $this->followLocation($updated)
            : $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(
            self::$updatedClassNameLabel,
            (string) ($list->view ?? ''),
            'Updated class-name label not found in list page',
        );

        // Extract the classNameId from the rendered list for the ClassCategory
        // walk below. The template renders each row as:
        //   <li … data-class-name-id="<id>" …>
        $view = (string) ($list->view ?? '');
        if (preg_match('/data-class-name-id="(\d+)"/', $view, $match) === 1) {
            self::$classNameId = $match[1];
        }

        return $list;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testConfirmsUpdatedClassNameInList')]
    public function testOpensClassCategoryList(ResourceObject $list): ResourceObject
    {
        // goClassCategoryList is an anchor link on the class-name-list page;
        // however it is not tagged with a rel/class ALPS token — the template
        // renders it as a plain <a href="…"> inside the row text. Navigate
        // directly using the known URL (same pattern as the Hypermedia test's
        // direct get() calls for untagged nav).
        $categoryList = $this->resource->get(
            'page://self/admin/class-category/class-category-list',
            ['classNameId' => self::$classNameId],
        );

        $this->assertSame(Code::OK, $categoryList->code, (string) ($categoryList->view ?? $categoryList->code));
        $this->assertAffordance($categoryList, 'doCreateClassCategory');

        return $categoryList;
    }

    #[Alps('doCreateClassCategory')]
    #[Depends('testOpensClassCategoryList')]
    public function testCreatesClassCategory(ResourceObject $categoryList): ResourceObject
    {
        $created = $this->submit($categoryList, 'doCreateClassCategory', [
            'classNameId' => self::$classNameId,
            'classCategoryName' => self::$classCategoryName,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateClassCategory did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testCreatesClassCategory')]
    public function testConfirmsCreatedClassCategoryInList(ResourceObject $created): ResourceObject
    {
        $list = $created->code === Code::SEE_OTHER
            ? $this->followLocation($created)
            : $this->resource->get(
                'page://self/admin/class-category/class-category-list',
                ['classNameId' => self::$classNameId],
            );

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(
            self::$classCategoryName,
            (string) ($list->view ?? ''),
            'Created class-category name not found in list page',
        );

        return $list;
    }

    #[Alps('doUpdateClassCategory')]
    #[Depends('testConfirmsCreatedClassCategoryInList')]
    public function testUpdatesClassCategory(ResourceObject $list): ResourceObject
    {
        // doUpdateClassCategory is rendered d-none/mode-edit once per row;
        // submit() picks the first matching <form class="… doUpdateClassCategory …">.
        // The action encodes classCategoryId in the query-string; only
        // classCategoryName needs to be in $fields.
        $this->assertAffordance($list, 'doUpdateClassCategory');

        $updated = $this->submit($list, 'doUpdateClassCategory', [
            'classCategoryName' => self::$updatedClassCategoryName,
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateClassCategory did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testUpdatesClassCategory')]
    public function testConfirmsUpdatedClassCategoryInList(ResourceObject $updated): void
    {
        $list = $updated->code === Code::SEE_OTHER
            ? $this->followLocation($updated)
            : $this->resource->get(
                'page://self/admin/class-category/class-category-list',
                ['classNameId' => self::$classNameId],
            );

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(
            self::$updatedClassCategoryName,
            (string) ($list->view ?? ''),
            'Updated class-category name not found in list page',
        );
    }
}
