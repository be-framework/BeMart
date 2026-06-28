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

use function str_contains;

/**
 * Phase 3 — markup-parity check for the idea-admin clean-room
 * PluginList.html.twig (Store section DATA/LIST page).
 *
 * L1 — structural / semantic landmarks (idea-admin-shell / content frame,
 *       required data present in output).
 * L2 — form action + method correctness, hypermedia link href/rel.
 *
 * EC-CUBE reference rendering tests (testPluginListHtmlMatchesEcCubeRenderingWithinResidualAllowlist)
 * have been moved to @group ec-cube-parity-archived and skipped — the
 * clean-room template no longer derives from EC-CUBE markup.
 */
final class AdminPluginListHtmlRenderTest extends TestCase
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

    // ── L1: structural landmarks ─────────────────────────────────────────

    /**
     * @group plugin-html-l1
     */
    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/plugin-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * The shell frame must use idea-admin-* landmarks, not the old
     * c-container / c-mainNavArea / c-headerBar hierarchy.
     *
     * @group plugin-html-l1
     */
    public function testIdeatAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
            'class="idea-admin-page-header"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin landmark missing: {$landmark}");
        }

        // Old EC-CUBE / Bootstrap landmarks must not appear.
        foreach ([
            'c-container',
            'c-mainNavArea',
            'c-headerBar',
            'c-contentsArea',
            'c-primaryCol',
        ] as $old) {
            $this->assertStringNotContainsString($old, $html, "EC-CUBE landmark still present: {$old}");
        }
    }

    /**
     * Plugin list data must be rendered from the resource body.
     * Fake storage seeds two plugins: Sample/SamplePlugin (enabled) and
     * Sample/DisabledPlugin (disabled).
     *
     * @group plugin-html-l1
     */
    public function testPluginListDataRendered(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        // Plugin names from FakePluginStorage seed
        $this->assertStringContainsString('Sample Plugin', $html);
        $this->assertStringContainsString('Disabled Sample Plugin', $html);

        // Plugin codes
        $this->assertStringContainsString('Sample/SamplePlugin', $html);
        $this->assertStringContainsString('Sample/DisabledPlugin', $html);

        // Status badges
        $this->assertStringContainsString('idea-admin-badge--public', $html, '有効バッジがない');
        $this->assertStringContainsString('idea-admin-badge--private', $html, '無効バッジがない');

        // Table structure
        $this->assertStringContainsString('idea-admin-table-wrap', $html);
        $this->assertStringContainsString('idea-admin-table', $html);
    }

    /**
     * The install form must expose all three required fields.
     *
     * @group plugin-html-l1
     */
    public function testInstallFormFieldsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        foreach (['pluginCode', 'pluginName', 'pluginVersion'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "install field missing: {$field}");
        }
    }

    // ── L2: hypermedia / form action+method ──────────────────────────────

    /**
     * Install form must POST to /admin/plugin-list (doInstallPlugin).
     *
     * @group plugin-html-l2
     */
    public function testInstallFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        $this->assertStringContainsString('action="/admin/plugin-list"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * Enable affordance must POST to /admin/plugin-enable (doEnablePlugin).
     * The disabled plugin (Sample/DisabledPlugin) should surface the enable form.
     *
     * @group plugin-html-l2
     */
    public function testEnableFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        $this->assertStringContainsString('action="/admin/plugin-enable"', $html);
    }

    /**
     * Disable affordance must POST to /admin/plugin-disable (doDisablePlugin).
     * The enabled plugin (Sample/SamplePlugin) should surface the disable form.
     *
     * @group plugin-html-l2
     */
    public function testDisableFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        $this->assertStringContainsString('action="/admin/plugin-disable"', $html);
    }

    /**
     * Uninstall affordance must POST to /admin/plugin with _method=delete
     * (doUninstallPlugin). Only the disabled plugin shows the delete button.
     *
     * @group plugin-html-l2
     */
    public function testUninstallFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        $this->assertStringContainsString('action="/admin/plugin"', $html);
        $this->assertStringContainsString('name="_method" value="delete"', $html);
    }

    /**
     * Enabled plugin must NOT show the uninstall button (UX guard: must
     * disable before uninstalling).
     *
     * @group plugin-html-l2
     */
    public function testEnabledPluginHasNoUninstallButton(): void
    {
        $html = $this->resource->get('page://self/admin/plugin-list')->toString();

        // The enabled plugin's dialog id would contain its pluginCode.
        // Sample/SamplePlugin (enabled) → id attribute would be
        // uninstall-dialog-sample-sampleplugin if rendered.
        $this->assertStringNotContainsString('uninstall-dialog-sample-sampleplugin', $html);
    }

    // ── EC-CUBE parity archived ──────────────────────────────────────────

    /**
     * This test compared BeMart's HTML against EC-CUBE's rendered output.
     * The clean-room template no longer derives from EC-CUBE markup, so
     * this comparison is no longer meaningful.
     *
     * @group ec-cube-parity-archived
     */
    public function testPluginListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering parity archived: PluginList.html.twig is a clean-room '
            . 'idea-admin rebuild and no longer mirrors EC-CUBE DOM structure.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testPluginListRendersAsHtmlDocument(): void
    {
        $this->markTestSkipped('Superseded by testRendersAsHtmlDocument (idea-admin build).');
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testPluginListPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure (c-* / Bootstrap) archived: '
            . 'PluginList.html.twig uses idea-admin-* landmarks.',
        );
    }
}
