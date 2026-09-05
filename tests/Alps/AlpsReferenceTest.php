<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Alps;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_diff;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match_all;
use function sort;

use const JSON_THROW_ON_ERROR;
use const PREG_SET_ORDER;

/**
 * `alps.json` is the SSOT, and `#[Alps('id')]` is a resource claiming to
 * implement one of its transitions. Nothing verified that the id exists:
 * `asd --validate` checks the profile, not the references into it.
 *
 * Nineteen references had drifted. They are listed below, split by what each
 * one needs, because the fix differs per group and every one of them is a
 * decision about the canonical profile rather than about code.
 */
final class AlpsReferenceTest extends TestCase
{
    /**
     * Route-gate and fallback plumbing. These answer a URL that EC-CUBE has
     * and BeMart deliberately does not model as an application transition.
     * Either the profile gains gate descriptors, or these stop carrying
     * `#[Alps]` — they are not screens a client can discover.
     */
    private const ROUTE_GATE = [
        'doActionRedirect',
        'doAdminActionRedirect',
        'doAdminUnsupportedRoute',
        'doUnsupportedRoute',
        'goActionRedirect',
        'goAdminActionRedirect',
        'goAdminUnsupportedRoute',
        'goUnsupportedRoute',
    ];

    /** A rendered placeholder, not a transition. */
    private const PLACEHOLDER = [
        'goAdminEmptyPage',
    ];

    /**
     * Screens and actions that exist as ported markup but were never added to
     * the profile. `goAdminOrderMailConfirm` is the closest to a plain rename:
     * the profile carries `goOrderMailConfirm` already. The rest are genuinely
     * absent — `goAdminOrderOrderPdf` and `goShoppingShippingMultipleEdit` are
     * editor screens distinct from the `goExportOrderPdf` and
     * `goShoppingShippingMultiple` transitions they link to.
     *
     * Four of these are also the four remaining dead forms
     * ({@see \MyVendor\BeMart\Tests\Router\TemplateFormActionTest}), so the
     * write handler and the descriptor land together.
     */
    private const SCREEN_GAP = [
        'doCreateMailTemplate',
        'goAdminContentFileManager',
        'goAdminCustomerDeliveryEdit',
        'goAdminLog',
        'goAdminOrderMailConfirm',
        'goAdminOrderOrderPdf',
        'goAdminProductProductClass',
        'goAdminTemplateTemplateAdd',
        'goAdminTwoFactorAuthEdit',
        'goShoppingShippingMultipleEdit',
    ];

    public function testEveryAlpsReferenceExistsInTheProfile(): void
    {
        $missing = array_diff($this->referencedIds(), $this->profileIds());

        $this->assertSame([], array_values(array_diff($missing, $this->known())));
    }

    public function testKnownGapListHasNoStaleEntry(): void
    {
        $missing = array_diff($this->referencedIds(), $this->profileIds());

        $this->assertSame([], array_values(array_diff($this->known(), $missing)));
    }

    /** A profile that stopped parsing would make the assertions above vacuous. */
    public function testProfileIsPopulated(): void
    {
        $this->assertGreaterThan(400, count($this->profileIds()));
        $this->assertGreaterThan(100, count($this->referencedIds()));
    }

    /** @return list<string> */
    private function known(): array
    {
        return array_merge(self::ROUTE_GATE, self::PLACEHOLDER, self::SCREEN_GAP);
    }

    /** @return list<string> */
    private function profileIds(): array
    {
        $profile = json_decode(
            (string) file_get_contents(__DIR__ . '/../../alps.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $ids = [];
        if (is_array($profile)) {
            /** @var mixed $descriptors */
            $descriptors = $profile['alps']['descriptor'] ?? [];
            if (is_array($descriptors)) {
                foreach ($descriptors as $descriptor) {
                    if (! is_array($descriptor)) {
                        continue;
                    }

                    /** @var mixed $id */
                    $id = $descriptor['id'] ?? null;
                    if (! is_string($id)) {
                        continue;
                    }

                    $ids[] = $id;
                }
            }
        }

        return $this->normalize($ids);
    }

    /** @return list<string> */
    private function referencedIds(): array
    {
        $ids = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../src'),
        );
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            preg_match_all("/#\[Alps\('([^']+)'\)\]/", $contents, $matches, PREG_SET_ORDER);
            foreach ($matches as [, $id]) {
                $ids[] = $id;
            }
        }

        return $this->normalize($ids);
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function normalize(array $values): array
    {
        $unique = array_values(array_unique($values));
        sort($unique);

        return $unique;
    }
}
