<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class HelpResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function helpPagesProvider(): array
    {
        return [
            'about' => ['page://self/help/about', 'goHelpAbout', 'about'],
            'guide' => ['page://self/help/guide', 'goHelpGuide', 'guide'],
            'agreement' => ['page://self/help/agreement', 'goHelpAgreement', 'agreement'],
            'privacy' => ['page://self/help/privacy', 'goHelpPrivacy', 'privacy'],
            'trade-law' => ['page://self/help/trade-law', 'goHelpTradeLaw', 'trade-law'],
        ];
    }

    /** @dataProvider helpPagesProvider */
    public function testOnGetReturnsExpectedShape(string $uri, string $transitionId, string $page): void
    {
        $ro = $this->resource->get($uri);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($transitionId, $ro->body['transitionId']);
        $this->assertSame([], $ro->body['fields']);
        $this->assertNull($ro->body['submitTo']);
        $this->assertSame($page, $ro->body['staticContent']['page']);
        $this->assertSame('page://self/', $ro->body['links']['goTop']);
    }
}
