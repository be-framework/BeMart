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
 * HTML hypermedia walk of the admin content CMS — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP (Path C).
 *
 * This test does NOT extend the Hypermedia workflow class; it walks the
 * rendered HTML the way a browser would, resolving transitions from ALPS
 * class/rel tokens on forms and anchors.
 *
 * Journey (subset of FlowAdminContentPublishTest):
 *   1. GET /admin/news/news      → assertAffordance doCreateNews
 *   2. submit doCreateNews       → 201/303 + Location
 *   3. followLocation            → created news title in rendered page
 *   4. GET /admin/content/css    → assertAffordance doUpdateContentCss
 *   5. submit doUpdateContentCss → 200/303 (unique CSS snippet)
 *   6. GET /admin/content/js     → assertAffordance doUpdateContentJs
 *   7. submit doUpdateContentJs  → 200/303 (unique JS snippet)
 *   8. GET /admin/block/block    → assertAffordance doCreateBlock
 *   9. submit doCreateBlock      → 201/303 + Location
 *  10. followLocation            → created block name in rendered page
 *  11. GET /admin/page/page      → assertAffordance doCreatePage
 *  12. submit doCreatePage       → 201/303 + Location
 *  13. followLocation            → created page name in rendered page
 *
 * Steps skipped (no HTML form affordance; would require JavaScript or
 * direct resource calls to navigate):
 *   - doUpdateNews / doDeleteNews — rendered by News.html.twig as doUpdateNews
 *     (PUT _method form) but that branch only renders when newsId is supplied;
 *     the delete is a JS modal anchor, not a <form class="doDeleteNews">.
 *   - doUpdateBlock / doDeleteBlock — Block.html.twig's update branch renders
 *     class="doUpdateBlock", but the delete affordance is a JS-anchor modal in
 *     BlockList.html.twig, not a <form>.
 *   - doUpdatePage / doDeletePage — same pattern as Block.
 *   - goPageList / goBlockList navigation steps — list pages carry no <form>
 *     submit affordance; they are plain anchor-list views.
 *   - goLayoutList / goLayout / doUpdateLayout — Layout.html.twig has no
 *     class="do*" form token; JS-driven drag-and-drop layout editor.
 *   - doUpdateTradeLaw — TradeLaw template has class="doUpdateTradeLaw" but
 *     navigating from news → trade law requires following untagged nav anchors
 *     across unrelated content sub-sections; omitted to keep this walk focused.
 */
final class FlowAdminContentPublishTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-content-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-content-html-csrf-token';

    private static string $suffix;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$suffix = bin2hex(random_bytes(4));
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
            '127.0.0.1:8127',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    // -----------------------------------------------------------------------
    // News: create
    // -----------------------------------------------------------------------

    #[Alps('goNews')]
    public function testOpensNewsCreateForm(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/news/news');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doCreateNews');

        return $page;
    }

    #[Alps('doCreateNews')]
    #[Depends('testOpensNewsCreateForm')]
    public function testCreatesNews(ResourceObject $page): ResourceObject
    {
        $created = $this->submit($page, 'doCreateNews', [
            'newsTitle' => 'HTML News ' . self::$suffix,
            'publishDate' => '2027-06-01 00:00:00',
            'newsDescription' => 'Created by flow-admin-content-html ' . self::$suffix . '.',
            'newsUrl' => 'https://example.com/html-content-' . self::$suffix,
            'linkMethod' => false,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateNews did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goNewsList')]
    #[Depends('testCreatesNews')]
    public function testConfirmsCreatedNews(ResourceObject $created): ResourceObject
    {
        $location = $this->header($created, 'Location');
        if ($location !== null) {
            $detail = $this->followLocation($created);
            $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
            $this->assertStringContainsString('HTML News ' . self::$suffix, (string) ($detail->view ?? ''));

            return $detail;
        }

        $this->assertStringContainsString(
            'HTML News ' . self::$suffix,
            (string) ($created->view ?? ''),
            'created news title not found in response',
        );

        return $created;
    }

    // -----------------------------------------------------------------------
    // Content CSS: update
    // -----------------------------------------------------------------------

    #[Alps('goContentCss')]
    #[Depends('testConfirmsCreatedNews')]
    public function testOpensCssPage(ResourceObject $previous): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/content/css');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateContentCss');

        return $page;
    }

    #[Alps('doUpdateContentCss')]
    #[Depends('testOpensCssPage')]
    public function testUpdatesContentCss(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateContentCss', [
            'css' => '.html-content-' . self::$suffix . ' { color: #abcdef; }',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doUpdateContentCss did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    // -----------------------------------------------------------------------
    // Content JS: update
    // -----------------------------------------------------------------------

    #[Alps('goContentJs')]
    #[Depends('testUpdatesContentCss')]
    public function testOpensJsPage(ResourceObject $previous): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/content/js');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateContentJs');

        return $page;
    }

    #[Alps('doUpdateContentJs')]
    #[Depends('testOpensJsPage')]
    public function testUpdatesContentJs(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateContentJs', [
            'js' => 'window.htmlContent' . self::$suffix . ' = true;',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doUpdateContentJs did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    // -----------------------------------------------------------------------
    // Block: create
    // -----------------------------------------------------------------------

    #[Alps('goBlock')]
    #[Depends('testUpdatesContentJs')]
    public function testOpensBlockCreateForm(ResourceObject $previous): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/block/block');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doCreateBlock');

        return $page;
    }

    #[Alps('doCreateBlock')]
    #[Depends('testOpensBlockCreateForm')]
    public function testCreatesBlock(ResourceObject $page): ResourceObject
    {
        $created = $this->submit($page, 'doCreateBlock', [
            'blockName' => 'HTML Block ' . self::$suffix,
            'blockFileName' => 'html_block_' . self::$suffix,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateBlock did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goBlockList')]
    #[Depends('testCreatesBlock')]
    public function testConfirmsCreatedBlock(ResourceObject $created): ResourceObject
    {
        $location = $this->header($created, 'Location');
        if ($location !== null) {
            $detail = $this->followLocation($created);
            $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
            $this->assertStringContainsString('HTML Block ' . self::$suffix, (string) ($detail->view ?? ''));

            return $detail;
        }

        $this->assertStringContainsString(
            'HTML Block ' . self::$suffix,
            (string) ($created->view ?? ''),
            'created block name not found in response',
        );

        return $created;
    }

    // -----------------------------------------------------------------------
    // Page: create
    // -----------------------------------------------------------------------

    #[Alps('goPage')]
    #[Depends('testConfirmsCreatedBlock')]
    public function testOpensPageCreateForm(ResourceObject $previous): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/page/page');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doCreatePage');

        return $page;
    }

    #[Alps('doCreatePage')]
    #[Depends('testOpensPageCreateForm')]
    public function testCreatesPage(ResourceObject $page): ResourceObject
    {
        $created = $this->submit($page, 'doCreatePage', [
            'pageName' => 'HTML Page ' . self::$suffix,
            'pageUrl' => 'html-page-' . self::$suffix,
            'pageFileName' => 'html_page_' . self::$suffix,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreatePage did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goPageList')]
    #[Depends('testCreatesPage')]
    public function testConfirmsCreatedPage(ResourceObject $created): void
    {
        $location = $this->header($created, 'Location');
        if ($location !== null) {
            $detail = $this->followLocation($created);
            $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
            $this->assertStringContainsString('HTML Page ' . self::$suffix, (string) ($detail->view ?? ''));

            return;
        }

        $this->assertStringContainsString(
            'HTML Page ' . self::$suffix,
            (string) ($created->view ?? ''),
            'created page name not found in response',
        );
    }
}
