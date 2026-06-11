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

    #[DataProvider('workflowFiles')]
    public function testCustomerPurchaseUsesInstallerPaymentMasters(string $file): void
    {
        if (! str_contains($file, 'FlowCustomerPurchaseTest.php')) {
            $this->addToAssertionCount(1);

            return;
        }

        $source = $this->source($file);

        self::assertStringNotContainsString(
            'doCreatePayment',
            $source,
            'flow-customer-purchase must not create payment methods as setup. It must prove checkout works from the installer payment masters loaded by setup-db.',
        );
    }

    public function testWebE2ERunnerDoesNotCreatePaymentAsPurchaseSetup(): void
    {
        $source = $this->source(__DIR__ . '/../../scripts/web-e2e-runner.mjs');

        self::assertStringNotContainsString(
            "step('admin-payment-create'",
            $source,
            'Web+DB runner must not create a purchase-only payment method before checkout; checkout must work from setup-db installer payment masters.',
        );
    }

    public function testWebE2ERunnerReadsBackNonMemberConfirmBeforeCheckout(): void
    {
        $source = $this->source(__DIR__ . '/../../scripts/web-e2e-runner.mjs');

        self::assertStringContainsString(
            'non-member submit did not redirect to confirm',
            $source,
            'Web+DB runner must require the browser-form 303 Location to /shopping/confirm.',
        );
        self::assertStringContainsString(
            'shopping-non-member-confirm',
            $source,
            'Web+DB runner must save screenshot evidence from the non-member confirm page, not only the POST Location.',
        );
        self::assertStringContainsString(
            'confirm page includes non-member email and installer payment name',
            $source,
            'Web+DB runner must read back non-member customer/payment data before checkout.',
        );
    }

    public function testWebE2ERunnerRequiresScreenshotEvidenceForUnsafeOperationPass(): void
    {
        $source = $this->source(__DIR__ . '/../../scripts/web-e2e-runner.mjs');

        self::assertStringContainsString(
            'if (unsafeCoveredBySetup && operationScreenshot)',
            $source,
            'Web+DB runner must not mark unsafe operations pass without screenshot-backed setup operation evidence.',
        );
        self::assertStringContainsString(
            'unsafe operation not executed',
            $source,
            'Web+DB runner must fail browser-reached unsafe rows when the unsafe operation was not actually executed.',
        );
        self::assertStringContainsString(
            'Browser navigation reached the page, but',
            $source,
            'Web+DB runner must record why page navigation alone is not enough evidence for unsafe operations.',
        );
    }

    public function testWebE2ERunnerRecordsNetworkBoundary(): void
    {
        $source = $this->source(__DIR__ . '/../../scripts/web-e2e-runner.mjs');

        self::assertStringContainsString(
            'baseUrl is resolved from the runner process',
            $source,
            'Web+DB runner reports must state that localhost/127.0.0.1 are resolved from the runner process.',
        );
        self::assertStringContainsString(
            'ローカルChrome/in-app browserが別マシンで動く場合',
            $source,
            'Web+DB reports must keep runner evidence separate from local browser evidence.',
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
