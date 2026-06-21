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
 * HTML hypermedia walk of the admin category editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * The walk covers the HTML-followable core:
 *   1. Open the editor page that renders the create form.
 *   2. Submit doCreateCategory and confirm the category was persisted.
 *   3. Follow the Location header to the detail page and assert the name.
 *
 * Steps skipped (no HTML affordance rendered):
 *   - doUpdateCategory: Category.html.twig renders a PUT form but carries
 *     no class="doUpdateCategory" token, so submit() cannot resolve it.
 *   - doDeleteCategory: rendered as a JS token-for-anchor <a> (not a
 *     <form>), which requires JavaScript to submit a DELETE request.
 */
final class FlowAdminCategoryMaintenanceTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-category-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-category-html-csrf-token';

    private static string $categoryName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$categoryName = 'HTML Category ' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8117',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goCategoryList')]
    public function testOpensAdminCategoryEditor(): ResourceObject
    {
        $editor = $this->resource->get('page://self/admin/category/edit');

        $this->assertSame(Code::OK, $editor->code);
        $this->assertAffordance($editor, 'doCreateCategory');

        return $editor;
    }

    #[Alps('doCreateCategory')]
    #[Depends('testOpensAdminCategoryEditor')]
    public function testCreatesCategory(ResourceObject $editor): ResourceObject
    {
        $created = $this->submit($editor, 'doCreateCategory', [
            'categoryName' => self::$categoryName,
            'sortNo' => '80',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateCategory affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goCategory')]
    #[Depends('testCreatesCategory')]
    public function testConfirmsCreatedCategory(ResourceObject $created): void
    {
        $location = $this->header($created, 'Location');
        if ($location !== null) {
            $detail = $this->followLocation($created);
            $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
            $this->assertStringContainsString(self::$categoryName, (string) ($detail->view ?? ''));

            return;
        }

        // No Location header: the list was returned inline — confirm the name
        // is present in the response body.
        $this->assertStringContainsString(
            self::$categoryName,
            (string) ($created->view ?? ''),
            'created category name not found in response',
        );
    }
}
