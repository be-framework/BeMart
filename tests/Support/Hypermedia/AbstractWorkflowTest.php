<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function html_entity_decode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_split;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;

abstract class AbstractWorkflowTest extends TestCase
{
    /** @var array<class-string, ResourceInterface> */
    private static array $resources = [];
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->resource = self::$resources[static::class] ??= $this->newResource();
    }

    public static function tearDownAfterClass(): void
    {
        unset(self::$resources[static::class]);
    }

    abstract protected function newResource(): ResourceInterface;

    /**
     * Follows a safe HAL/Resource link with GET.
     *
     * Unsafe `do*` transitions call post/put/delete directly in the workflow
     * step because HAL links do not carry an HTTP method.
     *
     * @param array<string, mixed> $query
     */
    protected function follow(ResourceObject $response, string $rel, array $query = []): ResourceObject
    {
        $next = $this->resource->href($rel, $query, $response);
        $this->assertSame(Code::OK, $next->code);

        return $next;
    }

    protected function linkHref(ResourceObject $response, string $rel): string
    {
        $href = $this->halHref($response, $rel) ?? $this->linkHeaderHref($response, $rel);
        $this->assertIsString($href, sprintf('Link rel `%s` should be present in the representation.', $rel));

        return $this->resourceUriForLocation($href);
    }

    protected function followLocation(ResourceObject $response, string|null $expectedLocation = null): ResourceObject
    {
        $location = $this->header($response, 'Location');
        $this->assertIsString($location);
        if ($expectedLocation !== null) {
            $this->assertSame($expectedLocation, $location);
        }

        $next = $this->resource->get($this->resourceUriForLocation($location));
        $this->assertSame(Code::OK, $next->code);

        return $next;
    }

    protected function bodyValue(ResourceObject $response, string $key): mixed
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $this->assertArrayHasKey($key, $body);

        return $body[$key];
    }

    protected function bodyString(ResourceObject $response, string $key): string
    {
        $value = $this->bodyValue($response, $key);
        $this->assertIsString($value, sprintf('Expected body key `%s` to be a string.', $key));

        return $value;
    }

    protected function header(ResourceObject $response, string $name): string|null
    {
        foreach ($response->headers as $header => $value) {
            if (! is_string($header) || ! is_string($value)) {
                continue;
            }

            if (strtolower($header) !== strtolower($name)) {
                continue;
            }

            return $value;
        }

        return null;
    }

    private function halHref(ResourceObject $response, string $rel): string|null
    {
        $body = $response->body;
        if (! is_array($body)) {
            return null;
        }

        $links = $body['_links'] ?? null;
        if (! is_array($links) || ! array_key_exists($rel, $links)) {
            return null;
        }

        $link = $links[$rel];
        if (! is_array($link)) {
            return null;
        }

        $href = $link['href'] ?? null;

        return is_string($href) ? $href : null;
    }

    private function linkHeaderHref(ResourceObject $response, string $rel): string|null
    {
        $header = $this->header($response, 'Link');
        if ($header === null) {
            return null;
        }

        $links = preg_split('/,\s*(?=<)/', $header);
        if (! is_array($links)) {
            return null;
        }

        foreach ($links as $link) {
            if (preg_match('/^\s*<([^>]*)>\s*(.*)$/', $link, $match) !== 1) {
                continue;
            }

            $linkRel = $this->linkHeaderParam($match[2], 'rel');
            if ($linkRel === null || ! $this->containsToken($linkRel, $rel)) {
                continue;
            }

            return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }

    private function linkHeaderParam(string $attrs, string $name): string|null
    {
        if (preg_match('/(?:^|;)\s*' . preg_quote($name, '/') . '\s*=\s*(?:"([^"]*)"|([^;\s]+))/i', $attrs, $match) !== 1) {
            return null;
        }

        return $match[1] ?? $match[2] ?? null;
    }

    private function containsToken(string $value, string $token): bool
    {
        $tokens = preg_split('/\s+/', trim($value));
        if (! is_array($tokens)) {
            return false;
        }

        return in_array($token, $tokens, true);
    }

    private function resourceUriForLocation(string $location): string
    {
        if (str_starts_with($location, '/')) {
            return 'page://self' . $location;
        }

        return $location;
    }
}
