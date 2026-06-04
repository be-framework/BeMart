<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Method;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri as ResourceUri;
use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Tests\Support\UnsupportedResourceOperationException;
use Override;

use function array_key_exists;
use function escapeshellarg;
use function explode;
use function file_exists;
use function file_put_contents;
use function http_build_query;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/**
 * ResourceInterface backed by a real HTTP round-trip.
 *
 * The PHP built-in server is managed by koriym/php-server (the maintained
 * component BEAR itself uses); requests are issued with curl against a
 * per-instance cookie jar so the session survives across the workflow.
 */
final class HttpResource implements ResourceInterface
{
    /** @var array<string, PhpServer> */
    private static array $servers = [];

    private readonly string $baseUri;
    private readonly string $cookieJar;

    public function __construct(
        string $host,
        string $index,
        private readonly string $logFile = 'php://stderr',
    ) {
        $this->baseUri = sprintf('http://%s', $host);
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-http-cookie-');
        $this->startServer($host, $index);
        $this->resetLog();
    }

    private function startServer(string $host, string $index): void
    {
        $serverKey = $host . ' ' . $index;
        if (array_key_exists($serverKey, self::$servers)) {
            return;
        }

        $server = new PhpServer($host, $index);
        $server->start();
        self::$servers[$serverKey] = $server;
    }

    #[Override]
    public function newInstance($uri): ResourceObject
    {
        throw new UnsupportedResourceOperationException('newInstance is not used by workflow tests.');
    }

    #[Override]
    public function object(ResourceObject $ro): RequestInterface
    {
        throw new UnsupportedResourceOperationException('object is not used by workflow tests.');
    }

    #[Override]
    public function uri($uri): RequestInterface
    {
        throw new UnsupportedResourceOperationException('uri is not used by workflow tests.');
    }

    #[Override]
    public function newRequest(Method $method, string $uri, array $query = []): RequestInterface
    {
        throw new UnsupportedResourceOperationException('newRequest is not used by workflow tests.');
    }

    #[Override]
    public function crawl(string $uri, string $linkKey, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('crawl is not used by workflow tests.');
    }

    #[Override]
    public function href(string $rel, array $query = [], ResourceObject|null $ro = null): ResourceObject
    {
        throw new UnsupportedResourceOperationException('href is not used by workflow tests.');
    }

    #[Override]
    public function get(string $uri, array $query = []): ResourceObject
    {
        return $this->request('GET', $uri, $query);
    }

    #[Override]
    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->request('POST', $uri, $query);
    }

    #[Override]
    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->request('PUT', $uri, $query);
    }

    #[Override]
    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->request('PATCH', $uri, $query);
    }

    #[Override]
    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->request('DELETE', $uri, $query);
    }

    #[Override]
    public function head(string $uri, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('head is not used by workflow tests.');
    }

    #[Override]
    public function options(string $uri, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('options is not used by workflow tests.');
    }

    /** @param array<string, mixed> $query */
    private function request(string $method, string $uri, array $query): ResourceObject
    {
        $url = $this->url($method, $uri, $query);
        $raw = $this->runHttp($method, $url, $query);
        [$responseHeaders, $view] = $this->splitResponse($raw);

        $ro = new HttpResponse();
        $resourceUri = new ResourceUri($url);
        $resourceUri->method = strtolower($method);
        $ro->uri = $resourceUri;
        $ro->code = $this->statusCode($responseHeaders);
        $ro->headers = $this->headers($responseHeaders);
        $ro->view = $view;
        $ro->body = $this->body($view);
        $this->log($method, $url, $query, $responseHeaders, $view);

        return $ro;
    }

    /** @param array<string, mixed> $query */
    private function url(string $method, string $uri, array $query): string
    {
        $uri = self::httpPath($uri);
        if ($method !== 'GET' || $query === []) {
            return $this->baseUri . $uri;
        }

        $separator = str_contains($uri, '?') ? '&' : '?';

        return $this->baseUri . $uri . $separator . http_build_query($query);
    }

    private static function httpPath(string $uri): string
    {
        if (! str_starts_with($uri, 'page://self')) {
            return $uri;
        }

        $path = substr($uri, strlen('page://self'));

        return $path === '' ? '/' : $path;
    }

    /** @param array<string, mixed> $query */
    private function runHttp(string $method, string $url, array $query): string
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf('curl -s -i -b %s -c %s', $jar, $jar);
        if ($method !== 'GET') {
            $body = escapeshellarg(json_encode($query, JSON_THROW_ON_ERROR));
            $curl .= sprintf(" -H 'Content-Type: application/json' -X %s -d %s", $method, $body);
        }

        $curl .= ' ' . escapeshellarg($url);
        $raw = shell_exec($curl);
        if (! is_string($raw) || $raw === '') {
            throw new HttpResourceRequestException(sprintf('curl produced no response for %s %s', $method, $url));
        }

        return $raw;
    }

    /** @return array{0: list<string>, 1: string} */
    private function splitResponse(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
        if (! is_array($parts) || ! array_key_exists(1, $parts)) {
            throw new HttpResourceRequestException('HTTP response did not contain a header/body separator.');
        }

        $headers = preg_split("/\r?\n/", trim($parts[0]));
        if (! is_array($headers)) {
            throw new HttpResourceRequestException('HTTP response headers could not be parsed.');
        }

        return [$headers, $parts[1]];
    }

    /** @param list<string> $responseHeaders */
    private function statusCode(array $responseHeaders): int
    {
        foreach ($responseHeaders as $header) {
            if (! str_contains($header, 'HTTP/')) {
                continue;
            }

            if (preg_match('/\s(\d{3})\s/', $header . ' ', $match) === 1) {
                return (int) $match[1];
            }
        }

        return 200;
    }

    /**
     * @param list<string> $responseHeaders
     * @return array<string, string>
     */
    private function headers(array $responseHeaders): array
    {
        $headers = [];
        foreach ($responseHeaders as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)] = trim($value);
        }

        return $headers;
    }

    /** @return array<string, mixed> */
    private function body(string $view): array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($view, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    /** @param array<string, mixed> $query */
    private function log(string $method, string $url, array $query, array $headers, string $view): void
    {
        $log = sprintf(
            "%s %s\nquery=%s\n%s\n\n%s\n\n",
            $method,
            $url,
            json_encode($query, JSON_THROW_ON_ERROR),
            implode(PHP_EOL, $headers),
            $view,
        );
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    private function resetLog(): void
    {
        if ($this->logFile === 'php://stderr' || ! file_exists($this->logFile)) {
            return;
        }

        file_put_contents($this->logFile, '');
    }
}
