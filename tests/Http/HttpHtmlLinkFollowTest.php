<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Code;
use PHPUnit\Framework\TestCase;

use function is_string;
use function strtolower;

final class HttpHtmlLinkFollowTest extends TestCase
{
    public function testFollowsLinkHeaderRenderedFromLinkAttribute(): void
    {
        $resource = new HttpResource(
            '127.0.0.1:8097',
            __DIR__ . '/index.php',
            __DIR__ . '/log/html-link-follow.log',
        );

        $index = $resource->get('page://self/');
        $this->assertSame(Code::OK, $index->code);
        $this->assertStringContainsString('text/html', $this->header($index->headers, 'Content-Type'));
        $this->assertStringContainsString('rel="goProductList"', $this->header($index->headers, 'Link'));

        $products = $resource->href('goProductList', [], $index);
        $this->assertSame(Code::OK, $products->code);
        $this->assertStringContainsString('text/html', $this->header($products->headers, 'Content-Type'));
    }

    /** @param array<string, mixed> $headers */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $header => $value) {
            if (! is_string($header) || ! is_string($value)) {
                continue;
            }

            if (strtolower($header) === strtolower($name)) {
                return $value;
            }
        }

        self::fail('Header not found: ' . $name);
    }
}
