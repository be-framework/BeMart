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
 * HTML hypermedia walk of the admin product editor.
 *
 * The Hypermedia suite drives this journey through #[Link] rels; this drives it
 * through the rendered HTML's data-alps affordances over real HTTP — rendering
 * the editor, then following (submitting) the doCreateProduct / doUpdateProduct
 * <form>s exactly as a browser would. It is the journey-level counterpart to the
 * single-step AffordanceProductFormTest, and it is the leg that catches a
 * form whose action/CSRF drift mid-flow (the 405 class).
 */
final class FlowAdminProductHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-product-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-product-html-csrf-token';

    private static string $productCode;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$productCode = 'wf-html-' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8110',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goProduct')]
    public function testOpensBlankEditor(): ResourceObject
    {
        $editor = $this->resource->get('page://self/admin/product/edit');

        $this->assertSame(Code::OK, $editor->code);
        $this->assertAffordance($editor, 'doCreateProduct');

        return $editor;
    }

    #[Depends('testOpensBlankEditor')]
    #[Alps('doCreateProduct')]
    public function testCreatesProductByFollowingTheRenderedForm(ResourceObject $editor): void
    {
        $created = $this->submit($editor, 'doCreateProduct', [
            'productCode' => self::$productCode,
            'productName' => 'HTML Workflow Product',
            'price02' => '1980',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateProduct affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );
    }

    #[Depends('testCreatesProductByFollowingTheRenderedForm')]
    #[Alps('goProduct')]
    public function testOpensEditorForCreatedProduct(): ResourceObject
    {
        $editor = $this->resource->get('page://self/admin/product/edit', ['productCode' => self::$productCode]);

        $this->assertSame(Code::OK, $editor->code);
        $this->assertAffordance($editor, 'doUpdateProduct');
        $this->assertStringContainsString(self::$productCode, (string) ($editor->view ?? ''));

        return $editor;
    }

    #[Depends('testOpensEditorForCreatedProduct')]
    #[Alps('doUpdateProduct')]
    public function testUpdatesProductByFollowingTheRenderedForm(ResourceObject $editor): void
    {
        $updated = $this->submit($editor, 'doUpdateProduct', [
            'productCode' => self::$productCode,
            'productName' => 'HTML Workflow Product (updated)',
            'price02' => '2980',
        ]);

        $this->assertSame(Code::SEE_OTHER, $updated->code, (string) ($updated->view ?? $updated->code));
    }
}
