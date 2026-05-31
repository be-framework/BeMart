<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Docs;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function is_string;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function trim;

/**
 * Regression tests for the generated EC-CUBE route/status table.
 *
 * The table is used as a planning artifact, so a generic ActionRedirect
 * fallback must not make every missing route look Hard. Difficulty should
 * follow the intended ALPS transition first, then use concrete Resource source
 * only as supporting evidence.
 */
final class EccubeFeatureAlpsStatusHtmlTest extends TestCase
{
    public function testKnownFalsePositiveRoutesHaveAuditedDifficulty(): void
    {
        $rows = self::rowsByRouteAndMethod();

        self::assertSame('Normal', self::difficulty($rows['admin_setting_shop_delivery_sort_no_move GET']));
        self::assertSame('Normal', self::difficulty($rows['admin_setting_system_member_up POST']));
        self::assertSame('Normal', self::difficulty($rows['admin_setting_system_member_down POST']));
        self::assertSame('Normal', self::difficulty($rows['admin_store_template_download GET']));
        self::assertSame('Hard', self::difficulty($rows['admin_product_csv_class_name POST']));
        self::assertSame('Hard', self::difficulty($rows['admin_change_password POST']));
        self::assertSame('Easy', self::difficulty($rows['homepage GET']));
    }

    public function testHardLessActionRedirectFallbacksAreGone(): void
    {
        $violations = [];
        foreach (self::rows() as $row) {
            if (
                str_contains($row['implementation'], 'ActionRedirect')
                && in_array(self::difficulty($row), ['Easy', 'Normal'], true)
            ) {
                $violations[] = $row['route'] . ' ' . $row['method'] . ' ' . $row['title'];
            }
        }

        self::assertSame([], $violations);
    }

    public function testHardActionRedirectDoesNotContainSimpleListOrDetailFallbacks(): void
    {
        $violations = [];
        foreach (self::rows() as $row) {
            if (
                self::difficulty($row) !== 'Hard'
                || ! str_contains($row['implementation'], 'ActionRedirect')
                || self::isCsvLike($row['title'])
            ) {
                continue;
            }

            if (
                str_contains($row['title'], '一覧を見る')
                || str_contains($row['title'], '詳細を見る')
                || str_ends_with($row['title'], 'を見る')
            ) {
                $violations[] = $row['route'] . ' ' . $row['method'] . ' ' . $row['title'];
            }
        }

        self::assertSame([], $violations);
    }

    public function testBrowserVerificationColumnExistsAndAuditTargetsAreVerified(): void
    {
        self::assertContains('ブラウザ確認', self::headers());
        $rows = self::rowsByRouteAndMethod();

        foreach (self::browserVerifiedTargets() as $key) {
            self::assertArrayHasKey($key, $rows);
            self::assertStringStartsWith('確認済み', $rows[$key]['browserVerification'], $key);
            self::assertStringContainsString('Codex内ブラウザ', $rows[$key]['browserVerification'], $key);
        }
    }

    public function testCsvPasswordSecurityAndTemplateFileRoutesStayHard(): void
    {
        $rows = self::rowsByRouteAndMethod();

        self::assertSame('Hard', self::difficulty($rows['admin_product_csv_class_name POST']));
        self::assertSame('Hard', self::difficulty($rows['admin_change_password POST']));
        self::assertSame('Hard', self::difficulty($rows['admin_setting_system_security POST']));
        self::assertSame('Hard', self::difficulty($rows['admin_store_template_download POST']));
    }

