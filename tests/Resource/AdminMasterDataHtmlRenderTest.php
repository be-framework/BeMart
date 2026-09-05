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
 * Phase 3 — HTML render verification for the admin マスタデータ管理 page.
 *
 * Levels:
 *   L1 — required data is present in the rendered output (selectedMaster, masterTypes list)
 *   L2 — form actions, _method field, ALPS rel flow
 *   Frame — idea-admin landmarks are present (shell / content)
 *
 * EC-CUBE markup parity checks are archived; they were specific to the old
 * EC-CUBE–derived template and do not apply to the clean-room idea-admin build.
 */
final class AdminMasterDataHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

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

    // ── Frame landmark checks ────────────────────────────────────────────

    /**
     * The page renders a complete HTML document through HtmlModule.
     */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/master-data');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /**
     * idea-admin-shell and idea-admin-content landmarks are present — these
     * come from admin-base.html.twig and confirm the frame is intact.
     */
    public function testIdeaAdminFrameLandmarksPresent(): void
    {
        $html = $this->resource->get('page://self/admin/master-data')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html, 'idea-admin-shell missing');
        $this->assertStringContainsString('class="idea-admin-content"', $html, 'idea-admin-content missing');
    }

    // ── L1: required data presence ───────────────────────────────────────

    /**
     * The page title "マスタデータ管理" is output somewhere in the document.
     */
    public function testPageTitlePresent(): void
    {
        $html = $this->resource->get('page://self/admin/master-data')->toString();

        $this->assertStringContainsString('マスタデータ管理', $html);
    }

    /**
     * The masterType select element is rendered with the id that
     * AdminMasterDataForm sets: admin_system_masterdata_masterdata.
     */
    public function testMasterTypeSelectPresent(): void
    {
        $html = $this->resource->get('page://self/admin/master-data')->toString();

        $this->assertStringContainsString('admin_system_masterdata_masterdata', $html);
    }

    /**
     * The phase-1 select form targets /admin/master-data with a PUT tunnel.
     */
    public function testSelectFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/master-data')->toString();

        $this->assertStringContainsString('action="/admin/master-data"', $html, 'select action missing');
        $this->assertStringContainsString('name="_method"', $html, '_method hidden field missing');
        $this->assertStringContainsString('value="PUT"', $html, '_method=PUT missing');
    }

    /**
     * The CSRF token field is present in the rendered output.
     */
    public function testCsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/master-data')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    // ── L2: phase-2 edit form (PUT doUpdateMasterData) ───────────────────

    /**
     * After a PUT (doSelectMasterData) the edit form is rendered with the
     * correct action and masterType hidden field.
     */
    public function testEditFormPresentAfterSelectPut(): void
    {
        $ro = $this->resource->put(
            'page://self/admin/master-data',
            ['masterType' => 'tag'],
        );

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString(
            'action="/admin/master-data-edit"',
            $html,
            'edit form action missing',
        );
        $this->assertStringContainsString(
            'name="masterType"',
            $html,
            'masterType hidden field missing in edit form',
        );
    }

    // ── EC-CUBE parity (archived) ─────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEcCubeAdminMarkupParity(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity test archived. '
            . 'The template is a clean-room idea-admin build and does not mirror '
            . 'EC-CUBE DOM structure (c-headerBar, c-contentsArea, form1 id). '
            . 'Functional equivalence is verified by the L1/L2 tests above.',
        );
    }
}
