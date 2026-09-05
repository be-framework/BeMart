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
 * HTML render verification for the admin dashboard (Index).
 *
 * Asserting against the idea-admin design language frame and the data
 * contracts defined in src/Resource/Page/Admin/Index.php and
 * var/json_schema/get-admin-index.json.
 *
 * L1 — required fields must appear in rendered output.
 * L2 — form actions / link hrefs derived from #[Link] annotations.
 *
 * EC-CUBE reference rendering tests have been archived:
 *
 * @group ec-cube-parity-archived
 */
final class AdminIndexHtmlRenderTest extends TestCase
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

    // ── Frame landmarks ───────────────────────────────────────────────────────

    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/index');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testIdeaAdminShellFrameIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        foreach ([
            'idea-admin-shell',
            'idea-admin-topbar',
            'idea-admin-sidebar',
            'idea-admin-content',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "frame landmark missing: {$landmark}");
        }
    }

    // ── L1: required data fields must be rendered ─────────────────────────────

    public function testShopStatusKpiGridIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        // The three real KPI labels from DashboardCountsQuery
        foreach (['取扱商品数', '会員数', '在庫切れ商品数'] as $label) {
            $this->assertStringContainsString($label, $html, "KPI label missing: {$label}");
        }
    }

    public function testSalesSummaryKpiGridIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        foreach (['今月の売上', '本日の売上', '昨日の売上'] as $label) {
            $this->assertStringContainsString($label, $html, "sales KPI label missing: {$label}");
        }
    }

    public function testOrderStatusPanelIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        $this->assertStringContainsString('注文状況', $html);
        // panel uses the idea-admin-panel structure
        $this->assertStringContainsString('idea-admin-panel', $html);
    }

    public function testPluginPanelIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        $this->assertStringContainsString('インストール済みプラグイン', $html);
    }

    // ── L2: link hrefs derived from #[Link] annotations ──────────────────────

    public function testProductListLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        // goMemberList → /admin/member-list (in sidebar), plus product-list KPI links
        $this->assertStringContainsString('href="/admin/product-list"', $html);
    }

    public function testCustomerListLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        $this->assertStringContainsString('href="/admin/customer-list"', $html);
    }

    public function testOrderListLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        $this->assertStringContainsString('href="/admin/order-list"', $html);
    }

    public function testPluginListLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/index')->toString();

        // goMemberList is in the sidebar frame; plugin-list appears in the plugin panel
        $this->assertStringContainsString('href="/admin/plugin-list"', $html);
    }

    // ── EC-CUBE parity archived ───────────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEcCubeDomParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE admin frame DOM parity (c-container / c-mainNavArea / Bootstrap) '
            . 'is superseded by the idea-admin clean-room rebuild. '
            . 'Archived under @group ec-cube-parity-archived.',
        );
    }

}
