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
 * HTML render fidelity for the admin Member-list page (goMemberList).
 *
 * Verification contract — two levels:
 *   L1  Required fields / list data output
 *       name, loginId present per member row; count chip; new-registration link.
 *   L2  Form action/method and link href/rel semantics
 *       Search form: GET /admin/member-list.
 *       Delete form:  POST /admin/member?loginId=…&_method=delete + csrfToken.
 *       Edit link:    GET  /admin/member?loginId=….
 *       Register link: GET /admin/member.
 *
 * EC-CUBE rendering comparison tests are retired:
 * @group ec-cube-parity-archived
 */
final class AdminMemberListHtmlRenderTest extends TestCase
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

    // ── L0: document shape ───────────────────────────────────────────────────

    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testUsesIdeaAdminShellFrame(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "frame landmark missing: {$landmark}");
        }
    }

    // ── L1: required data output ─────────────────────────────────────────────

    public function testMemberNamesAppearInTable(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        // Fake seed always contains at least these admin names.
        foreach (['テスト管理者', '店舗オーナー'] as $name) {
            $this->assertStringContainsString($name, $html, "member name missing from table: {$name}");
        }
    }

    public function testMemberLoginIdsAppearInTable(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        foreach (['test-admin', 'shop-owner'] as $loginId) {
            $this->assertStringContainsString($loginId, $html, "loginId missing from table: {$loginId}");
        }
    }

    public function testCountChipIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        // Count chip must appear; exact number depends on the Fake seed.
        $this->assertMatchesRegularExpression('/class="idea-admin-count"[^>]*>\s*\d+件/', $html);
    }

    public function testTableUsesIdeaAdminTableVocabulary(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        foreach ([
            'class="idea-admin-table-wrap"',
            'class="idea-admin-table"',
            'class="idea-admin-table__actions"',
        ] as $cls) {
            $this->assertStringContainsString($cls, $html, "idea-admin-table vocabulary missing: {$cls}");
        }
    }

    // ── L2: link / form semantics ─────────────────────────────────────────────

    public function testSearchFormIsGetToMemberList(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/admin/member-list"', $html);
        $this->assertStringContainsString('name="nameKeyword"', $html);
    }

    public function testNewRegistrationLinkPointsToMemberResource(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        $this->assertStringContainsString('href="/admin/member"', $html);
    }

    public function testEditLinkContainsLoginId(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        // At least one edit link must carry the ?loginId= parameter.
        $this->assertStringContainsString('href="/admin/member?loginId=', $html);
    }

    public function testDeleteFormUsesPostWithMethodOverride(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        // Delete forms POST with _method=delete tunnel and csrfToken hidden field.
        $this->assertStringContainsString('_method=delete', $html);
        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertStringContainsString('name="mode" value="member_form"', $html);
    }

    // ── EC-CUBE parity tests (archived) ───────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testMemberListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering comparison retired: template rebuilt as idea-admin clean-room. '
            . 'Functional coverage is provided by L1/L2 assertions above.',
        );
    }
}
