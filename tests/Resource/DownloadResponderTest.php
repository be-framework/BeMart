<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Provide\Transfer\ConditionalResponseInterface;
use BEAR\Sunday\Provide\Transfer\HeaderInterface;
use BEAR\Sunday\Provide\Transfer\Output;
use MyVendor\BeMart\Provide\Transfer\ApiDownloadContentTypePolicy;
use MyVendor\BeMart\Provide\Transfer\DownloadResponder;
use MyVendor\BeMart\Provide\Transfer\HtmlDownloadContentTypePolicy;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/** Ensures download-specific transfer behavior lives outside Bootstrap. */
final class DownloadResponderTest extends TestCase
{
    public function testCsvDownloadUsesCsvBodyField(): void
    {
        $ro = $this->resourceWithDownload('text/csv; charset=UTF-8', ['csv' => "id,name\n1,BeMart\n"]);

        $this->assertTrue($this->isDownload($ro, html: true));
        $output = $this->downloadOutput($ro);

        $this->assertSame(Code::OK, $output->code);
        $this->assertSame('text/csv; charset=UTF-8', $output->headers['Content-Type'] ?? null);
        $this->assertSame("id,name\n1,BeMart\n", $output->view);
    }

    public function testPdfDownloadUsesPdfBodyField(): void
    {
        $ro = $this->resourceWithDownload('application/pdf', ['pdf' => "%PDF-1.4\n"]);

        $this->assertTrue($this->isDownload($ro));
        $this->assertSame("%PDF-1.4\n", $this->downloadOutput($ro)->view);
    }

    public function testZipDownloadUsesScalarBody(): void
    {
        $ro = $this->resourceWithDownload('application/zip', 'PKZIP');

        $this->assertTrue($this->isDownload($ro));
        $this->assertSame('PKZIP', $this->downloadOutput($ro)->view);
    }

    public function testArrayDownloadBodyFallsBackToJson(): void
    {
        $ro = $this->resourceWithDownload('application/octet-stream', ['message' => 'ok', 'count' => 1]);

        $this->assertTrue($this->isDownload($ro));
        $this->assertSame('{"message":"ok","count":1}', $this->downloadOutput($ro)->view);
    }

    public function testCsvInHalContextIsNotDownload(): void
    {
        $ro = $this->resourceWithDownload('text/csv; charset=UTF-8', ['csv' => "id,name\n1,BeMart\n"]);

        $this->assertFalse($this->isDownload($ro));
    }

    public function testCsvInHtmlPolicyIsDownload(): void
    {
        $ro = $this->resourceWithDownload('text/csv; charset=UTF-8', ['csv' => "id,name\n1,BeMart\n"]);

        $this->assertTrue($this->isDownload($ro, html: true));
    }

    public function testJsonInHtmlContextIsDirectOutput(): void
    {
        $ro = $this->resourceWithDownload('application/json; charset=utf-8', ['message' => 'ok', 'count' => 1]);

        $this->assertTrue($this->isDownload($ro, html: true));
        $this->assertSame('{"message":"ok","count":1}', $this->downloadOutput($ro)->view);
    }

    public function testHalJsonIsNotDownload(): void
    {
        $ro = $this->resourceWithDownload('application/hal+json', ['message' => 'ok']);

        $this->assertFalse($this->isDownload($ro));
    }

    /** @param mixed $body */
    private function resourceWithDownload(string $contentType, mixed $body): ResourceObject
    {
        $ro = new DownloadResponderTestResource();
        $ro->code = Code::OK;
        $ro->headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="download"',
        ];
        $ro->body = $body;

        return $ro;
    }

    private function isDownload(ResourceObject $ro, bool $html = false): bool
    {
        $method = new ReflectionMethod(DownloadResponder::class, 'isDownload');

        return (bool) $method->invoke($this->responder($html), $ro, []);
    }

    private function downloadOutput(ResourceObject $ro): Output
    {
        $method = new ReflectionMethod(DownloadResponder::class, 'downloadOutput');

        /** @var Output */
        return $method->invoke($this->responder(), $ro);
    }

    private function responder(bool $html = false): DownloadResponder
    {
        $apiPolicy = new ApiDownloadContentTypePolicy();

        return new DownloadResponder(
            new class implements HeaderInterface {
                /** {@inheritDoc} */
                public function __invoke(ResourceObject $ro, array $server): array
                {
                    unset($server);

                    /** @var array<string, string> */
                    return $ro->headers;
                }
            },
            new class implements ConditionalResponseInterface {
                /** {@inheritDoc} */
                public function isModified(ResourceObject $ro, array $server): bool
                {
                    unset($ro, $server);

                    return true;
                }

                /** {@inheritDoc} */
                public function getOutput(array $headers): Output
                {
                    unset($headers);

                    return new Output(Code::NOT_MODIFIED, [], '');
                }
            },
            $html ? new HtmlDownloadContentTypePolicy($apiPolicy) : $apiPolicy,
        );
    }
}

final class DownloadResponderTestResource extends ResourceObject
{
}
