<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;

use function assert;

class FlowAdminContentPublishTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-content-publish';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goNewsList')]
    public function testNewsList(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open news list.');
    }

    #[Alps('doCreateNews')]
    #[Depends('testNewsList')]
    public function testCreatesNews(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish create news.');
    }

    #[Alps('goNews')]
    #[Depends('testCreatesNews')]
    public function testReadsNews(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish read created news.');
    }

    #[Alps('doUpdateNews')]
    #[Depends('testReadsNews')]
    public function testUpdatesNews(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update news.');
    }

    #[Alps('doDeleteNews')]
    #[Depends('testUpdatesNews')]
    public function testDeletesNews(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish delete news.');
    }

    #[Alps('goPageList')]
    #[Depends('testDeletesNews')]
    public function testPageList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open page list.');
    }

    #[Alps('doCreatePage')]
    #[Depends('testPageList')]
    public function testCreatesPage(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish create page.');
    }

    #[Alps('doUpdatePage')]
    #[Depends('testCreatesPage')]
    public function testUpdatesPage(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update page.');
    }

    #[Alps('doDeletePage')]
    #[Depends('testUpdatesPage')]
    public function testDeletesPage(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish delete page.');
    }

    #[Alps('goBlockList')]
    #[Depends('testDeletesPage')]
    public function testBlockList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open block list.');
    }

    #[Alps('doCreateBlock')]
    #[Depends('testBlockList')]
    public function testCreatesBlock(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish create block.');
    }

    #[Alps('doUpdateBlock')]
    #[Depends('testCreatesBlock')]
    public function testUpdatesBlock(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update block.');
    }

    #[Alps('doDeleteBlock')]
    #[Depends('testUpdatesBlock')]
    public function testDeletesBlock(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish delete block.');
    }

    #[Alps('goLayoutList')]
    #[Depends('testDeletesBlock')]
    public function testLayoutList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open layout list.');
    }

    #[Alps('doUpdateLayout')]
    #[Depends('testLayoutList')]
    public function testUpdatesLayout(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update layout.');
    }

    #[Alps('goTradeLawList')]
    #[Depends('testUpdatesLayout')]
    public function testTradeLawList(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open trade law list.');
    }

    #[Alps('doUpdateTradeLaw')]
    #[Depends('testTradeLawList')]
    public function testUpdatesTradeLaw(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update trade law.');
    }

    #[Alps('goContentCss')]
    #[Depends('testUpdatesTradeLaw')]
    public function testContentCss(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open CSS content editor.');
    }

    #[Alps('doUpdateContentCss')]
    #[Depends('testContentCss')]
    public function testUpdatesContentCss(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update CSS content.');
    }

    #[Alps('goContentJs')]
    #[Depends('testUpdatesContentCss')]
    public function testContentJs(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish open JavaScript content editor.');
    }

    #[Alps('doUpdateContentJs')]
    #[Depends('testContentJs')]
    public function testUpdatesContentJs(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-content-publish update JavaScript content.');
    }
}
