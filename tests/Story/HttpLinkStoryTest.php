<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Dev\Http\HttpResource;
use BEAR\Resource\Code;
use BEAR\Resource\Exception\ParameterException;
use Koriym\FileUpload\FileUpload;
use MyVendor\BeMart\Tests\Support\LocalServerHost;
use PHPUnit\Framework\Attributes\DataProviderExternal;

use function dirname;

/**
 * Follows the stories over HTTP on the Fake HAL app with an admin signed in, from the links the representations carry,
 * as a guest unless a test signs a customer in.
 */
final class HttpLinkStoryTest extends AbstractLinkStoryCase
{
    private HttpResource $resource;

    protected function setUp(): void
    {
        $this->signIn(null);
    }

    /** The guest rows also pass as the buyer; the order history opens to a signed-in customer only */
    public function testRunsAsGuestUnlessSignedIn(): void
    {
        $this->assertSame(Code::UNAUTHORIZED, $this->resource->get('page://self/mypage/order-history')->code);
        $this->signIn(StoryModule::BUYER_ID);
        $this->assertSame(Code::OK, $this->resource->get('page://self/mypage/order-history')->code);
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

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forAdminOrders')]
    public function testFollowsAdminOrders(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forMembers')]
    public function testFollowsAsMember(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->signIn(StoryModule::MEMBER_ID);
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forBuyers')]
    public function testFollowsAsBuyer(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->signIn(StoryModule::BUYER_ID);
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
    }

    /**
     * @param class-string<\Throwable> $exception
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'unfollowable')]
    public function testRefuses(string $uri, string $exception, string|array $flow): void
    {
        $this->assertFlow($uri, $flow);
        if ($exception === ParameterException::class) {
            // The server answers a request it cannot invoke with 400 instead of throwing to the client
            $this->assertSame(Code::BAD_REQUEST, $this->resource->get($uri)->code);

            return;
        }

        $this->expectException($exception);

        $this->resource->get($uri);
    }

    /**
     * @param array<string, string> $query
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'uploads')]
    public function testUploads(string $uri, array $query, string $file, int $code, string $path, string|array $flow): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->post($uri, $query + ['file' => FileUpload::fromFile($file)]), $code, $path, []);
    }

    /** @param non-empty-string|null $customerId sent as the header tests/Http/story-json-index.php signs the customer in with */
    private function signIn(string|null $customerId): void
    {
        $http = dirname(__DIR__) . '/Http';
        $headers = ['Sec-Fetch-Site' => 'same-origin'];
        if ($customerId !== null) {
            $headers['X-BeMart-Test-Customer-Id'] = $customerId;
        }

        $this->resource = new HttpResource(LocalServerHost::for(self::class)->host, $http . '/story-json-index.php', $http . '/log', $headers);
    }
}
