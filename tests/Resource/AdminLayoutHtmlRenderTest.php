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
 * Phase 3 — semantic render check for the admin Layout editor page.
 *
 * Tests verify:
 *   L0  Response is 200 HTML document.
 *   L1  Required data fields are present in the rendered output.
 *   L2  Action form carries correct action / method attributes and
 *       link anchors carry the expected href / rel.
 *   Frame  Shell landmark classes produced by admin-base.html.twig.
 *
 * EC-CUBE parity tests were never created for this page;
 * any future EC-CUBE markup assertions should be marked:
 *   @group ec-cube-parity-archived + markTestSkipped.
 */
final class AdminLayoutHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID  = 'ad000000000000000000000000000001';
    private const TEST_LAYOUT_ID = 'lo-pc-default';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session  = new FakeAdminSession(self::TEST_ADMIN_ID);
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
    public function testLayoutEditorRendersAsHtmlDocument(): void
    {
        $ro   = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID]);
        $html = $ro->toString();

        $this->assertSame(Code::OK, $ro->code);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** Frame landmark: idea-admin-shell + idea-admin-content are rendered by the base. */
    public function testLayoutEditorContainsFrameLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /** L1: the editable name field renders with the saved layout name. */
    public function testLayoutEditorRendersNameInput(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('id="admin_layout_name"', $html);
        $this->assertStringContainsString('PC標準', $html);
    }

    /** L1: layout ID is rendered in the page for reference / action targeting. */
    public function testLayoutEditorRendersLayoutId(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString(self::TEST_LAYOUT_ID, $html);
    }

    /** L1: device type badge is rendered for existing layouts. */
    public function testLayoutEditorRendersDeviceTypeBadge(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        // PC layout (deviceType=10) renders fa-desktop icon
        $this->assertStringContainsString('fa-desktop', $html);
    }

    /**
     * L2: form action targets the PUT endpoint.
     * The action must contain _method=put and layoutId.
     */
    public function testLayoutEditorFormActionTargetsPutEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertTrue(
            str_contains($html, '_method=put'),
            'Expected form action to contain _method=put',
        );
        $this->assertTrue(
            str_contains($html, 'layoutId=' . self::TEST_LAYOUT_ID),
            'Expected form action to contain layoutId query param',
        );
    }

    /** L2: form carries CSRF token hidden input. */
    public function testLayoutEditorFormCarriesCsrfToken(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /**
     * L2: goLayoutList back-link carries rel="goLayoutList" and correct href.
     */
    public function testLayoutEditorBackLinkHasGoLayoutListRel(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertTrue(
            str_contains($html, 'rel="goLayoutList"'),
            'Expected at least one anchor with rel="goLayoutList"',
        );
        $this->assertStringContainsString('/admin/layout/layout-list', $html);
    }

    /** L2: designer section drop zones are rendered with data-section attributes. */
    public function testLayoutEditorRendersDesignerSections(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString('data-section="1"', $html);  // <head>
        $this->assertStringContainsString('data-section="10"', $html); // #footer
        $this->assertStringContainsString('data-section="13"', $html); // palette
    }

    /** L1: block palette section rendered. */
    public function testLayoutEditorRendersPalette(): void
    {
        $html = $this->resource->get('page://self/admin/layout/layout', ['layoutId' => self::TEST_LAYOUT_ID])->toString();

        $this->assertStringContainsString('id="block-palette"', $html);
    }

    /**
     * EC-CUBE parity test — not applicable.
     *
     * The Layout editor is a clean-room idea-admin-* design;
     * no EC-CUBE markup mirroring is required or implemented.
     *
     * @group ec-cube-parity-archived
     */
    public function testLayoutEditorHtmlMatchesEcCubeRendering(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity test not applicable: Layout.html.twig is a clean-room '
            . 'idea-admin-* design; EC-CUBE markup mirroring is not required.',
        );
    }
}
