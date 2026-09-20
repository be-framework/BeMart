<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Alps;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_diff;
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
 * Nineteen references had drifted; fifteen were resolved (#143 — the
 * customer, customer-delivery-edit, and product-class dead forms; the
 * log/order-pdf/order-mail-confirm/template-add id renames; and the
 * route-gate/fallback/placeholder ids, which dropped `#[Alps]` since they
 * answer a URL that EC-CUBE has and BeMart deliberately does not model as
 * an application transition). The remaining four are listed below.
 */
final class AlpsReferenceTest extends TestCase
{
    /**
     * Screens and actions that exist as ported markup but were never added to
     * the profile. Two of these are also the two remaining dead forms
     * ({@see \MyVendor\BeMart\Tests\Router\TemplateFormActionTest}:
     * `goAdminContentFileManager` / `goAdminTwoFactorAuthEdit`), so the write
     * handler and the descriptor land together when those are picked up.
     */
    private const SCREEN_GAP = [
        'doCreateMailTemplate',
        'goAdminContentFileManager',
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
        return self::SCREEN_GAP;
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
