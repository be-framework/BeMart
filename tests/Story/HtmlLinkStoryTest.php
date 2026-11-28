<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Dev\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\LocalServerHost;
use PHPUnit\Framework\Attributes\DataProviderExternal;

use function dirname;

/**
 * Follows the guest stories over HTTP on the Fake HTML app, from the links and forms the pages carry.
 *
 * The admin stories are not followed here: this app has no admin signed in, and the admin forms send PUT and DELETE
 * as a POST with `_method`, which the client refuses.
 */
final class HtmlLinkStoryTest extends AbstractLinkStoryCase
{
    private HttpResource $resource;

    protected function setUp(): void
    {
        $http = dirname(__DIR__) . '/Http';
        $this->resource = new HttpResource(LocalServerHost::for(self::class)->host, $http . '/index.php', $http . '/log', ['Sec-Fetch-Site' => 'same-origin']);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forGuests')]
    public function testFollows(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
    }
}
