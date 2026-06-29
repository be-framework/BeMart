<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function str_contains;

/**
 * Phase 3 — semantic render check for the admin Layout-list page.
 *
 * Tests verify:
 *   L1  Required data is present in the rendered output.
 *   L2  Action links carry correct href / rel.
 *   Frame  Shell landmark classes produced by admin-base.html.twig.
 *
 * EC-CUBE layout_list.twig parity tests were retired:
 *   @group ec-cube-parity-archived
 *   Reason: LayoutList.html.twig is a clean-room idea-admin-* design.
 *   The former markup-diff assertions are superseded by semantic tests here.
 */
final class AdminLayoutListHtmlRenderTest extends TestCase
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

    /** L0: response is 200 HTML document. */
    public function testLayoutListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/layout/layout-list');

        $this->assertSame(Code::OK, $ro->code);
        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** Frame landmark: idea-admin-shell + idea-admin-content are rendered by the base. */
    public function testLayoutListContainsFrameLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /**
     * L1: required data fields are present.
     * Fake seed provides lo-pc-default (PC標準) and lo-sp-default (スマホ標準).
     */
    public function testLayoutListRendersLayoutNames(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        $this->assertStringContainsString('PC標準', $html);
        $this->assertStringContainsString('スマホ標準', $html);
    }

    /** L1: layout IDs are rendered (used in row anchors + hrefs). */
    public function testLayoutListRendersLayoutIds(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        $this->assertStringContainsString('lo-pc-default', $html);
        $this->assertStringContainsString('lo-sp-default', $html);
    }

    /**
     * L2: goLayout links carry the correct href pattern
     * (ALPS #[Link] rel=goLayout -> /admin/layout/layout?layoutId=…).
     */
    public function testLayoutListGoLayoutLinksContainLayoutId(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        $this->assertStringContainsString('/admin/layout/layout?layoutId=lo-pc-default', $html);
        $this->assertStringContainsString('/admin/layout/layout?layoutId=lo-sp-default', $html);
    }

    /** L2: goLayout links carry rel="goLayout" attribute. */
    public function testLayoutListGoLayoutLinksHaveRelAttribute(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        $this->assertTrue(
            str_contains($html, 'rel="goLayout"'),
            'Expected at least one anchor with rel="goLayout"',
        );
    }

    /** L1: device-type badges distinguish PC (deviceType=10) from mobile (deviceType=2). */
    public function testLayoutListRendersDeviceTypeBadges(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout-list')->toString();

        // The template renders fa-desktop for PC and fa-mobile for mobile.
        $this->assertStringContainsString('fa-desktop', $html);
        $this->assertStringContainsString('fa-mobile', $html);
    }

    /** L1: layout count is rendered. */
    public function testLayoutListRendersCount(): void
    {
        $ro = $this->resource->get('page://self/admin/layout/layout-list');
        $count = $ro->body['count'];
        $html  = $ro->toString();

        $this->assertGreaterThan(0, $count);
        $this->assertStringContainsString((string) $count, $html);
    }

    /**
     * EC-CUBE parity test — retired.
     *
     * The LayoutList page is a clean-room idea-admin-* design and no longer
     * mirrors EC-CUBE's Content/layout_list.twig markup. The DOM-diff
     * approach is superseded by the semantic tests above.
     *
     * @group ec-cube-parity-archived
     */
    public function testLayoutListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity test retired: LayoutList.html.twig is a clean-room '
            . 'idea-admin-* design; EC-CUBE markup mirroring is no longer required.',
        );
    }
}