    public function testIssue24HardActionRedirectRowsAreConnected(): void
    {
        // The 22 Hard rows that Issue #24 re-classification originally
        // parked on the generic ActionRedirect safe-evacuation. This PR
        // connects every one to a concrete Be/BEAR resource, so each row
        // must now read 実装済み (no longer ActionRedirect), keep its Hard
        // difficulty, and carry an audited migration strategy. This test
        // pins that headline deliverable positively: a regression that
        // re-parks any route on ActionRedirect, downgrades its difficulty,
        // or drops the row from the table fails here. (Note: once a row is
        // connected it no longer carries the "Issue #24 Hard ActionRedirect
        // 再分類" planning note, so this asserts the *connected* state, not
        // that note.)
        $known = [
            'admin_change_password POST',
            'admin_content_cache POST',
            'admin_content_css POST',
            'admin_content_js POST',
            'admin_content_maintenance POST',
            'admin_product_class_category_export GET',
            'admin_product_class_category_export POST',
            'admin_product_class_name_export GET',
            'admin_product_class_name_export POST',
            'admin_product_csv_class_category GET',
            'admin_product_csv_class_category POST',
            'admin_product_csv_class_name GET',
            'admin_product_csv_class_name POST',
            'admin_setting_system_masterdata POST',
            'admin_setting_system_masterdata_edit POST',
            'admin_setting_system_security POST',
            'admin_store_template POST',
            'admin_store_template_delete POST',
            'admin_store_template_download POST',
            'admin_store_template_install POST',
            'admin_two_factor_auth POST',
            'admin_two_factor_auth_set POST',
        ];
        $validStrategies = ['native', 'adapter', 'legacy compatibility', 'out-of-scope'];

        $rows = self::rowsByRouteAndMethod();
        $strategyCounts = ['native' => 0, 'adapter' => 0, 'legacy compatibility' => 0, 'out-of-scope' => 0];
        foreach ($known as $key) {
            self::assertArrayHasKey($key, $rows, "Issue #24 route missing from status table: {$key}");
            $row = $rows[$key];
            self::assertStringNotContainsString(
                'ActionRedirect',
                $row['implementation'],
                "Issue #24 route is still parked on ActionRedirect (regression): {$key}",
            );
            self::assertContains(
                self::difficulty($row),
                ['Hard', 'Super Hard'],
                "Issue #24 route difficulty was downgraded: {$key}",
            );
            $strategy = self::strategy($row);
            self::assertContains($strategy, $validStrategies, "Unaudited strategy: {$key}");
            $strategyCounts[$strategy]++;
        }

        // All 22 routes connected, with the audited 4 native + 18 adapter split.
        self::assertSame([
            'native' => 4,
            'adapter' => 18,
            'legacy compatibility' => 0,
            'out-of-scope' => 0,
        ], $strategyCounts);

        // Invariant: no Hard ActionRedirect row may exist outside the known
        // set — guards against a NEW unaudited route appearing on the generic
        // fallback. Given every known member was just asserted connected,
        // this loop is expected to find nothing today.
        foreach (self::rows() as $row) {
            if (
                ! str_contains($row['implementation'], 'ActionRedirect')
                || ! in_array(self::difficulty($row), ['Hard', 'Super Hard'], true)
            ) {
                continue;
            }

            $key = $row['route'] . ' ' . $row['method'];
            self::assertContains($key, $known, "Unexpected Hard ActionRedirect row: {$key}");
        }
    }

    public function testPdfPilotIsLegacyCompatibility(): void
    {
        $rows = self::rowsByRouteAndMethod();

        foreach (['admin_order_export_pdf GET', 'admin_order_export_pdf POST'] as $key) {
            self::assertArrayHasKey($key, $rows);
            self::assertSame('Hard', self::difficulty($rows[$key]));
            self::assertSame('legacy compatibility', self::strategy($rows[$key]));
            self::assertStringContainsString('Issue #24 PDF legacy compatibility', $rows[$key]['assessment']);
            self::assertStringNotContainsString('stub明記', $rows[$key]['assessment']);
        }
    }

    /**
     * @return array<string, array{
     *     route: string,
     *     method: string,
     *     title: string,
     *     implementation: string,
     *     browserVerification: string,
     *     assessment: string
     * }>
     */
    private static function rowsByRouteAndMethod(): array
    {
        $indexed = [];
        foreach (self::rows() as $row) {
            $indexed[$row['route'] . ' ' . $row['method']] = $row;
        }

        return $indexed;
    }

