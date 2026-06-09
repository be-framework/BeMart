<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Code;
use BEAR\Resource\Uri as ResourceUri;
use PHPUnit\Framework\TestCase;

final class HttpResourceHrefTest extends TestCase
{
    private HttpResource $resource;

    protected function setUp(): void
    {
        $this->resource = new HttpResource('127.0.0.1:18080', __DIR__ . '/index.php', __DIR__ . '/log/href.log');
    }

    public function testFollowsHtmlSemanticLinkBeforeLinkHeader(): void
    {
        $source = $this->source(
            '<a class="goProductList" href="/products/list">Products</a>',
            ['Link' => '</cart>; rel="goProductList"; method="get"'],
        );

        $next = $this->resource->href('goProductList', [], $source);

        $this->assertSame(Code::OK, $next->code);
        $this->assertStringContainsString('ec-shelfRole', $next->toString());
    }

    public function testFollowsLinkHeaderFallback(): void
    {
        $source = $this->source('', ['Link' => '</cart>; rel="goCart"; method="get"']);

        $next = $this->resource->href('goCart', [], $source);

        $this->assertSame(Code::OK, $next->code);
        $this->assertStringContainsString('ec-cartRole', $next->toString());
    }

    public function testFollowsHalBeforeHtmlAndLinkHeader(): void
    {
        $source = $this->source(
            '<a class="goCart" href="/products/list">Products</a>',
            ['Link' => '</products/list>; rel="goCart"; method="get"'],
        );
        $source->body = [
            '_links' => [
                'goCart' => ['href' => '/cart'],
            ],
        ];

        $next = $this->resource->href('goCart', [], $source);

        $this->assertSame(Code::OK, $next->code);
        $this->assertStringContainsString('ec-cartRole', $next->toString());
    }

    /** @param array<string, string> $headers */
    private function source(string $view, array $headers): HttpResponse
    {
        $source = new HttpResponse();
        $source->uri = new ResourceUri('http://127.0.0.1:18080/');
        $source->code = Code::OK;
        $source->headers = $headers;
        $source->view = $view;
        $source->body = [];

        return $source;
    }
}
