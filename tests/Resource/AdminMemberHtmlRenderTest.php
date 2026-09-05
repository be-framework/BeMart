<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminMemberForm;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\SystemJaMessages;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\WebFormModule\FormFactory;
use Twig\Environment;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_diff;
use function array_filter;
use function array_values;
use function count;
use function dirname;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function is_dir;
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — idea-admin clean-room Member editor: HTML render verification.
 *
 * Verifies the admin Member-edit page rendered by
 * var/templates/Page/Admin/Member.html.twig (idea-admin-* frame, no
 * EC-CUBE / Bootstrap / c-* classes). Checks:
 *
 *   L0 – shell frame: idea-admin-shell / idea-admin-content landmarks
 *   L1 – required field markup: name, loginId (new), password/passwordConfirm (new)
 *        and authority select rendered by the real AdminMemberForm
 *   L2 – form action/method semantics and back-link href/rel
 *   L3 – edit-mode vs create-mode branching
 *
 * The EC-CUBE markup-parity diff test is retired to the
 * ec-cube-parity-archived group; the template is no longer a port of
 * EC-CUBE's member_edit.twig DOM — it is a clean-room build.
 */
final class AdminMemberHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** The pre-seeded admin in be/var/fake/admins.json. */
    private const SEED_LOGIN_ID = 'test-admin';

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

    // ── L0: shell frame ──────────────────────────────────────────────────

    public function testMemberEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testMemberEditRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-content"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "shell landmark missing: {$landmark}");
        }
    }

    // ── L1: required field markup ─────────────────────────────────────────

    /**
     * The form inputs are rendered by a real AdminMemberForm: the page
     * carries the EC-CUBE field ids / attributes, pre-filled with the
     * persisted profile.
     */
    public function testMemberEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        $this->assertStringContainsString('id="admin_member_name"', $html);
        // The seed admin's profile is repopulated from the resource body.
        $this->assertStringContainsString('value="テスト管理者"', $html);
    }

    public function testMemberNewRendersLoginIdAndPasswordFields(): void
    {
        $html = $this->resource->get('page://self/admin/member')->toString();

        $this->assertStringContainsString('id="admin_member_loginId"', $html);
        $this->assertStringContainsString('id="admin_member_password"', $html);
        $this->assertStringContainsString('id="admin_member_passwordConfirm"', $html);
        $this->assertStringContainsString('id="admin_member_authority"', $html);
    }

    public function testMemberNewDoesNotRenderPasswordFieldsInEditMode(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        // Password inputs are create-only.
        $this->assertStringNotContainsString('id="admin_member_password"', $html);
        $this->assertStringNotContainsString('id="admin_member_passwordConfirm"', $html);
    }

    // ── L2: form action / method + navigation links ───────────────────────

    public function testMemberNewFormActionIsCreateEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/member')->toString();

        $this->assertStringContainsString('action="/admin/member"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('name="mode" value="member_form"', $html);
    }

    public function testMemberEditFormActionIsUpdateEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        $this->assertStringContainsString('action="/admin/member?_method=put"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('name="mode" value="member_form"', $html);
    }

    public function testMemberEditDeleteFormActionContainsLoginId(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        // The delete affordance form targets the delete endpoint with loginId.
        $this->assertStringContainsString('_method=delete', $html);
        $this->assertStringContainsString('loginId=', $html);
    }

    public function testMemberEditBackLinkPointsToMemberList(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        $this->assertStringContainsString('href="/admin/member-list"', $html);
    }

    // ── L3: create vs edit branching ─────────────────────────────────────

    public function testMemberListDeleteFormIsBrowserVisibleAffordance(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        $this->assertStringContainsString('action="/admin/member?loginId=', $html);
        $this->assertStringContainsString('_method=delete', $html);
        $this->assertStringContainsString('name="mode" value="member_form"', $html);
    }

    // ── Archived: EC-CUBE markup-parity diff ──────────────────────────────

    /**
     * This test compared BeMart's Member-edit HTML line-by-line against
     * EC-CUBE 4.3's member_edit.twig rendering. The template is now a
     * clean-room build in idea-admin-* vocabulary; it no longer mirrors
     * EC-CUBE's DOM, so the parity diff is not meaningful.
     *
     * Retired to ec-cube-parity-archived group. The EC-CUBE reference clone
     * check is left in place so the test is skipped gracefully if the clone
     * is not present.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testMemberEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $this->markTestSkipped(
            'Member.html.twig is now a clean-room idea-admin-* build; '
            . 'EC-CUBE markup-parity diff is retired to ec-cube-parity-archived.',
        );
    }
}
