<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function glob;
use function is_array;
use function preg_match_all;
use function sprintf;
use function str_contains;

/**
 * Guard the definition of "workflow evidence".
 *
 * A workflow may hardcode its entrypoint. It may not create business state by
 * directly posting to arbitrary page resources or by using fixture boundaries.
 * Unsafe transitions must take the URL from the previous representation.
 */
final class WorkflowBackdoorStateCoverageTest extends TestCase
{
    /** @return iterable<array{string}> */
    public static function workflowFiles(): iterable
    {
        $files = glob(__DIR__ . '/Flow*.php');
        self::assertIsArray($files);

        foreach ($files as $file) {
            yield $file => [$file];
        }
    }

    #[DataProvider('workflowFiles')]
    public function testWorkflowDoesNotCreateBusinessStateThroughFixtureBoundary(string $file): void
    {
        $source = $this->source($file);

        self::assertStringNotContainsString(
            'WorkflowFixtureBoundary',
            $source,
            sprintf('%s must create business data through hypermedia transitions, not fixture boundaries.', $file),
        );
    }

    #[DataProvider('workflowFiles')]
    public function testUnsafeTransitionsDoNotHardcodePageUris(string $file): void
    {
        $source = $this->source($file);
        preg_match_all(
            '/\\$this->resource->(?P<method>post|put|patch|delete)\\(\\s*[\'"]page:\\/\\/self\\//',
            $source,
            $matches,
        );

        self::assertSame(
            [],
            $matches['method'],
            sprintf(
                '%s contains unsafe direct page://self calls. Use linkHref($previousResponse, $rel) or Location from the previous representation.',
                $file,
            ),
        );
    }

    #[DataProvider('workflowFiles')]
    public function testOrdersAreCreatedThroughStorefrontCheckout(string $file): void
    {
        $source = $this->source($file);

        self::assertStringNotContainsString(
            'doCreateOrder',
            $source,
            sprintf(
                '%s must not use admin manual order creation as business-state setup. Create orders through storefront checkout and reuse the resulting orderNo.',
                $file,
            ),
        );
    }

    private function source(string $file): string
    {
        $source = file_get_contents($file);
        self::assertIsString($source);
        self::assertFalse(str_contains($source, 'markTestSkipped('), sprintf('%s must not hide incomplete workflow steps.', $file));
        self::assertFalse(str_contains($source, 'markTestIncomplete('), sprintf('%s must not hide incomplete workflow steps.', $file));

        return $source;
    }
}
