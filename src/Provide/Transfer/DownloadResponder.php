<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Transfer;

use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use BEAR\Sunday\Provide\Transfer\ConditionalResponseInterface;
use BEAR\Sunday\Provide\Transfer\HeaderInterface;
use BEAR\Sunday\Provide\Transfer\Output;
use Override;

use function header;
use function http_response_code;
use function is_array;
use function is_resource;
use function is_scalar;
use function is_string;
use function json_encode;
use function stream_get_contents;
use function str_contains;
use function strtolower;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/** Transfers explicit download responses without representation rendering. */
final class DownloadResponder implements TransferInterface
{
    public function __construct(
        private readonly HeaderInterface $header,
        private readonly ConditionalResponseInterface $conditionalResponse,
    ) {
    }

    /** {@inheritDoc} */
    #[Override]
    public function __invoke(ResourceObject $ro, array $server): void
    {
        /** @var array{HTTP_IF_NONE_MATCH?: string} $server */
        $isModified = $this->conditionalResponse->isModified($ro, $server);
        $output = $isModified
            ? ($this->isDownload($ro, $server) ? $this->downloadOutput($ro) : $this->renderedOutput($ro, $server))
            : $this->conditionalResponse->getOutput($ro->headers);

        foreach ($output->headers as $label => $value) {
            header("{$label}: {$value}", false);
        }

        http_response_code($output->code);
        echo $output->view;
    }

    /** @param array<string, string> $server */
    private function renderedOutput(ResourceObject $ro, array $server): Output
    {
        $ro->toString();

        return new Output($ro->code, ($this->header)($ro, $server), $ro->view ?? $ro->toString());
    }

    private function downloadOutput(ResourceObject $ro): Output
    {
        /** @var array<string, string> $headers */
        $headers = [];
        foreach ($ro->headers as $label => $value) {
            if (is_string($value)) {
                $headers[$label] = $value;
            }
        }

        return new Output($ro->code, $headers, $this->body($ro->body));
    }

    /** @param array<string, mixed> $server */
    private function isDownload(ResourceObject $ro, array $server): bool
    {
        $contentType = $ro->headers['Content-Type'] ?? null;
        if (! is_string($contentType)) {
            return false;
        }

        $contentType = strtolower($contentType);
        $context = (string) ($server['_BEMART_CONTEXT'] ?? '');

        return (str_contains($context, 'html') && str_contains($contentType, 'text/csv'))
            || str_contains($contentType, 'application/zip')
            || str_contains($contentType, 'application/pdf')
            || str_contains($contentType, 'application/octet-stream');
    }

    /** @param mixed $body */
    private function body(mixed $body): string
    {
        if (is_resource($body)) {
            return (string) stream_get_contents($body);
        }

        if (is_scalar($body)) {
            return (string) $body;
        }

        if (! is_array($body)) {
            return '';
        }

        foreach (['csv', 'pdf', 'zip', 'file', 'content'] as $key) {
            $value = $body[$key] ?? null;
            if (is_string($value)) {
                return $value;
            }
        }

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
