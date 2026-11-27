<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Dev\Http\HttpResource;
use BEAR\Resource\Code;
use BEAR\Resource\Exception\LinkRelException;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\Attributes\DataProviderExternal;

use function dirname;

/**
 * Follows the stories over HTTP on the Fake HAL app with an admin signed in, from the links the representations carry.
 */
final class HttpLinkStoryTest extends AbstractLinkStoryCase
{
    private const string HOST = '127.0.0.1:18491';

    private HttpResource $resource;

    protected function setUp(): void
    {
        $http = dirname(__DIR__) . '/Http';
        $this->resource = new HttpResource(self::HOST, $http . '/admin-json-index.php', $http . '/log', ['Sec-Fetch-Site' => 'same-origin']);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'followable')]
    public function testFollows(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProviderExternal(LinkStories::class, 'unfollowable')]
    public function testRefuses(string $uri, string $exception): void
    {
        if ($exception !== LinkRelException::class) {
            // The server answers a request it cannot invoke with 400 instead of throwing to the client
            $this->assertSame(Code::BAD_REQUEST, $this->resource->get($uri)->code);

            return;
        }

        $this->expectException($exception);

        $this->resource->get($uri);
    }

    /** @param array<string, string> $query */
    #[DataProviderExternal(LinkStories::class, 'uploads')]
    public function testUploads(string $uri, array $query, string $file, int $code, string $path): void
    {
        $this->assertEnd($this->resource->post($uri, $query + ['file' => FileUpload::fromFile($file)]), $code, $path, []);
    }
}
