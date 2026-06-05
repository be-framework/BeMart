<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowTestSession;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function count;
use function random_bytes;

class FlowAdminContentPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-content-publish';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';

    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $suffix;
    private static WorkflowTestSession|null $session = null;

    public static function setUpBeforeClass(): void
    {
        self::$suffix = bin2hex(random_bytes(4));
        self::$session = WorkflowTestSession::fromCurrent();
        self::$session->assumeAdminLoggedIn(self::ADMIN_ID, self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $layouts = self::$injector->getInstance(LayoutStorageInterface::class);
        assert($layouts instanceof LayoutStorageInterface);
        $layouts->put(new LayoutEntity('1', 'Workflow Seed Layout', 10));

        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        self::$session?->restore();

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;
        self::$session = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        if (self::$dbResource instanceof ResourceInterface) {
            return self::$dbResource;
        }

        assert(self::$injector instanceof InjectorInterface);
        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;

        return $resource;
    }

    #[Alps('goNewsList')]
    public function testNewsList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/news/news-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateNews')]
    #[Depends('testNewsList')]
    public function testCreatesNews(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/news/news-list', [
            'newsTitle' => 'Workflow News ' . self::$suffix,
            'publishDate' => '2027-03-01 00:00:00',
            'newsDescription' => 'Created by flow-admin-content-publish.',
            'newsUrl' => 'https://example.com/workflow-news',
            'linkMethod' => false,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame('Workflow News ' . self::$suffix, $this->bodyValue($created, 'newsTitle'));

        return $created;
    }

    #[Alps('goNews')]
    #[Depends('testCreatesNews')]
    public function testReadsNews(ResourceObject $response): ResourceObject
    {
        return $this->followLocation($response);
    }

    #[Alps('doUpdateNews')]
    #[Depends('testReadsNews')]
    public function testUpdatesNews(ResourceObject $response): ResourceObject
    {
        $newsId = $this->bodyValue($response, 'newsId');
        $this->assertIsString($newsId);

        $updated = $this->resource->put('page://self/admin/news/news', [
            'newsId' => $newsId,
            'newsTitle' => 'Workflow News Updated ' . self::$suffix,
            'publishDate' => '2027-03-02 00:00:00',
            'newsDescription' => 'Updated by flow-admin-content-publish.',
            'newsUrl' => 'https://example.com/workflow-news-updated',
            'linkMethod' => false,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($newsId, $this->bodyValue($updated, 'newsId'));
        $this->assertSame('Workflow News Updated ' . self::$suffix, $this->bodyValue($updated, 'newsTitle'));

        return $updated;
    }

    #[Alps('doDeleteNews')]
    #[Depends('testUpdatesNews')]
    public function testDeletesNews(ResourceObject $response): ResourceObject
    {
        $newsId = $this->bodyValue($response, 'newsId');
        $this->assertIsString($newsId);

        $deleted = $this->resource->delete('page://self/admin/news/news', [
            'newsId' => $newsId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($newsId, $this->bodyValue($deleted, 'newsId'));

        return $deleted;
    }

    #[Alps('goPageList')]
    #[Depends('testDeletesNews')]
    public function testPageList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goPageList');
    }

    #[Alps('doCreatePage')]
    #[Depends('testPageList')]
    public function testCreatesPage(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/page/page-list', [
            'pageName' => 'Workflow Page ' . self::$suffix,
            'pageUrl' => 'workflow-page-' . self::$suffix,
            'pageFileName' => 'workflow_page_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame('Workflow Page ' . self::$suffix, $this->bodyValue($created, 'pageName'));

        return $created;
    }

    #[Alps('doUpdatePage')]
    #[Depends('testCreatesPage')]
    public function testUpdatesPage(ResourceObject $response): ResourceObject
    {
        $pageId = $this->bodyValue($response, 'pageId');
        $this->assertIsString($pageId);

        $updated = $this->resource->put('page://self/admin/page/page', [
            'pageId' => $pageId,
            'pageName' => 'Workflow Page Updated ' . self::$suffix,
            'pageUrl' => 'workflow-page-updated-' . self::$suffix,
            'pageFileName' => 'workflow_page_updated_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($pageId, $this->bodyValue($updated, 'pageId'));
        $this->assertSame('Workflow Page Updated ' . self::$suffix, $this->bodyValue($updated, 'pageName'));

        return $updated;
    }

    #[Alps('doDeletePage')]
    #[Depends('testUpdatesPage')]
    public function testDeletesPage(ResourceObject $response): ResourceObject
    {
        $pageId = $this->bodyValue($response, 'pageId');
        $this->assertIsString($pageId);

        $deleted = $this->resource->delete('page://self/admin/page/page', [
            'pageId' => $pageId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($pageId, $this->bodyValue($deleted, 'pageId'));

        return $deleted;
    }

    #[Alps('goBlockList')]
    #[Depends('testDeletesPage')]
    public function testBlockList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goBlockList');
    }

    #[Alps('doCreateBlock')]
    #[Depends('testBlockList')]
    public function testCreatesBlock(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => 'Workflow Block ' . self::$suffix,
            'blockFileName' => 'workflow_block_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame('Workflow Block ' . self::$suffix, $this->bodyValue($created, 'blockName'));

        return $created;
    }

    #[Alps('doUpdateBlock')]
    #[Depends('testCreatesBlock')]
    public function testUpdatesBlock(ResourceObject $response): ResourceObject
    {
        $blockId = $this->bodyValue($response, 'blockId');
        $this->assertIsString($blockId);

        $updated = $this->resource->put('page://self/admin/block/block', [
            'blockId' => $blockId,
            'blockName' => 'Workflow Block Updated ' . self::$suffix,
            'blockFileName' => 'workflow_block_updated_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($blockId, $this->bodyValue($updated, 'blockId'));
        $this->assertSame('Workflow Block Updated ' . self::$suffix, $this->bodyValue($updated, 'blockName'));

        return $updated;
    }

    #[Alps('doDeleteBlock')]
    #[Depends('testUpdatesBlock')]
    public function testDeletesBlock(ResourceObject $response): ResourceObject
    {
        $blockId = $this->bodyValue($response, 'blockId');
        $this->assertIsString($blockId);

        $deleted = $this->resource->delete('page://self/admin/block/block', [
            'blockId' => $blockId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($blockId, $this->bodyValue($deleted, 'blockId'));

        return $deleted;
    }

    #[Alps('goLayoutList')]
    #[Depends('testDeletesBlock')]
    public function testLayoutList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goLayoutList');
    }

    #[Alps('doUpdateLayout')]
    #[Depends('testLayoutList')]
    public function testUpdatesLayout(ResourceObject $response): ResourceObject
    {
        $layouts = $this->bodyValue($response, 'layouts');
        $this->assertIsArray($layouts);
        $this->assertGreaterThan(0, count($layouts));
        $layout = $layouts[0];
        $this->assertIsArray($layout);
        $this->assertArrayHasKey('layoutId', $layout);
        $this->assertIsString($layout['layoutId']);

        $updated = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => $layout['layoutId'],
            'layoutName' => 'Workflow Layout ' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($layout['layoutId'], $this->bodyValue($updated, 'layoutId'));
        $this->assertSame('Workflow Layout ' . self::$suffix, $this->bodyValue($updated, 'layoutName'));

        return $updated;
    }

    #[Alps('goTradeLawList')]
    #[Depends('testUpdatesLayout')]
    public function testTradeLawList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTradeLawList');
    }

    #[Alps('doUpdateTradeLaw')]
    #[Depends('testTradeLawList')]
    public function testUpdatesTradeLaw(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/admin/trade-law', [
            'tradeLawBody' => "販売業者: Workflow Company\n所在地: Workflow City\n連絡先: 03-1234-5678",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertStringContainsString('Workflow Company', (string) $this->bodyValue($updated, 'tradeLawBody'));

        return $updated;
    }

    #[Alps('goContentCss')]
    #[Depends('testUpdatesTradeLaw')]
    public function testContentCss(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goContentCss');
    }

    #[Alps('doUpdateContentCss')]
    #[Depends('testContentCss')]
    public function testUpdatesContentCss(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/content/css', [
            'css' => '.workflow-content-' . self::$suffix . ' { color: #123456; }',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateContentCss', $this->bodyValue($updated, 'transitionId'));

        return $updated;
    }

    #[Alps('goContentJs')]
    #[Depends('testUpdatesContentCss')]
    public function testContentJs(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goContentJs');
    }

    #[Alps('doUpdateContentJs')]
    #[Depends('testContentJs')]
    public function testUpdatesContentJs(ResourceObject $response): void
    {
        $updated = $this->resource->put('page://self/admin/content/js', [
            'js' => 'window.workflowContent' . self::$suffix . ' = true;',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateContentJs', $this->bodyValue($updated, 'transitionId'));
    }
}
