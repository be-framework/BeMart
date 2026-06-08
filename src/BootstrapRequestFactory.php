<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use function count;
use function file_get_contents;
use function headers_sent;
use function is_array;
use function is_readable;
use function is_string;
use function json_decode;
use function parse_str;
use function parse_url;
use function session_start;
use function session_status;
use function strtolower;

use const PHP_SESSION_ACTIVE;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/** Normalizes CLI/HTTP superglobals into the RouterInterface input shape. */
final class BootstrapRequestFactory
{
    /**
     * @param array<string, mixed> $globals
     * @param array<string, mixed> $server
     */
    public function request(array $globals, array $server, bool $isCli): BootstrapRequest
    {
        return $isCli ? $this->cliRequest($globals) : $this->httpRequest($server);
    }

    public function startHtmlSession(bool $isHtml, bool $isCli): void
    {
        if (! $isHtml || $isCli || session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }

    /**
     * @param array<string, mixed> $server
     * @return array{
     *     0: array{_GET: array<string, mixed>, _POST: array<string, mixed>},
     *     1: array{REQUEST_URI: string, REQUEST_METHOD: string, CONTENT_TYPE?: string, HTTP_CONTENT_TYPE?: string, HTTP_RAW_POST_DATA?: string}
     * }
     */
    public function routingInput(BootstrapRequest $request, array $server): array
    {
        $method = strtolower($request->method);
        $routingServer = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $request->target,
        ];
        foreach (['CONTENT_TYPE', 'HTTP_CONTENT_TYPE', 'HTTP_RAW_POST_DATA'] as $key) {
            if (isset($server[$key]) && is_string($server[$key])) {
                $routingServer[$key] = $server[$key];
            }
        }

        $globals = ['_GET' => [], '_POST' => []];
        if ($method === 'get' || $method === 'head') {
            $globals['_GET'] = $request->params;
        } else {
            $globals['_POST'] = $request->params;
        }

        return [$globals, $routingServer];
    }

    /** @param array<string, mixed> $globals */
    private function cliRequest(array $globals): BootstrapRequest
    {
        /** @var list<string> $argv */
        $argv = isset($globals['argv']) && is_array($globals['argv']) ? $globals['argv'] : [];
        if (count($argv) < 3) {
            throw new InvalidCliRequestException('Usage: php bin/*.php <method> <path-with-query>');
        }

        $method = strtolower((string) $argv[1]);
        $target = (string) $argv[2];

        return $this->requestFromTarget($method, $target, []);
    }

    /** @param array<string, mixed> $server */
    private function httpRequest(array $server): BootstrapRequest
    {
        $method = strtolower((string) ($server['REQUEST_METHOD'] ?? 'get'));
        $target = (string) ($server['REQUEST_URI'] ?? '/');
        $body = [];
        if ($method !== 'get') {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                /** @var mixed $decoded */
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    /** @var array<string, mixed> $decoded */
                    $body = $decoded;
                }
            }

            /** @psalm-suppress MixedAssignment */
            foreach ($_POST as $key => $value) {
                if (is_string($key)) {
                    $body[$key] = $value;
                }
            }

            $uploadedCsv = $this->uploadedCsvBody();
            if ($uploadedCsv !== null && (($body['csv'] ?? '') === '')) {
                $body['csv'] = $uploadedCsv;
            }
        }

        return $this->requestFromTarget($method, $target, $body);
    }

    private function uploadedCsvBody(): string|null
    {
        /** @var mixed $file */
        $file = $_FILES['import_file'] ?? null;
        if (! is_array($file)) {
            return null;
        }

        /** @var mixed $tmpName */
        $tmpName = $file['tmp_name'] ?? null;
        if (! is_string($tmpName) || $tmpName === '' || ! is_readable($tmpName)) {
            return null;
        }

        $csv = file_get_contents($tmpName);

        return is_string($csv) ? $csv : null;
    }

    /** @param array<string, mixed> $body */
    private function requestFromTarget(string $method, string $target, array $body): BootstrapRequest
    {
        $path = (string) (parse_url($target, PHP_URL_PATH) ?? '/');
        $queryString = (string) (parse_url($target, PHP_URL_QUERY) ?? '');
        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        /** @var array<string, mixed> $query */
        $params = $body + $query;

        return new BootstrapRequest($method, $target, $path, $params);
    }
}
