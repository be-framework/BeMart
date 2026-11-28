<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

use function explode;
use function getenv;
use function is_array;
use function is_string;

/**
 * A story passes only on the response it ends on: a chain that stops at a 4xx returns normally.
 */
abstract class AbstractLinkStoryCase extends TestCase
{
    /** @param string|list<string> $flow the `flow-*` tags of alps.json the story walks; empty when the row declares none */
    protected function assertFlow(string $uri, string|array $flow): void
    {
        // The golden being recorded is not written yet, and the one on disk may be the old one
        if (getenv('UPDATE_GOLDEN') === '1') {
            return;
        }

        $flows = is_string($flow) ? [$flow] : $flow;
        $this->assertSame([], StoryCoverage::forRepository()->flowMismatch((string) $this->dataName(), $uri, $flows), 'The flow tags of the row and of alps.json disagree.');
    }

    /** @param array<string, mixed> $body values the end body holds; a dot in a key reaches into a nested array */
    protected function assertEnd(ResourceObject $ro, int $code, string $path, array $body): void
    {
        $this->assertSame($code, $ro->code);
        $this->assertSame($path, $ro->uri->path);
        $this->assertIsArray($ro->body);
        foreach ($body as $key => $value) {
            $this->assertSame($value, $this->bodyValue($ro->body, $key), $key);
        }
    }

    /** @param array<array-key, mixed> $body */
    private function bodyValue(array $body, string $key): mixed
    {
        $found = $body;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($found)) {
                return null;
            }

            $found = $found[$segment] ?? null;
        }

        return $found;
    }
}
