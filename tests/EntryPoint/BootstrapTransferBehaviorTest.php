<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\EntryPoint;

use MyVendor\BeMart\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/** Characterizes Bootstrap transfer decisions before making it a thin shell. */
final class BootstrapTransferBehaviorTest extends TestCase
{
    public function testHtmlRedirectWithOkResourceCodeIsEmittedAsSeeOther(): void
    {
        $this->assertSame(303, $this->httpStatusCode(200, true, true));
    }

    public function testHtmlRedirectKeepsExplicitRedirectStatusCode(): void
    {
        $this->assertSame(302, $this->httpStatusCode(302, true, true));
    }

    public function testJsonRedirectDoesNotRewriteResourceStatusCode(): void
    {
        $this->assertSame(200, $this->httpStatusCode(200, false, true));
    }

    public function testHtmlCsvResponseIsTreatedAsDownload(): void
    {
        $this->assertTrue($this->isDownloadResponse(['Content-Type' => 'text/csv; charset=UTF-8'], true));
    }

    public function testJsonCsvResponseIsNotTreatedAsDownload(): void
    {
        $this->assertFalse($this->isDownloadResponse(['Content-Type' => 'text/csv; charset=UTF-8'], false));
    }

    public function testPdfResponseIsDownloadInAnyContext(): void
    {
        $this->assertTrue($this->isDownloadResponse(['Content-Type' => 'application/pdf'], false));
    }

    public function testDownloadBodyPrefersCsvField(): void
    {
        $this->assertSame("id,name\n1,BeMart\n", $this->downloadBody(['csv' => "id,name\n1,BeMart\n"]));
    }

    public function testDownloadBodyFallsBackToJsonEncodingForArrayBody(): void
    {
        $this->assertSame('{"message":"ok","count":1}', $this->downloadBody(['message' => 'ok', 'count' => 1]));
    }

    public function testPostRoutingUsesBodyParamsAndKeepsContentHeaders(): void
    {
        $request = $this->requestFromTarget(
            'post',
            '/admin/product?productCode=query-code',
            ['productCode' => 'body-code', 'name' => 'BeMart product'],
        );

        [$globals, $server] = $this->routingInput($request, [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_RAW_POST_DATA' => '{"productCode":"body-code"}',
        ]);

        $this->assertSame([], $globals['_GET']);
        $this->assertSame('body-code', $globals['_POST']['productCode'] ?? null);
        $this->assertSame('BeMart product', $globals['_POST']['name'] ?? null);
        $this->assertSame('post', $server['REQUEST_METHOD']);
        $this->assertSame('/admin/product?productCode=query-code', $server['REQUEST_URI']);
        $this->assertSame('application/json', $server['CONTENT_TYPE']);
        $this->assertSame('{"productCode":"body-code"}', $server['HTTP_RAW_POST_DATA']);
    }

    public function testGetRoutingUsesQueryParams(): void
    {
        $request = $this->requestFromTarget('get', '/products?categoryId=1&name=coffee', []);

        [$globals, $server] = $this->routingInput($request, []);

        $this->assertSame('1', $globals['_GET']['categoryId'] ?? null);
        $this->assertSame('coffee', $globals['_GET']['name'] ?? null);
        $this->assertSame([], $globals['_POST']);
        $this->assertSame('get', $server['REQUEST_METHOD']);
        $this->assertSame('/products?categoryId=1&name=coffee', $server['REQUEST_URI']);
    }

    public function testUploadedCsvBodyReadsImportFileBoundary(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bemart-csv-');
        $this->assertIsString($tmp);
        file_put_contents($tmp, "id,name\n1,BeMart\n");

        $previousFiles = $_FILES;
        $_FILES['import_file'] = ['tmp_name' => $tmp];

        try {
            $this->assertSame("id,name\n1,BeMart\n", $this->uploadedCsvBody());
        } finally {
            $_FILES = $previousFiles;
            unlink($tmp);
        }
    }

    private function httpStatusCode(int $resourceCode, bool $isHtml, bool $isRedirect): int
    {
        $method = new ReflectionMethod(Bootstrap::class, 'httpStatusCode');

        /** @var int */
        return $method->invoke(new Bootstrap(), $resourceCode, $isHtml, $isRedirect);
    }

    /** @param array<string, mixed> $headers */
    private function isDownloadResponse(array $headers, bool $isHtml): bool
    {
        $method = new ReflectionMethod(Bootstrap::class, 'isDownloadResponse');

        /** @var bool */
        return $method->invoke(new Bootstrap(), $headers, $isHtml);
    }

    /** @param mixed $body */
    private function downloadBody(mixed $body): string
    {
        $method = new ReflectionMethod(Bootstrap::class, 'downloadBody');

        /** @var string */
        return $method->invoke(new Bootstrap(), $body);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function requestFromTarget(string $method, string $target, array $body): object
    {
        $reflectedMethod = new ReflectionMethod(Bootstrap::class, 'requestFromTarget');

        /** @var object */
        return $reflectedMethod->invoke(new Bootstrap(), $method, $target, $body);
    }

    /**
     * @param object               $request
     * @param array<string, mixed> $server
     *
     * @return array{
     *     0: array{_GET: array<string, mixed>, _POST: array<string, mixed>},
     *     1: array{REQUEST_METHOD: string, REQUEST_URI: string, CONTENT_TYPE?: string, HTTP_RAW_POST_DATA?: string}
     * }
     */
    private function routingInput(object $request, array $server): array
    {
        $reflectedMethod = new ReflectionMethod(Bootstrap::class, 'routingInput');

        /** @var array{
         *     0: array{_GET: array<string, mixed>, _POST: array<string, mixed>},
         *     1: array{REQUEST_METHOD: string, REQUEST_URI: string, CONTENT_TYPE?: string, HTTP_RAW_POST_DATA?: string}
         * } */
        return $reflectedMethod->invoke(new Bootstrap(), $request, $server);
    }

    private function uploadedCsvBody(): string|null
    {
        $method = new ReflectionMethod(Bootstrap::class, 'uploadedCsvBody');

        /** @var string|null */
        return $method->invoke(new Bootstrap());
    }
}
