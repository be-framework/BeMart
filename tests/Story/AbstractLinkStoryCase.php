<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

/**
 * A story passes only on the response it ends on: a chain that stops at a 4xx returns normally.
 */
abstract class AbstractLinkStoryCase extends TestCase
{
    /** @param array<string, mixed> $body */
    protected function assertEnd(ResourceObject $ro, int $code, string $path, array $body): void
    {
        $this->assertSame($code, $ro->code);
        $this->assertSame($path, $ro->uri->path);
        $this->assertIsArray($ro->body);
        foreach ($body as $key => $value) {
            $this->assertSame($value, $ro->body[$key] ?? null, $key);
        }
    }
}
