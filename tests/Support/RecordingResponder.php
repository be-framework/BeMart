<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use Override;

/** Test double that captures the ResourceObject a handler transfers. */
final class RecordingResponder implements TransferInterface
{
    public ResourceObject|null $ro = null;

    /** @var array<string, mixed> */
    public array $server = [];

    /** @param array<string, mixed> $server */
    #[Override]
    public function __invoke(ResourceObject $ro, array $server): void
    {
        $this->ro = $ro;
        $this->server = $server;
    }
}
