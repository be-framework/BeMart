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
 * HTML hypermedia walk of the admin product editor — driven entirely by the
 * rendered HTML's data-alps affordances over real HTTP.
 *
 * Navigation follows the `<a data-alps="goProduct">` a browser would click;
 * writes submit the `<form data-alps="doCreateProduct|doUpdateProduct">` a
 * browser would submit (with its rendered action + hidden CSRF). It is the
 * journey-level counterpart to AffordanceProductFormTest, and the leg that
 * catches a link/form whose target drifts mid-flow (the 405 class).
 */
final class FlowAdminProductHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-product-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-product-html-csrf-token';

    private static string $productCode;
    private static string $productName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$productCode = 'wf-html-' . $suffix;
        self::$productName = 'HTML Workflow Product ' . $suffix;
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
            'productName' => self::$productName,
            'price02' => '1980',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateProduct affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );
    }

    #[Depends('testCreatesProductByFollowingTheRenderedForm')]
    #[Alps('goProductList')]
    public function testListsCreatedProduct(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/product-list', ['nameKeyword' => self::$productName]);

        $this->assertSame(Code::OK, $list->code);
        $this->assertStringContainsString(self::$productCode, (string) ($list->view ?? ''));
        $this->assertAffordance($list, 'goProduct');

        return $list;
    }

    #[Depends('testListsCreatedProduct')]
    #[Alps('goProduct')]
    public function testFollowsEditAffordanceFromList(ResourceObject $list): ResourceObject
    {
        $editor = $this->follow($list, 'goProduct');

        $this->assertStringContainsString(self::$productCode, (string) ($editor->view ?? ''));
        $this->assertAffordance($editor, 'doUpdateProduct');

        return $editor;
    }

    #[Depends('testFollowsEditAffordanceFromList')]
    #[Alps('doUpdateProduct')]
    public function testUpdatesProductByFollowingTheRenderedForm(ResourceObject $editor): void
    {
        $updated = $this->submit($editor, 'doUpdateProduct', [
            'productCode' => self::$productCode,
            'productName' => self::$productName . ' (updated)',
            'price02' => '2980',
        ]);

        $this->assertSame(Code::SEE_OTHER, $updated->code, (string) ($updated->view ?? $updated->code));
    }
}
