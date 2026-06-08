<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\EntryPoint;

use MyVendor\BeMart\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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
}
