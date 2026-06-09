<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Provide\Transfer\ConditionalResponseInterface;
use BEAR\Sunday\Provide\Transfer\HeaderInterface;
use BEAR\Sunday\Provide\Transfer\Output;
use MyVendor\BeMart\Provide\Transfer\DownloadResponder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/** Ensures download-specific transfer behavior lives outside Bootstrap. */
final class DownloadResponderTest extends TestCase
{
    public function testCsvDownloadUsesCsvBodyField(): void
    {
        $ro = $this->resourceWithDownload('text/csv; charset=UTF-8', ['csv' => "id,name\n1,BeMart\n"]);

        $this->assertTrue($this->isDownload($ro, ['_BEMART_CONTEXT' => 'html-hal-app']));
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

        $this->assertFalse($this->isDownload($ro, ['_BEMART_CONTEXT' => 'http-prod-hal-api-app']));
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

    /** @param array<string, mixed> $server */
    private function isDownload(ResourceObject $ro, array $server = []): bool
    {
        $method = new ReflectionMethod(DownloadResponder::class, 'isDownload');

        return (bool) $method->invoke($this->responder(), $ro, $server);
    }

    private function downloadOutput(ResourceObject $ro): Output
    {
        $method = new ReflectionMethod(DownloadResponder::class, 'downloadOutput');

        /** @var Output */
        return $method->invoke($this->responder(), $ro);
    }

    private function responder(): DownloadResponder
    {
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
        );
    }
}

final class DownloadResponderTestResource extends ResourceObject
{
}
