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
 * Phase 3 — HTML render verification for the admin 権限ルール管理 page.
 *
 * Tests are organised into three layers:
 *
 *   L0 — Frame:    idea-admin-shell landmark, content region, HTML scaffold
 *   L1 — Data:     required data surfaces (rules list, authority options, KPI count)
 *   L2 — Actions:  form action/method, CSRF field, back-nav link rel/href
 *
 * EC-CUBE residual markup assertions have been retired.
 * Any test that compared against EC-CUBE's reference DOM is archived below
 * with @group ec-cube-parity-archived and markTestSkipped().
 */
final class AdminAuthorityRoleHtmlRenderTest extends TestCase
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

    // ── L0: Frame ──────────────────────────────────────────────────────────

    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'frame: idea-admin-shell landmark missing');
        $this->assertStringContainsString('idea-admin-content', $html, 'frame: idea-admin-content region missing');
    }

    // ── L1: Data ───────────────────────────────────────────────────────────

    public function testRuleTableRendered(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('idea-admin-rule-tbody', $html, 'L1: rule tbody missing');
        $this->assertStringContainsString('AuthorityRoles[', $html, 'L1: rule field names missing');
        $this->assertStringContainsString('[deny_url]', $html, 'L1: deny_url field missing');
        $this->assertStringContainsString('[Authority]', $html, 'L1: Authority select missing');
    }

    public function testAuthorityOptionsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('システム管理者', $html, 'L1: システム管理者 option missing');
        $this->assertStringContainsString('店舗オーナー', $html, 'L1: 店舗オーナー option missing');
    }

    public function testKpiCountChipRendered(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('idea-admin-rule-count', $html, 'L1: KPI count chip missing');
    }

    public function testPageTitleRendered(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('権限ルール管理', $html, 'L1: page title missing');
    }

    // ── L2: Actions ────────────────────────────────────────────────────────

    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('action="/admin/authority-role"', $html, 'L2: form action missing');
        $this->assertStringContainsString('method="post"', $html, 'L2: form method missing');
    }

    public function testCsrfFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'L2: csrfToken field missing');
    }

    public function testBackNavLinkToMemberList(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('href="/admin/member-list"', $html, 'L2: goMemberList href missing');
        $this->assertStringContainsString('rel="goMemberList"', $html, 'L2: goMemberList rel missing');
    }

    public function testSaveButtonPresent(): void
    {
        $html = $this->resource->get('page://self/admin/authority-role')->toString();

        $this->assertStringContainsString('type="submit"', $html, 'L2: submit button missing');
        $this->assertStringContainsString('保存', $html, 'L2: save label missing');
    }

    // ── Archived: EC-CUBE parity ────────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM parity retired: BeMart uses idea-admin design language. '
            . 'Functional equivalence is verified by L0/L1/L2 tests above.',
        );
    }
}
