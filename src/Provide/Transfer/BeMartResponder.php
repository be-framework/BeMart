<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Transfer;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use MyVendor\BeMart\BootstrapRequest;
use Override;

use function header;
use function http_response_code;
use function is_array;
use function is_string;
use function json_encode;
use function ob_end_clean;
use function ob_start;
use function strtolower;
use function str_contains;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;
use const PHP_SAPI;

/** BeMart transfer adapter that preserves legacy CLI/download behavior outside Bootstrap. */
final class BeMartResponder implements TransferInterface
{
    /**
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    public static function withRouteContext(
        array $server,
        string $context,
        BootstrapRequest $request,
        string $routeMethod,
        string $routePath,
    ): array {
        $server['_BEMART_CONTEXT'] = $context;
        $server['_BEMART_IS_CLI'] = PHP_SAPI === 'cli' ? '1' : '0';
        $server['_BEMART_IS_HTML'] = str_contains($context, 'html') ? '1' : '0';
        $server['_BEMART_TARGET'] = $request->target;
        $server['_BEMART_ROUTE_METHOD'] = $routeMethod;
        $server['_BEMART_ROUTE_PATH'] = $routePath;

        return $server;
    }

    /** {@inheritDoc} */
    #[Override]
    public function __invoke(ResourceObject $ro, array $server): void
    {
        $context = is_string($server['_BEMART_CONTEXT'] ?? null) ? $server['_BEMART_CONTEXT'] : '';
        $isCli = ($server['_BEMART_IS_CLI'] ?? null) === '1' || (($server['_BEMART_IS_CLI'] ?? null) === null && PHP_SAPI === 'cli');
        $isHtml = ($server['_BEMART_IS_HTML'] ?? null) === '1' || (($server['_BEMART_IS_HTML'] ?? null) === null && str_contains($context, 'html'));
        $isRedirect = isset($ro->headers['Location']);
        $isDownload = ! $isRedirect && $this->isDownloadResponse($ro->headers, $isHtml);

        ob_start();
        $view = $isHtml && ! $isRedirect && ! $isDownload ? $ro->toString() : null;
        ob_end_clean();

        if ($isCli) {
            $this->transferCli($ro, $server, $context, $isHtml, $view);

            return;
        }

        $this->transferHttp($ro, $isHtml, $isRedirect, $isDownload, $view);
    }

    /** @param array<string, mixed> $server */
    private function transferCli(ResourceObject $ro, array $server, string $context, bool $isHtml, string|null $view): void
    {
        if ($isHtml) {
            echo (string) $view;

            return;
        }

        echo json_encode([
            'context' => $context,
            'method' => $server['_BEMART_ROUTE_METHOD'] ?? '',
            'path' => $server['_BEMART_TARGET'] ?? '',
            'uri' => $server['_BEMART_ROUTE_PATH'] ?? '',
            'code' => $ro->code,
            'body' => $ro->body,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    }

    private function transferHttp(ResourceObject $ro, bool $isHtml, bool $isRedirect, bool $isDownload, string|null $view): void
    {
        $statusCode = $this->httpStatusCode($ro->code, $isHtml, $isRedirect);
        http_response_code($statusCode);

        if ($isHtml) {
            $this->emitHeaders($ro->headers, $statusCode);
            if (! isset($ro->headers['Content-Type'])) {
                header('Content-Type: text/html; charset=utf-8');
            }

            echo $isDownload ? $this->downloadBody($ro->body) : (string) $view;

            return;
        }

        if ($isDownload) {
            $this->emitHeaders($ro->headers, $statusCode);
            echo $this->downloadBody($ro->body);

            return;
        }

        $view = $ro->toString();
        $this->emitHeaders($ro->headers, $statusCode);
        if (! isset($ro->headers['Content-Type'])) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo $view;
    }

    /** @param array<string, mixed> $headers */
    private function emitHeaders(array $headers, int $statusCode): void
    {
        foreach ($headers as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $this->emitHeader($name, $value, $statusCode);
            }
        }
    }

    private function emitHeader(string $name, string $value, int $statusCode): void
    {
        if (strtolower($name) === 'location') {
            header($name . ': ' . $value, true, $statusCode);

            return;
        }

        header($name . ': ' . $value);
    }

    private function httpStatusCode(int $resourceCode, bool $isHtml, bool $isRedirect): int
    {
        if ($isHtml && $isRedirect && ($resourceCode < 300 || $resourceCode >= 400)) {
            return Code::SEE_OTHER;
        }

        return $resourceCode;
    }

    /** @param array<string, mixed> $headers */
    private function isDownloadResponse(array $headers, bool $isHtml): bool
    {
        $contentType = $headers['Content-Type'] ?? null;
        if (! is_string($contentType)) {
            return false;
        }

        if (str_contains($contentType, 'application/pdf') || str_contains($contentType, 'application/zip')) {
            return true;
        }

        return $isHtml && ! str_contains($contentType, 'text/html');
    }

    /** @param mixed $body */
    private function downloadBody(mixed $body): string
    {
        if (! is_array($body)) {
            return (string) $body;
        }

        foreach (['csv', 'pdf'] as $key) {
            $value = $body[$key] ?? null;
            if (is_string($value)) {
                return $value;
            }
        }

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
