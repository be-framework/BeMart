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
use Twig\Environment;

use function json_decode;
use function preg_match;
use function str_contains;
use function substr_count;

/**
 * HTML render fidelity for the admin Member-list page (goMemberList).
 *
 * Verification contract — three levels:
 *   L1  Required fields / list data output
 *       name, loginId present per member row; count chip; new-registration link.
 *   L2  Form action/method and link href/rel semantics
 *       Search form: GET /admin/member-list.
 *       Delete form:  POST /admin/member?loginId=…&_method=delete + csrfToken.
 *       Edit link:    GET  /admin/member?loginId=….
 *       Register link: GET /admin/member.
 *   L3  Injection boundary: the per-row delete `onclick` JS string literal.
 *
 * Only the retired EC-CUBE rendering comparison carries
 * `@group ec-cube-parity-archived`; a class-level group would exclude the
 * L1/L2/L3 contract from the default suite.
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

    // ── L3: injection boundary ────────────────────────────────────────────────

    /**
     * The delete button interpolates the login ID into a JS string literal
     * inside an `onclick` attribute. `html` escaping does not hold there: the
     * attribute parser decodes `&#039;` back into a quote before the JS parser
     * sees it. The grid renders stored rows, so the template defends even
     * though {@see \MyVendor\BeMart\Be\Semantic\LoginId} now rejects the
     * payload at the write boundary.
     */
    public function testDeleteDialogOnclickCannotBreakOutOfTheJsStringLiteral(): void
    {
        $html = $this->renderMemberList("evil'); alert(1); ('");

        $onclick = $this->dialogOnclick($html);
        $this->assertSame(2, substr_count($onclick, "'"), 'the JS string keeps exactly its two delimiters');
        $this->assertStringNotContainsString('&#039;', $onclick);
        $this->assertStringNotContainsString('&#x27;', $onclick);
        $this->assertStringNotContainsString('alert(1)', $onclick);
    }

    /**
     * Escaping must not break the id handshake: the JS-escaped literal has to
     * evaluate back to the dialog's `id` attribute.
     */
    public function testDeleteDialogOnclickTargetsTheRenderedDialogId(): void
    {
        $html = $this->renderMemberList('test-admin');

        $matched = preg_match("/getElementById\('([^']*)'\)/", $this->dialogOnclick($html), $matches);
        $this->assertSame(1, $matched, 'delete-dialog id argument not rendered');

        // `\uXXXX` is what keeps the literal intact; JSON shares the escape
        // syntax, so decoding it gives the value the JS runtime would see.
        $evaluated = json_decode('"' . $matches[1] . '"');

        $this->assertSame('delete-dialog-test-admin', $evaluated);
        $this->assertStringContainsString('id="delete-dialog-test-admin"', $html);
    }

    private function renderMemberList(string $loginId): string
    {
        $twig = HtmlTestInjector::getInstance()->getInstance(Environment::class);

        return $twig->render('Page/Admin/MemberList.html.twig', [
            'members' => [
                [
                    'adminId' => 'ad0000000000000000000000000000ff',
                    'loginId' => $loginId,
                    'name' => '侵入者',
                    'authority' => 1,
                    'work' => 1,
                    'sortNo' => 1,
                ],
            ],
            'count' => 1,
            'filters' => ['nameKeyword' => null, 'limit' => 50, 'offset' => 0],
            'csrfToken' => 'render-test-token',
        ]);
    }

    /** The `onclick` that opens the per-row delete dialog. */
    private function dialogOnclick(string $html): string
    {
        $matched = preg_match('/onclick="(document\.getElementById\([^"]*)"/', $html, $matches);
        $this->assertSame(1, $matched, 'delete-dialog onclick attribute not rendered');

        return $matches[1];
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
