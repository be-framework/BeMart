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

use function assert;
use function bin2hex;
use function count;
use function random_bytes;

class FlowAdminContentPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-content-publish';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csrf-token';

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

        return self::$dbSession->resource();
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
        $created = $this->resource->post($this->linkHref($response, 'doCreateNews'), [
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
        $newsList = $this->follow($response, 'goNewsList');

        $updated = $this->resource->put($this->linkHref($newsList, 'doUpdateNews'), [
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

        $readback = $this->follow($updated, 'goNews', ['newsId' => $newsId]);
        $this->assertSame('Workflow News Updated ' . self::$suffix, $this->bodyValue($readback, 'newsTitle'));
        $this->assertSame('Updated by flow-admin-content-publish.', $this->bodyValue($readback, 'newsDescription'));

        return $updated;
    }

    #[Alps('doDeleteNews')]
    #[Depends('testUpdatesNews')]
    public function testDeletesNews(ResourceObject $response): ResourceObject
    {
        $newsId = $this->bodyValue($response, 'newsId');
        $this->assertIsString($newsId);
        $news = $this->follow($response, 'goNews', ['newsId' => $newsId]);
        $newsList = $this->follow($news, 'goNewsList');

        $deleted = $this->resource->delete($this->linkHref($newsList, 'doDeleteNews'), [
            'newsId' => $newsId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($newsId, $this->bodyValue($deleted, 'newsId'));

        $readbackList = $this->follow($deleted, 'goNewsList');
        foreach ((array) ($readbackList->body['news'] ?? []) as $newsItem) {
            $news = (array) $newsItem;
            $this->assertNotSame($newsId, (string) ($news['newsId'] ?? ''));
        }

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
        $created = $this->resource->post($this->linkHref($response, 'doCreatePage'), [
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
        $pageList = $this->follow($response, 'goPageList');

        $updated = $this->resource->put($this->linkHref($pageList, 'doUpdatePage'), [
            'pageId' => $pageId,
            'pageName' => 'Workflow Page Updated ' . self::$suffix,
            'pageUrl' => 'workflow-page-updated-' . self::$suffix,
            'pageFileName' => 'workflow_page_updated_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($pageId, $this->bodyValue($updated, 'pageId'));
        $this->assertSame('Workflow Page Updated ' . self::$suffix, $this->bodyValue($updated, 'pageName'));

        $readback = $this->follow($updated, 'goPage', ['pageId' => $pageId]);
        $this->assertSame('Workflow Page Updated ' . self::$suffix, $this->bodyValue($readback, 'pageName'));
        $this->assertSame('workflow-page-updated-' . self::$suffix, $this->bodyValue($readback, 'pageUrl'));

        return $updated;
    }

    #[Alps('doDeletePage')]
    #[Depends('testUpdatesPage')]
    public function testDeletesPage(ResourceObject $response): ResourceObject
    {
        $pageId = $this->bodyValue($response, 'pageId');
        $this->assertIsString($pageId);
        $page = $this->follow($response, 'goPage', ['pageId' => $pageId]);
        $pageList = $this->follow($page, 'goPageList');

        $deleted = $this->resource->delete($this->linkHref($pageList, 'doDeletePage'), [
            'pageId' => $pageId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($pageId, $this->bodyValue($deleted, 'pageId'));

        $readbackList = $this->follow($deleted, 'goPageList');
        foreach ((array) ($readbackList->body['pages'] ?? []) as $pageItem) {
            $page = (array) $pageItem;
            $this->assertNotSame($pageId, (string) ($page['pageId'] ?? ''));
        }

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
        $created = $this->resource->post($this->linkHref($response, 'doCreateBlock'), [
            'blockName' => 'Workflow Block ' . self::$suffix,
            'blockFileName' => 'workflow_block_' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame('Workflow Block ' . self::$suffix, $this->bodyValue($created, 'blockName'));

        return $created;
    }

    #[Alps('goBlock')]
    #[Depends('testCreatesBlock')]
    public function testOpensCreatedBlock(ResourceObject $response): ResourceObject
    {
        $opened = $this->followLocation($response);
        $this->assertSame($this->bodyValue($response, 'blockId'), $this->bodyValue($opened, 'blockId'));
        $this->assertSame('Workflow Block ' . self::$suffix, $this->bodyValue($opened, 'blockName'));

        return $opened;
    }

    #[Alps('doUpdateBlock')]
    #[Depends('testOpensCreatedBlock')]
    public function testUpdatesBlock(ResourceObject $response): ResourceObject
    {
        $blockId = $this->bodyValue($response, 'blockId');
        $this->assertIsString($blockId);
        $blockList = $this->follow($response, 'goBlockList');

        $updated = $this->resource->put($this->linkHref($blockList, 'doUpdateBlock'), [
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
        $blockList = $this->follow($response, 'goBlockList');

        $deleted = $this->resource->delete($this->linkHref($blockList, 'doDeleteBlock'), [
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

    #[Alps('goLayout')]
    #[Depends('testLayoutList')]
    public function testOpensLayout(ResourceObject $response): ResourceObject
    {
        $layouts = $this->bodyValue($response, 'layouts');
        $this->assertIsArray($layouts);
        $this->assertGreaterThan(0, count($layouts));
        $layout = $layouts[0];
        $this->assertIsArray($layout);
        $this->assertArrayHasKey('layoutId', $layout);
        $this->assertIsString($layout['layoutId']);

        $layoutForm = $this->follow($response, 'goLayout', ['layoutId' => $layout['layoutId']]);
        $this->assertSame($layout['layoutId'], $this->bodyValue($layoutForm, 'layoutId'));

        return $layoutForm;
    }

    #[Alps('doUpdateLayout')]
    #[Depends('testOpensLayout')]
    public function testUpdatesLayout(ResourceObject $response): ResourceObject
    {
        $layoutId = $this->bodyValue($response, 'layoutId');
        $this->assertIsString($layoutId);

        $updated = $this->resource->put($this->linkHref($response, 'doUpdateLayout'), [
            'layoutId' => $layoutId,
            'layoutName' => 'Workflow Layout ' . self::$suffix,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame($layoutId, $this->bodyValue($updated, 'layoutId'));
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
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateTradeLaw'), [
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateContentCss'), [
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
        $updated = $this->resource->put($this->linkHref($response, 'doUpdateContentJs'), [
            'js' => 'window.workflowContent' . self::$suffix . ' = true;',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateContentJs', $this->bodyValue($updated, 'transitionId'));
    }
}
