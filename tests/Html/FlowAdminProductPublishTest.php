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
use function str_repeat;

/**
 * HTML hypermedia walk of the admin product editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Following only needs the affordance's link target, not a reconstructed body:
 * navigation follows the `<a rel="goProduct">` a browser would click; writes
 * submit the `<form class="doCreateProduct|doUpdateProduct">` a browser would
 * submit (with its rendered action + hidden CSRF). It asserts the walk is
 * reachable (status) and the next affordance is present — catching a link/form
 * whose target drifts mid-flow (the 405 class). State assertions stay with the
 * JSON resource/contract tests.
 */
final class FlowAdminProductPublishTest extends AbstractHtmlWorkflowTestCase
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

        // E2E state check: the editor renders the values we created (assertState
        // reads the rendered control, the HTML counterpart of bodyValue()).
        $this->assertState($editor, 'productCode', self::$productCode);
        $this->assertState($editor, 'productName', self::$productName);
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

    /**
     * Regression: a real browser submits the rendered edit form including an
     * EMPTY stock field (unlimited stock renders blank). The transport schema +
     * resource must accept '' and treat it as null — not 400. The earlier update
     * test passed only because submit() sent a minimal 3-field set (no stock),
     * masking the empty-string → int|null binding gap.
     */
    #[Depends('testFollowsEditAffordanceFromList')]
    #[Alps('doUpdateProduct')]
    public function testUpdateAcceptsEmptyStock(ResourceObject $editor): void
    {
        $updated = $this->submit($editor, 'doUpdateProduct', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => '2980',
            'stock' => '',
            'productStatus' => '1',
            'description' => '',
            'searchWord' => '',
            'note' => '',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'empty stock (unlimited) must be accepted, not 400: ' . (string) ($updated->view ?? $updated->code),
        );
    }

    /**
     * Regression: invalid request input (productName over its 128 maxLength)
     * must surface a *field-named* 400, not an opaque one. Since bear/resource
     * 1.33.0 the request-exception handler reads the structured `$e->getErrors()`
     * and raises a ValidationException; ExceptionStatusMapper names the field by
     * its Japanese schema title on the error page. Locks the form-validation UX
     * so it cannot regress to a bare 400 (the failure this whole alignment fixed).
     */
    #[Depends('testFollowsEditAffordanceFromList')]
    #[Alps('doUpdateProduct')]
    public function testRejectsInvalidInputWithFieldNamedError(ResourceObject $editor): void
    {
        $rejected = $this->submit($editor, 'doUpdateProduct', [
            'productCode' => self::$productCode,
            'productName' => str_repeat('あ', 200),
            'price02' => '2980',
            'stock' => '',
            'productStatus' => '1',
        ]);

        $this->assertSame(Code::BAD_REQUEST, $rejected->code, (string) ($rejected->view ?? $rejected->code));
        $this->assertStringContainsString('商品名（入力）', (string) ($rejected->view ?? ''));
    }
}
