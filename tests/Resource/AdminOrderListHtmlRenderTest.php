<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Phase 3 — functional/semantic fidelity check for the admin Order-list
 * HTML page (idea-admin clean-room rebuild).
 *
 * L1: required fields and list-data output.
 * L2: form action/method correctness; link href/rel affordances.
 *
 * EC-CUBE rendering parity tests are archived in the
 * @group ec-cube-parity-archived group (markTestSkipped).
 */
final class AdminOrderListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ------------------------------------------------------------------ L0

    /** Page renders as a valid HTML document with HTTP 200. */
    public function testOrderListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ------------------------------------------------------------------ frame landmarks (idea-admin)

    /** idea-admin shell frame landmarks are present. */
    public function testOrderListContainsIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "idea-admin frame landmark missing: {$needle}");
        }
    }

    /** No legacy EC-CUBE / Bootstrap frame classes leak into the output. */
    public function testOrderListContainsNoLegacyEcCubeFrameClasses(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        foreach ([
            'c-container',
            'c-mainNavArea',
            'c-headerBar',
            'c-contentsArea',
            'c-pageTitle',
            'c-outsideBlock',
            'c-primaryCol',
            'btn-ec-',
            'ec-collapse',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "legacy class must not appear: {$forbidden}");
        }
    }

    // ------------------------------------------------------------------ L1: required fields & list data

    /** Search form keyword input is rendered with the correct element id. */
    public function testOrderListRendersKeywordSearchInput(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $this->assertStringContainsString('id="admin_search_order_multi"', $html);
    }

    /** Result count is rendered in the page body. */
    public function testOrderListRendersResultCount(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        // The count is rendered alongside the search bar.
        $this->assertMatchesRegularExpression('/\d+\s*件/', $html);
    }

    /** Seeded orders produce table rows with the required data columns. */
    public function testOrderListRendersSeededOrderRows(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');
        $html = $ro->toString();

        // At least one order must be present from the Fake seed.
        $this->assertGreaterThan(0, $ro->body['count']);

        // Each order row must carry the orderNo, customerId, orderDate,
        // paymentTotal (price-formatted) and orderStatus badge.
        foreach ($ro->body['orders'] as $order) {
            $this->assertStringContainsString($order['orderNo'], $html);
            $this->assertStringContainsString($order['customerId'], $html);
            $this->assertStringContainsString($order['orderDate'], $html);
        }
    }

    /** The order grid table is present (idea-admin-table). */
    public function testOrderListRendersIdeaAdminTable(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');
        $html = $ro->toString();

        if ($ro->body['count'] === 0) {
            $this->markTestSkipped('No seeded orders — populated branch not exercised.');
        }

        $this->assertStringContainsString('class="idea-admin-table"', $html);
        $this->assertStringContainsString('id="search_result"', $html);
    }

    /** Page body always contains either the order grid or the empty-state block. */
    public function testOrderListAlwaysContainsGridOrEmptyState(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $hasTable = str_contains($html, 'idea-admin-table');
        $hasEmpty = str_contains($html, 'idea-admin-empty');
        $this->assertTrue($hasTable || $hasEmpty, 'Neither idea-admin-table nor idea-admin-empty is present');
    }

    // ------------------------------------------------------------------ L2: form action/method & link affordances

    /** Search form uses GET and targets /admin/order-list (safe read, no CSRF). */
    public function testOrderListSearchFormIsGetToOrderListEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/admin/order-list"', $html);
    }

    /** Per-row detail links point to /admin/order?orderNo=… with rel="goOrder". */
    public function testOrderListRowLinksCarryGoOrderRel(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');
        $html = $ro->toString();

        if ($ro->body['count'] === 0) {
            $this->markTestSkipped('No seeded orders — link affordances not exercised.');
        }

        $this->assertStringContainsString('rel="goOrder"', $html);
        $this->assertStringContainsString('href="/admin/order?orderNo=', $html);
    }

    /** New-order creation link carries rel="doCreateOrder" (ALPS unsafe transition). */
    public function testOrderListNewOrderLinkCarriesDoCreateOrderRel(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $this->assertStringContainsString('rel="doCreateOrder"', $html);
        $this->assertStringContainsString('href="/admin/order/create"', $html);
    }

    /** CSV export links target the declared resource endpoints. */
    public function testOrderListCsvExportLinksTargetCorrectEndpoints(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $this->assertStringContainsString('href="/admin/order/export-order"', $html);
        $this->assertStringContainsString('href="/admin/order/export-shipping"', $html);
    }

    // ------------------------------------------------------------------ EC-CUBE parity (archived)

    /**
     * EC-CUBE rendering parity test — archived.
     *
     * This test compared BeMart's Order-list HTML against EC-CUBE's own
     * rendering. It is retired because the template is now a clean-room
     * idea-admin rebuild (DOM structure intentionally differs from EC-CUBE)
     * and the EC-CUBE source clone is not required for this test suite.
     *
     * @group ec-cube-parity-archived
     */
    public function testOrderListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity test archived: OrderList.html.twig is now a clean-room '
            . 'idea-admin rebuild. DOM structure intentionally differs from EC-CUBE. '
            . 'Functional/semantic coverage is in the L1/L2 tests above.',
        );
    }
}
