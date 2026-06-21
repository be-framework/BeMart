<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;
use function str_replace;

/**
 * HTML hypermedia walk of the admin shop-configuration pages — driven entirely
 * by the rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Ported from tests/Hypermedia/FlowAdminShopConfigurationTest.php.
 *
 * Steps walked:
 *   1. testOpensBaseInfo         — GET /admin/base-info (200 + doUpdateBaseInfo form)
 *   2. testUpdatesBaseInfo       — submit doUpdateBaseInfo → 200
 *   3. testOpensTradeLaw         — GET /admin/trade-law (200 + doUpdateTradeLaw form)
 *   4. testUpdatesTradeLaw       — submit doUpdateTradeLaw → 303 + follow Location
 *   5. testOpensTaxRuleList      — GET /admin/tax-rule/tax-rule-list (200 + doCreateTaxRule form)
 *   6. testCreatesTaxRule        — submit doCreateTaxRule → 201 + follow Location
 *   7. testOpensCalendar         — GET /admin/calendar (200 + doCreateCalendarHoliday form)
 *   8. testCreatesCalendarHoliday — submit doCreateCalendarHoliday → 201, title in list
 *
 * Steps skipped (no HTML form affordance rendered):
 *   - doCreatePayment: PaymentList template has no <form class="doCreatePayment">.
 *     The "新規作成" button links to /admin/payment/payment (GET), which renders
 *     a form with class="doUpdatePayment" — not a HTML-followable doCreatePayment.
 *   - doUpdatePayment / doDeletePayment: PaymentList renders delete as a JS modal
 *     anchor; update requires navigating to the edit page with a known paymentId,
 *     then submitting a PUT form — not a simple HTML affordance chain.
 *   - doCreateDelivery / doUpdateDelivery / doDeleteDelivery: DeliveryList template
 *     has no inline create form; CRUD uses JS modal and separate edit page.
 *   - doDeleteTaxRule: rendered as a JS modal `<a>` (data-post-action), not a
 *     `<form class="doDeleteTaxRule">`.
 *   - doUpdateCalendar: inline JS-toggled edit row — not a persistently-rendered form.
 *   - doDeleteCalendarHoliday: rendered as a JS modal `<a rel="doDeleteCalendarHoliday">`,
 *     not a `<form>`. submit() only matches `<form>` affordances.
 */
final class FlowAdminShopConfigurationTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-shop-config-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-shop-config-html-csrf-token';

    private static string $suffix;
    private static string $taxApplyDate;
    private static string $calendarTitle;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$suffix = bin2hex(random_bytes(4));
        self::$taxApplyDate = '2028-03-' . (string) (10 + (int) (hexdec(self::$suffix[0]) % 9)) . 'T10:00';
        self::$calendarTitle = 'HTML Holiday ' . self::$suffix;
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8130',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    // -----------------------------------------------------------------------
    // Step 1-2: Base Info
    // -----------------------------------------------------------------------

    #[Alps('goBaseInfo')]
    public function testOpensBaseInfo(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateBaseInfo');

        return $page;
    }

    #[Alps('doUpdateBaseInfo')]
    #[Depends('testOpensBaseInfo')]
    public function testUpdatesBaseInfo(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateBaseInfo', [
            'shopName' => 'HTML Shop Config ' . self::$suffix,
            'shopKana' => 'エイチティーエムエル',
            'shopNameEng' => 'HTML Shop',
            'companyName' => 'HTML Company',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => 'HTML1-1-' . self::$suffix,
            'phoneNumber' => '0312345678',
            'businessHour' => '10:00-18:00',
            'shopEmail01' => 'html-shop-config-' . self::$suffix . '@example.com',
            'shopMessage' => 'Updated by ' . self::FLOW_ID . '.',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateBaseInfo affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    // -----------------------------------------------------------------------
    // Step 3-4: Trade Law
    // -----------------------------------------------------------------------

    #[Alps('goTradeLawList')]
    #[Depends('testUpdatesBaseInfo')]
    public function testOpensTradeLaw(ResourceObject $updated): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateTradeLaw');

        return $page;
    }

    #[Alps('doUpdateTradeLaw')]
    #[Depends('testOpensTradeLaw')]
    public function testUpdatesTradeLaw(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateTradeLaw', [
            'trade_law_1_name' => '販売事業者',
            'trade_law_1_description' => 'HTML Company ' . self::$suffix,
            'trade_law_2_name' => '代表者',
            'trade_law_2_description' => 'HTML Taro',
            'mode' => 'trade_law_form',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateTradeLaw affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        $page = $this->header($updated, 'Location') !== null
            ? $this->followLocation($updated)
            : $updated;

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));

        return $page;
    }

    // -----------------------------------------------------------------------
    // Step 5-6: Tax Rule
    // -----------------------------------------------------------------------

    #[Alps('goTaxRuleList')]
    #[Depends('testUpdatesTradeLaw')]
    public function testOpensTaxRuleList(ResourceObject $page): ResourceObject
    {
        $taxPage = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');

        $this->assertSame(Code::OK, $taxPage->code, (string) ($taxPage->view ?? $taxPage->code));
        $this->assertAffordance($taxPage, 'doCreateTaxRule');

        return $taxPage;
    }

    #[Alps('doCreateTaxRule')]
    #[Depends('testOpensTaxRuleList')]
    public function testCreatesTaxRule(ResourceObject $taxPage): ResourceObject
    {
        $created = $this->submit($taxPage, 'doCreateTaxRule', [
            'taxRate' => '8.5',
            'applyDate' => self::$taxApplyDate,
            'roundingType' => '1',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doCreateTaxRule affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        // The Location header points to the detail resource (DELETE-only: no onGet).
        // Re-GET the list to confirm the new rule appears.
        $list = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');
        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));

        return $list;
    }

    // -----------------------------------------------------------------------
    // Step 7-8: Calendar Holiday
    // -----------------------------------------------------------------------

    #[Alps('goCalendar')]
    #[Depends('testCreatesTaxRule')]
    public function testOpensCalendar(ResourceObject $page): ResourceObject
    {
        $calPage = $this->resource->get('page://self/admin/calendar');

        $this->assertSame(Code::OK, $calPage->code, (string) ($calPage->view ?? $calPage->code));
        $this->assertAffordance($calPage, 'doCreateCalendarHoliday');

        return $calPage;
    }

    #[Alps('doCreateCalendarHoliday')]
    #[Depends('testOpensCalendar')]
    public function testCreatesCalendarHoliday(ResourceObject $calPage): void
    {
        $created = $this->submit($calPage, 'doCreateCalendarHoliday', [
            'title' => self::$calendarTitle,
            'holiday' => '2028-04-29',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doCreateCalendarHoliday affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        $location = $this->header($created, 'Location');
        $list = $location !== null
            ? $this->followLocation($created)
            : $this->resource->get('page://self/admin/calendar');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(self::$calendarTitle, (string) ($list->view ?? ''));
    }
}
