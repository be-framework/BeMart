<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Dev\Http\Exception\HalLinkNotFoundException;
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
use function html_entity_decode;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const FILE_APPEND;
use const ENT_HTML5;
use const ENT_QUOTES;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const PREG_SET_ORDER;

/**
 * ResourceInterface backed by a real HTTP round-trip.
 *
 * The PHP built-in server is managed by koriym/php-server (the maintained
 * component BEAR itself uses); requests are issued with curl against a
 * per-instance cookie jar so the session survives across the workflow.
 *
 * @phpstan-type SemanticLink array{href: string, method: string}
 */
final class HttpResource implements ResourceInterface
{
    private static PhpServer|null $server = null;

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
        if (self::$server instanceof PhpServer) {
            return;
        }

        $server = new PhpServer($host, $index);
        $server->start();
        self::$server = $server;
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
        if ($ro === null) {
            throw new HalLinkNotFoundException('A source ResourceObject is required.');
        }

        $link = $this->halLink($rel, $ro)
            ?? $this->htmlSemanticLink($rel, $ro)
            ?? $this->linkHeaderLink($rel, $ro);
        if ($link === null) {
            throw new HalLinkNotFoundException(sprintf('Link rel `%s` is not available.', $rel));
        }

        return $this->requestLink($link, $query);
    }

    /** @return SemanticLink|null */
    private function halLink(string $rel, ResourceObject $ro): array|null
    {
        if (! is_array($ro->body)) {
            return null;
        }

        $links = $ro->body['_links'] ?? null;
        if (is_object($links)) {
            $links = (array) $links;
        }

        if (! is_array($links) || ! array_key_exists($rel, $links)) {
            return null;
        }

        $link = $this->halLinkData($links[$rel]);
        if ($link === null) {
            return null;
        }

        $href = $link['href'] ?? null;
        if (! is_string($href)) {
            return null;
        }

        $method = is_string($link['method'] ?? null) ? $link['method'] : 'get';

        return ['href' => $href, 'method' => strtolower($method)];
    }

    /** @return array<string, mixed>|null */
    private function halLinkData(mixed $link): array|null
    {
        if (is_object($link)) {
            $link = (array) $link;
        }

        if (! is_array($link)) {
            return null;
        }

        $data = [];
        foreach ($link as $key => $value) {
            if (is_string($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /** @return SemanticLink|null */
    private function htmlSemanticLink(string $rel, ResourceObject $ro): array|null
    {
        if (! is_string($ro->view) || $ro->view === '') {
            return null;
        }

        if (preg_match_all('/<(a|area|link|form)\b(?P<attrs>[^>]*)>/i', $ro->view, $matches, PREG_SET_ORDER) !== 1) {
            return null;
        }

        foreach ($matches as $match) {
            $attrs = $match['attrs'];
            if (! $this->hasLinkToken($attrs, $rel)) {
                continue;
            }

            $tag = strtolower($match[1]);
            $href = $tag === 'form' ? $this->attribute($attrs, 'action') : $this->attribute($attrs, 'href');
            if ($href === null || $href === '') {
                continue;
            }

            $method = $tag === 'form' ? strtolower($this->attribute($attrs, 'method') ?? 'get') : 'get';

            return ['href' => $href, 'method' => $method];
        }

        return null;
    }

    private function hasLinkToken(string $attrs, string $rel): bool
    {
        $relAttr = $this->attribute($attrs, 'rel');
        if ($relAttr !== null && $this->containsToken($relAttr, $rel)) {
            return true;
        }

        $classAttr = $this->attribute($attrs, 'class');

        return $classAttr !== null && $this->containsToken($classAttr, $rel);
    }

    private function attribute(string $attrs, string $name): string|null
    {
        if (preg_match('/\b' . $name . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $match) !== 1) {
            return null;
        }

        $value = $match[1] ?? $match[2] ?? $match[3] ?? '';

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
    }

    private function containsToken(string $value, string $token): bool
    {
        $tokens = preg_split('/\s+/', trim($value));
        if (! is_array($tokens)) {
            return false;
        }

        return in_array($token, $tokens, true);
    }

    /** @return SemanticLink|null */
    private function linkHeaderLink(string $rel, ResourceObject $ro): array|null
    {
        $header = $this->headerValue($ro, 'Link');
        if ($header === null) {
            return null;
        }

        foreach (preg_split('/,\s*(?=<)/', $header) ?: [] as $entry) {
            if (preg_match('/^\s*<([^>]+)>(.*)$/', $entry, $matched) !== 1) {
                continue;
            }

            $params = $this->linkHeaderParams($matched[2]);
            if (($params['rel'] ?? '') !== $rel) {
                continue;
            }

            return [
                'href' => $matched[1],
                'method' => strtolower($params['method'] ?? 'get'),
            ];
        }

        return null;
    }

    /** @return array<string, string> */
    private function linkHeaderParams(string $params): array
    {
        preg_match_all(
            '/;\s*([A-Za-z][A-Za-z0-9_-]*)\s*=\s*(?:"([^"]*)"|([^;\s]+))/',
            $params,
            $matches,
            PREG_SET_ORDER,
        );

        $result = [];
        foreach ($matches as $match) {
            $quoted = $match[2] ?? '';
            $bare = $match[3] ?? '';
            $result[$match[1]] = $quoted !== '' ? $quoted : $bare;
        }

        return $result;
    }

    private function headerValue(ResourceObject $ro, string $name): string|null
    {
        foreach ($ro->headers as $header => $value) {
            if (is_string($header) && is_string($value) && strtolower($header) === strtolower($name)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param SemanticLink $link
     * @param array<string, mixed> $query
     */
    private function requestLink(array $link, array $query): ResourceObject
    {
        $href = $this->resourcePath($link['href']);

        return match ($link['method']) {
            'get', 'head' => $this->get($href, $query),
            'post' => $this->post($href, $query),
            'put' => $this->put($href, $query),
            'patch' => $this->patch($href, $query),
            'delete' => $this->delete($href, $query),
            default => throw new HalLinkNotFoundException(
                sprintf('Link method `%s` is not supported.', $link['method']),
            ),
        };
    }

    private function resourcePath(string $href): string
    {
        if (! str_starts_with($href, 'http://') && ! str_starts_with($href, 'https://')) {
            return $href;
        }

        $path = parse_url($href, PHP_URL_PATH);
        $query = parse_url($href, PHP_URL_QUERY);
        $resourcePath = is_string($path) && $path !== '' ? $path : '/';

        return is_string($query) && $query !== '' ? $resourcePath . '?' . $query : $resourcePath;
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
        if ($method !== 'GET' || $query === []) {
            return $this->baseUri . $uri;
        }

        $separator = str_contains($uri, '?') ? '&' : '?';

        return $this->baseUri . $uri . $separator . http_build_query($query);
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
