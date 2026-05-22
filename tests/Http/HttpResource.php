<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Method;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri as ResourceUri;
use MyVendor\BeMart\Tests\Support\UnsupportedResourceOperationException;
use Override;

use function array_key_exists;
use function dirname;
use function explode;
use function fclose;
use function file_exists;
use function file_put_contents;
use function fwrite;
use function http_build_query;
use function implode;
use function is_array;
use function is_executable;
use function is_resource;
use function is_string;
use function json_decode;
use function json_encode;
use function parse_url;
use function preg_match;
use function preg_split;
use function proc_close;
use function proc_open;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function stripos;
use function stream_get_contents;
use function strlen;
use function substr;
use function trim;

use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const PHP_EOL;
use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;

final class HttpResource implements ResourceInterface
{
    /** @var array<string, string> */
    private array $cookies = [];

    private readonly string $baseUri;

    public function __construct(
        string $host,
        private readonly string $index,
        private readonly string $logFile = 'php://stderr',
    ) {
        $this->baseUri = sprintf('http://%s', $host);
        $this->resetLog();
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
        [$responseHeaders, $view] = $this->runCgi($method, $uri, $query);
        $this->captureCookies($responseHeaders);

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
        if ($method !== 'GET' || $query === []) {
            return $this->baseUri . $uri;
        }

        $separator = str_contains($uri, '?') ? '&' : '?';

        return $this->baseUri . $uri . $separator . $this->queryString($query);
    }

    /**
     * @param array<string, mixed> $query
     * @return array{0: list<string>, 1: string}
     */
    private function runCgi(string $method, string $uri, array $query): array
    {
        $content = $method === 'GET' ? '' : json_encode($query, JSON_THROW_ON_ERROR);
        $requestUri = $method === 'GET' && $query !== [] ? $uri . '?' . $this->queryString($query) : $uri;
        $env = [
            'APP_CONTEXT' => 'html',
            'DOCUMENT_ROOT' => dirname($this->index, 3) . '/public',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_HOST' => (string) parse_url($this->baseUri, PHP_URL_HOST),
            'QUERY_STRING' => (string) (parse_url($requestUri, PHP_URL_QUERY) ?? ''),
            'REDIRECT_STATUS' => '1',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $requestUri,
            'SCRIPT_FILENAME' => $this->index,
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => '127.0.0.1',
            'SERVER_PORT' => (string) (parse_url($this->baseUri, PHP_URL_PORT) ?? '80'),
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];

        if ($this->cookies !== []) {
            $env['HTTP_COOKIE'] = $this->cookieHeader();
        }

        if ($method !== 'GET') {
            $env['CONTENT_TYPE'] = 'application/json';
            $env['CONTENT_LENGTH'] = (string) strlen($content);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([$this->phpCgiBinary()], $descriptors, $pipes, dirname($this->index, 3), $env);
        if (! is_resource($process)) {
            throw new HttpResourceServerStartException('Could not start php-cgi for HTTP workflow request.');
        }

        fwrite($pipes[0], $content);
        fclose($pipes[0]);
        $raw = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new HttpResourceRequestException(sprintf('php-cgi failed: %s', trim((string) $error)));
        }

        if (! is_string($raw)) {
            throw new HttpResourceRequestException('php-cgi returned no response.');
        }

        return $this->splitResponse($raw);
    }

    /**
     * @return array{0: list<string>, 1: string}
     */
    private function splitResponse(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
        if (! is_array($parts) || ! array_key_exists(1, $parts)) {
            throw new HttpResourceRequestException('CGI response did not contain a header/body separator.');
        }

        $headers = preg_split("/\r?\n/", trim($parts[0]));
        if (! is_array($headers)) {
            throw new HttpResourceRequestException('CGI response headers could not be parsed.');
        }

        return [$headers, $parts[1]];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function queryString(array $query): string
    {
        return http_build_query($query);
    }

    private function phpCgiBinary(): string
    {
        $candidate = dirname(PHP_BINARY) . '/php-cgi';
        if (is_executable($candidate)) {
            return $candidate;
        }

        $homebrew = '/opt/homebrew/opt/php@8.4/bin/php-cgi';
        if (is_executable($homebrew)) {
            return $homebrew;
        }

        throw new HttpResourceServerStartException('php-cgi binary is not available.');
    }

    /** @param list<string> $responseHeaders */
    private function statusCode(array $responseHeaders): int
    {
        foreach ($responseHeaders as $header) {
            if (! str_starts_with($header, 'Status:')) {
                continue;
            }

            if (preg_match('/\d{3}/', $header, $match) === 1) {
                return (int) $match[0];
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

    /**
     * @return array<string, mixed>
     */
    private function body(string $view): array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($view, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    /** @param list<string> $responseHeaders */
    private function captureCookies(array $responseHeaders): void
    {
        foreach ($responseHeaders as $line) {
            if (stripos($line, 'Set-Cookie:') !== 0) {
                continue;
            }

            $cookie = trim(substr($line, 11));
            [$pair] = explode(';', $cookie, 2);
            if (! str_contains($pair, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $pair, 2);
            $this->cookies[$name] = $value;
        }
    }

    private function cookieHeader(): string
    {
        $pairs = [];
        foreach ($this->cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }

        return implode('; ', $pairs);
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