    /**
     * @return list<array{
     *     route: string,
     *     method: string,
     *     title: string,
     *     implementation: string,
     *     browserVerification: string,
     *     assessment: string
     * }>
     */
    private static function rows(): array
    {
        $html = file_get_contents(dirname(__DIR__, 2) . '/docs/eccube-feature-alps-status.html');
        self::assertIsString($html);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $rows = [];
        foreach ($xpath->query('//table[@id="features"]/tbody/tr') as $tr) {
            self::assertInstanceOf(DOMElement::class, $tr);
            $cells = $xpath->query('./td', $tr);
            self::assertNotFalse($cells);
            self::assertSame(11, $cells->length);

            $rows[] = [
                'route' => self::text($cells->item(0)),
                'method' => self::text($cells->item(1)),
                'title' => self::text($cells->item(5)),
                'implementation' => self::text($cells->item(7)),
                'browserVerification' => self::text($cells->item(8)),
                'assessment' => self::text($cells->item(9)),
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private static function headers(): array
    {
        $html = file_get_contents(dirname(__DIR__, 2) . '/docs/eccube-feature-alps-status.html');
        self::assertIsString($html);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $headers = [];
        foreach ($xpath->query('//table[@id="features"]/thead/tr/th') as $th) {
            self::assertInstanceOf(DOMElement::class, $th);
            $headers[] = self::text($th);
        }

        return $headers;
    }

    /** @return list<string> */
    private static function browserVerifiedTargets(): array
    {
        return [
            'admin_order_bulk_delete GET',
            'admin_product_bulk_product_status GET',
            'admin_product_class_category_sort_no_move GET',
            'admin_product_class_category_visibility GET',
            'admin_product_class_name_sort_no_move GET',
            'admin_product_product_copy GET',
            'admin_product_tag_sort_no_move GET',
            'admin_setting_shop_calendar POST',
            'admin_setting_shop_calendar_delete GET',
            'admin_setting_shop_calendar_delete POST',
            'admin_setting_shop_calendar_new POST',
            'admin_setting_shop_delivery_sort_no_move GET',
            'admin_setting_shop_delivery_visibility GET',
            'admin_setting_shop_mail_delete GET',
            'admin_setting_shop_mail_delete POST',
            'admin_setting_shop_order_status POST',
            'admin_setting_shop_payment_sort_no_move GET',
            'admin_setting_shop_payment_visible GET',
            'admin_setting_shop_tradelaw POST',
            'admin_setting_system_member_down GET',
            'admin_setting_system_member_down POST',
            'admin_setting_system_member_up GET',
            'admin_setting_system_member_up POST',
            'admin_shipping_notify_mail GET',
            'admin_shipping_update_order_status GET',
            'admin_shipping_update_tracking_number GET',
            'admin_store_template_download GET',
        ];
    }

    /** @param array{assessment: string} $row */
    private static function difficulty(array $row): string
    {
        foreach (['Super Hard', 'Hard', 'Normal', 'Easy'] as $difficulty) {
            if (str_starts_with($row['assessment'], $difficulty)) {
                return $difficulty;
            }
        }

        self::fail('Unknown difficulty cell: ' . $row['assessment']);
    }

    /** @param array{assessment: string} $row */
    private static function strategy(array $row): string
    {
        foreach (['legacy compatibility', 'out-of-scope', 'adapter', 'native'] as $strategy) {
            if (str_contains($row['assessment'], ' ' . $strategy)) {
                return $strategy;
            }
        }

        self::fail('Unknown strategy cell: ' . $row['assessment']);
    }

    private static function text(DOMElement|null $cell): string
    {
        self::assertInstanceOf(DOMElement::class, $cell);
        $text = preg_replace('/\s+/u', ' ', $cell->textContent);

        return trim(is_string($text) ? $text : $cell->textContent);
    }

    private static function isCsvLike(string $title): bool
    {
        return str_contains($title, 'CSV')
            || str_contains($title, 'エクスポート')
            || str_contains($title, 'インポート');
    }
}
